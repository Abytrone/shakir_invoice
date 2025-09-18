<?php

namespace App\Console\Commands;

use App\Constants\InvoiceStatus;
use App\Models\Invoice;
use App\Services\PaystackService;
use Illuminate\Console\Command;

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
                $query->where('auth_email', '!=', null)
                    ->where('auth_res', '!=', null);
            })
            ->get();

        $billedCount = 0;
        foreach ($authBillInvoice as $invoice) {
            if ($invoice->isOverdue()) {
                $res = json_decode($invoice->client->auth_res);
                if ($invoice->client->shouldBeBillAutomatically()) {
                    $res = $paystackService->chargeAuthorization($invoice->client->auth_email, $res->authorization_code, $invoice->total);
                    $data = $res->json();
                    if ($data['data']['status'] == 'failed') {
                        $this->error("Failed to bill client {$invoice->client->name} for invoice #{$invoice->id}");
                        continue;
                    }
                    $billedCount++;
                    $invoice->payments()->create([
                        'amount' => $invoice->total,
                        'payment_date' => now(),
                        'payment_method' => 'credit_card',
                        'note' => 'Auto billed via Paystack',
                        'reference_number'=>$data['data']['reference'],
                        'status'=>'completed',
                    ]);
                }
                //todo: send email notification
            }
        }

        $this->info("$billedCount has been billed...");
    }
}
