<?php

namespace App\Console\Commands;

use App\Constants\InvoiceStatus;
use App\Constants\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoBillClient extends Command
{
    protected $signature = 'app:auto-bill-client';

    protected $description = 'Automatically bill clients with saved payment authorization';

    public function handle(PaystackService $paystackService): void
    {
        $this->info('Starting auto bill client...');

        $this->createPaymentIntents();
        $billedCount = $this->processPendingPayments($paystackService);

        $this->info("$billedCount has been billed...");
    }

    private function createPaymentIntents(): void
    {
        $billableInvoices = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::PAID, InvoiceStatus::CANCELLED])
            ->where('is_recurring', true)
            ->withWhereHas('client', function ($query) {
                $query->whereNotNull('auth_email')
                    ->whereNotNull('auth_res');
            })
            ->whereDoesntHave('allPayments', function ($query) {
                $query->where('status', PaymentStatus::PENDING);
            })
            ->get();

        foreach ($billableInvoices as $invoice) {
            $balance = $invoice->balance;

            if ($balance <= 0) {
                continue;
            }

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $balance,
                'payment_method' => "card",
                'status' => PaymentStatus::PENDING,
                'notes' => 'Auto-billing intent',
            ]);

            $this->line("Created payment intent for invoice #{$invoice->invoice_number}");
        }
    }

    private function processPendingPayments(PaystackService $paystackService): int
    {
        $pendingPayments = Payment::query()
            ->where('status', PaymentStatus::PENDING)
            ->where('attempts', '<', PaymentStatus::MAX_ATTEMPTS)
            ->with('invoice.client')
            ->get();

        $billedCount = 0;

        foreach ($pendingPayments as $payment) {
            $invoice = $payment->invoice;
            $client = $invoice->client;

            if ($invoice->isPaid()) {
                $payment->update(['status' => PaymentStatus::FAILED, 'failure_reason' => 'Invoice already paid']);
                $this->line("Invoice #{$invoice->invoice_number} already paid, cancelled pending payment");
                continue;
            }

            $authRes = json_decode($client->auth_res);

            if (!$authRes || !isset($authRes->authorization_code)) {
                $this->markFailed($payment, 'Missing authorization data');
                continue;
            }

            $payment->update([
                'attempts' => $payment->attempts + 1,
                'last_attempted_at' => now(),
            ]);

            $res = $paystackService->chargeAuthorization(
                $client->auth_email,
                $authRes->authorization_code,
                $payment->amount
            );

            if (!$res) {
                $this->handleFailure($payment, $invoice, 'Paystack request failed');
                continue;
            }

            $data = $res->json();

            if (!$data['status']) {
                $this->handleFailure($payment, $invoice, $data['message'] ?? 'Transaction error');
                $this->error("Transaction error: Failed to bill client {$client->name} for invoice #{$invoice->id}");
                continue;
            }

            if ($data['data']['status'] === 'failed') {
                $this->handleFailure($payment, $invoice, $data['data']['gateway_response'] ?? 'Payment declined');
                $this->error("Payment error: Failed to bill client {$client->name} for invoice #{$invoice->id}");
                continue;
            }

            $chargeData = $data['data'];

            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'reference_number' => $chargeData['reference'],
                'payment_method' => $chargeData['channel'],
                'amount' => $chargeData['amount'] / 100,
                'failure_reason' => null,
            ]);

            $invoice->refresh();

            if ($invoice->isPaid()) {
                $invoice->update(['status' => InvoiceStatus::PAID]);
            } elseif ($invoice->isPartial()) {
                $invoice->update(['status' => InvoiceStatus::PARTIAL]);
            }

            $billedCount++;
        }

        return $billedCount;
    }

    private function handleFailure(Payment $payment, Invoice $invoice, string $reason): void
    {
        $updates = ['failure_reason' => $reason];

        if ($payment->attempts >= PaymentStatus::MAX_ATTEMPTS) {
            $updates['status'] = PaymentStatus::FAILED;
            Log::error("Auto-billing permanently failed after {$payment->attempts} attempts", [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'reason' => $reason,
            ]);
        }

        $payment->update($updates);
    }

    private function markFailed(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => $reason,
        ]);
    }
}
