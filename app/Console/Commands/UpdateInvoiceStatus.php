<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class UpdateInvoiceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:update-invoice-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting recurring invoice generation...');
        $invoices = Invoice::query()
            ->get();
        foreach ($invoices as $invoice) {
            if (($invoice->isSent() || $invoice->isOverdue() || $invoice->isDraft()) && $invoice->isPaid()) {
                $invoice->markAsPaid();
                $this->info("Invoice {$invoice->invoice_number} status updated to paid.");
            } elseif ($invoice->due_date < now() && $invoice->status !== 'paid') {
                $invoice->markAsOverdue();
                $this->info("Invoice {$invoice->invoice_number} status updated to overdue.");
            }elseif ($invoice->status === 'overdue' && $invoice->amount_paid >= $invoice->total) {
                $invoice->markAsPaid();
                $this->info("Invoice {$invoice->invoice_number} status updated to paid.");
            }
        }

    }
}
