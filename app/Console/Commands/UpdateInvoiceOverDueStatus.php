<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class UpdateInvoiceOverDueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:update-invoice-over-due-status';

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
        $invoices = Invoice::query()
            ->where('due_date', '<', now())
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->markAsOverDue();
        }
    }
}
