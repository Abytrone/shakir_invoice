<?php

namespace App\Console\Commands;

use App\Constants\InvoiceStatus;
use App\Models\Invoice;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoBillClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-bill-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    public function handle(PaystackService $paystackService): void
    {
        $this->info('Starting auto bill client...');
        $authBillInvoice = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::PAID])
            ->where('is_recurring', true)
            ->withWhereHas('client', function ($query) {
                $query->whereNotNull('auth_email')
                    ->whereNotNull('auth_res');
            })
            ->get();

        $billedCount = 0;
        foreach ($authBillInvoice as $invoice) {

            $res = json_decode($invoice->client->auth_res);
            $res = $paystackService->chargeAuthorization($invoice->client->auth_email, $res->authorization_code, $invoice->total);

            $data = $res->json();

            if (!$data['status']) {
                $this->error("Transaction error: Failed to bill client {$invoice->client->name} for invoice #{$invoice->id}");
                Log::error("Transaction error", [$data]);
                continue;
            }

            if ($data['data']['status'] == 'failed') {
                $this->error("Payment error: Failed to bill client {$invoice->client->name} for invoice #{$invoice->id}");
                continue;
            }


            $verify = $paystackService->verify($data['data']['reference']);
            $verifyRes = json_decode($verify);

            if (!$verifyRes->status) {
                $this->error("Failed to verify payment for client {$invoice->client->name} for invoice #{$invoice->id}");
                continue;
            }

            $billedCount++;
            $saved = $paystackService->savePaymentAfterVerification($verify, $invoice);

            if (!$saved) {
                $this->error("Failed to save payment for client {$invoice->client->name} for invoice #{$invoice->id}");
            }

            //todo: send email notification

        }

        $this->info("$billedCount has been billed...");
    }
}
