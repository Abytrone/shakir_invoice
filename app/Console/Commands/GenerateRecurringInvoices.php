<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Generate recurring invoices based on their frequency and schedule';

    public function handle()
    {
        $this->info('Starting recurring invoice generation...');

        try {
            DB::beginTransaction();

            $recurringInvoices = Invoice::where('is_recurring', true)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) {
                    $query->whereNull('recurring_end_date')
                        ->orWhere('recurring_end_date', '>', now());
                })
                ->get();

            foreach ($recurringInvoices as $invoice) {
                $this->processRecurringInvoice($invoice);
            }

            DB::commit();
            $this->info('Recurring invoice generation completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate recurring invoices: ' . $e->getMessage());
            $this->error('Failed to generate recurring invoices: ' . $e->getMessage());
        }
    }

    protected function processRecurringInvoice(Invoice $invoice): void
    {
        $lastGeneratedDate = $invoice->recurring_start_date;
        $nextDate = $this->calculateNextDate($lastGeneratedDate, $invoice->frequency, $invoice->interval);

        if ($nextDate <= now() && $nextDate <= ($invoice->recurring_end_date ?? now()->addYears(100))) {
            $this->generateNextInvoice($invoice, $nextDate);
        }
    }

    protected function calculateNextDate(Carbon $lastDate, string $frequency, int $interval): Carbon
    {
        return match ($frequency) {
            'daily' => $lastDate->addDays($interval),
            'weekly' => $lastDate->addWeeks($interval),
            'monthly' => $lastDate->addMonths($interval),
            'quarterly' => $lastDate->addMonths($interval * 3),
            'yearly' => $lastDate->addYears($interval),
            default => $lastDate,
        };
    }

    protected function generateNextInvoice(Invoice $originalInvoice, Carbon $nextDate): void
    {
        // Create new invoice
        $newInvoice = $originalInvoice->replicate();
        $newInvoice->issue_date = $nextDate;
        $newInvoice->due_date = $nextDate->copy()->addDays(30); // Default 30 days due date
        $newInvoice->status = 'draft';
        $newInvoice->amount_paid = 0;
        $newInvoice->balance = $newInvoice->total;
        $newInvoice->created_at = now();
        $newInvoice->updated_at = now();

        // Generate new invoice number
        $prefix = $originalInvoice->recurring_invoice_number_prefix ?? 'REC-';
        $latestInvoice = Invoice::withTrashed()->latest()->first();
        $nextNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, 3)) + 1 : 1;
        $newInvoice->invoice_number = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $newInvoice->save();

        // Copy invoice items
        foreach ($originalInvoice->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newInvoice->id;
            $newItem->created_at = now();
            $newItem->updated_at = now();
            $newItem->save();
        }

        $this->info("Generated new invoice {$newInvoice->invoice_number} for {$originalInvoice->invoice_number}");
    }
}
