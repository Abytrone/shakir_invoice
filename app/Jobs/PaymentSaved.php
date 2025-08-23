<?php

namespace App\Jobs;

use App\Mail\InvoicePaid;
use App\Models\AuthPayment;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class PaymentSaved implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(array $data): void
    {
        info('Handling payment saved job', [$data]);
        $metaData = $data['metadata']['custom_fields'];
        info('meta data', [$metaData]);
        $meta = collect($metaData);

        if ($meta->contains('variable_name', 'auth_email')) {
            Client::query()
                ->where('auth_email',
                    $meta->firstWhere('variable_name', 'auth_email')['value'])
                ->update(['auth_res' => json_encode($data['authorization'])]);
            AuthPayment::query()
                ->create([
                    'auth_email' => $meta->firstWhere('variable_name', 'auth_email')['value'],
                    'reference' => $data['reference'],
                    'amount' => $data['amount'] / 100,
                ]);
            info('updated client auth res');
        }


        $invoiceNumber = $meta->firstWhere('variable_name', 'invoice_number')['value'];
        $amount = $data['amount'] / 100;
        $channel = $data['channel'];
        $reference = $data['reference'];

        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            info('Invoice not found. Please check the invoice number and try again.');
            return;
        }

        $invoice->payments()
            ->firstOrCreate(
                ['reference_number' => $reference], [
                'amount' => $amount,
                'notes' => '...',
                'payment_method' => $channel,
            ]);

        if ($invoice->isPaid()) {
            $invoice->update(['status' => 'paid']);
        }

        if ($invoice->isPartial()) {
            $invoice->update(['status' => 'partial']);
        }

        if ($invoice->client->hasEmail()) {
            Mail::to($invoice->client->email)->send(new InvoicePaid($invoice, $amount));
        }
        info('Invoice payment has been processed successfully.');
    }
}
