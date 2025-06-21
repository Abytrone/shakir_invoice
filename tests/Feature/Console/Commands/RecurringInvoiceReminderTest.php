<?php

namespace Console\Commands;

use App\Mail\InvoiceReminderSent;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecurringInvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow(); // Reset time
    }

    public function test_it_sends_reminders_for_invoices_due_soon()
    {
        // Freeze time for predictable tests
        $today = Carbon::create(2023);
        Carbon::setTestNow($today);

        Mail::fake();

        // Create test client
        $client = Client::factory()->create(['email' => 'test@example.com']);

        // Create invoices with due dates we want to test
        $dueDates = [
            $today->copy()->addDays(15),
            $today->copy()->addDays(10),
            $today->copy()->addDays(5),
            $today->copy(),
        ];

        $invoices = [];
        foreach ($dueDates as $dueDate) {
            $invoices[] = Invoice::factory()
                ->for($client)
                ->has(InvoiceItem::factory()->count(3), 'items')
                ->create(['due_date' => $dueDate]);
        }

        // Create some invoices that shouldn't trigger reminders
        Invoice::factory()
            ->for($client)
            ->create(['due_date' => $today->copy()->addDays(20)]); // Too far in the future
        Invoice::factory()
            ->for($client)
            ->create(['due_date' => $today->copy()->subDay()]); // Already past due

        // Execute the command 2x to make sure that invoices are sent just once on the reminder date
        $this->artisan('invoice:recurring-invoice-reminder')->assertExitCode(0);
        $this->artisan('invoice:recurring-invoice-reminder')->assertExitCode(0);

        // Assert emails were sent for the correct invoices
        foreach ($invoices as $invoice) {
            Mail::assertQueued(InvoiceReminderSent::class, function ($mail) use ($invoice) {
                return $mail->hasTo($invoice->client->email) &&
                    $mail->invoice->id === $invoice->id;
            });
        }

        // Assert total emails sent
        Mail::assertQueuedCount(count($dueDates));
    }

    public function test_it_does_not_send_emails_when_no_invoices_due_soon()
    {
        Mail::fake();

        // Create invoices with due dates outside our range
        Invoice::factory()
            ->for(Client::factory())
            ->create(['due_date' => now()->addDays(20)]);
        Invoice::factory()
            ->for(Client::factory())
            ->create(['due_date' => now()->subDays(1)]);

        $this->artisan('invoice:recurring-invoice-reminder')->assertExitCode(0);

        Mail::assertNothingQueued();
    }
}
