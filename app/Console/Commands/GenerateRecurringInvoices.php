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

    public function handle(): void
    {
        $this->info('Starting recurring invoice generation...');

        try {
            DB::beginTransaction();

            $recurringInvoices = Invoice::query()
                ->where('is_recurring', true)
                ->where('status', 'paid')
                ->where('has_next', false)
                ->where('next_recurring_date', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('recurring_end_date')
                        ->orWhere('recurring_end_date', '>=', now());
                })
                ->get();
            //todo: if necessary add condition to get is the recurring is stopped

            foreach ($recurringInvoices as $invoice) {
                $lastGeneratedDate = $invoice->next_recurring_date;
                $nextDate = $this->calculateNextDate($lastGeneratedDate, $invoice->recurring_frequency);
                $this->generateNextInvoice($invoice, $nextDate);
            }

            DB::commit();
            $this->info('Recurring invoice generation completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate recurring invoices: ' . $e->getMessage());
            $this->error('Failed to generate recurring invoices: ' . $e->getMessage());
        }
    }


    public function calculateNextDate(string $lastDate, string $frequency): Carbon
    {
        $date = Carbon::parse($lastDate);
        return match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'quarterly' => $date->addQuarter(),
            'yearly' => $date->addYears(),
            default => $date->addMonths(),
        };
    }

    protected function generateNextInvoice(Invoice $originalInvoice, Carbon $nextDate): void
    {
        // Create new invoice
        $newInvoice = $originalInvoice->replicate();
        $newInvoice->issue_date = today();
        $newInvoice->status = 'draft';
        $newInvoice->created_at = now();
        $newInvoice->updated_at = now();

        $newInvoice->due_date = match ($newInvoice->invoice_frequency) {
            'daily' => $newInvoice->due_date->addDay(),
            'weekly' => $newInvoice->due_date->addWeek(),
            'quarterly' => $newInvoice->due_date->addQuarter(),
            'yearly' => $newInvoice->due_date->addYears(),
            default => $newInvoice->due_date->addMonths(),
        };

        // Generate new invoice number
        $latestInvoice = Invoice::withTrashed()->latest()->first();
        $nextNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, 3)) + 1 : 1;
        $newInvoice->invoice_number = 'REC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $newInvoice->save();

        // Copy invoice items
        foreach ($originalInvoice->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newInvoice->id;
            $newItem->created_at = now();
            $newItem->updated_at = now();
            $newItem->save();
        }

        $originalInvoice->update(['has_next' => true]);


        $this->info("Generated new invoice {$newInvoice->invoice_number} for {$originalInvoice->invoice_number}");
    }
}
