<?php

namespace App\Console\Commands;

use App\Mail\InvoiceSent;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                ->where(function ($query) {
                    $query->whereNull('recurring_end_date')
                        ->orWhere('recurring_end_date', '>=', now());
                })
                ->where(function ($query) {
                    // And meets the frequency conditions
                    $query->where(function ($subQuery) {
                        $subQuery->where('recurring_frequency', 'monthly')
                            ->where('next_recurring_date', '<=', now()->addDays(7)); // 7-day notice
                    })
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('recurring_frequency', 'yearly')
                                ->where('next_recurring_date', '<=', now()->addDays(20)); // 20-day notice
                        });
                })
                ->get();
//            info($recurringInvoices);
            //todo: if necessary add condition to get is the recurring is stopped
//            dd();
            foreach ($recurringInvoices as $invoice) {
                $this->generateNextInvoice($invoice);
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
            'yearly' => $date->addYears(),
            default => $date->addMonths(),
        };
    }

    protected function generateNextInvoice(Invoice $originalInvoice): void
    {
        // Create new invoice
        $newInvoice = $originalInvoice->replicate();
        $newInvoice->issue_date = today();
        $newInvoice->status = 'draft';
        $newInvoice->created_at = now();
        $newInvoice->updated_at = now();

        $newInvoice->due_date = match ($newInvoice->invoice_frequency) {
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

        Mail::to($newInvoice->client->email)
            ->queue(new InvoiceSent($newInvoice));

        $this->info("Generated new invoice {$newInvoice->invoice_number} for {$originalInvoice->invoice_number}");
    }
}
