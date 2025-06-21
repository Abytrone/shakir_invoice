<?php

namespace App\Console\Commands;

use App\Mail\InvoiceReminderSent;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RecurringInvoiceReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:recurring-invoice-reminder';

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
            ->with(['client', 'items'])
            ->whereIn('due_date', [
                today()->addDays(15),
                today()->addDays(10),
                today()->addDays(5),
                today()])
            ->where(function ($query) {
                $query->where('due_reminder_date', '!=', today())
                    ->orWhereNull('due_reminder_date');
            })
            ->get();

        foreach ($invoices as $invoice) {
            $daysBeforeDueDate = $invoice->due_date->diffInDays(today());
            $invoice->update(['due_reminder_date' => now()]);
            Mail::to($invoice->client->email)
                ->send(new InvoiceReminderSent($invoice, $daysBeforeDueDate));

        }
    }
}
