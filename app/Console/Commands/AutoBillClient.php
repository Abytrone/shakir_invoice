<?php

namespace App\Console\Commands;

use App\Constants\InvoiceStatus;
use App\Models\Client;
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
            ->where('status', InvoiceStatus::UNPAID)
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
                    $paystackService->chargeAuthorization($invoice->client->auth_email, $res->authorization_code, $invoice->total);

                    $billedCount++;
                }
                //todo: send email notification
            }
        }

        $this->info("$billedCount has been billed...");
    }
}
