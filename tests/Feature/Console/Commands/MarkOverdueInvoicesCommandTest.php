<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Invoice;
use Illuminate\Support\Facades\Date;
use Carbon\Carbon;

class MarkOverdueInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_marks_overdue_invoices()
    {
        // Freeze time for predictable tests
        $today = Carbon::create(2023, 1, 15);
        Date::setTestNow($today);

        // Create overdue invoices (before today)
        $overdueInvoices = Invoice::factory()
            ->count(3)
            ->create(['due_date' => $today->copy()->subDay()]);

        // Create non-overdue invoices (today or future)
        $currentInvoices = Invoice::factory()
            ->count(2)
            ->create(['due_date' => $today->copy()]);
        $futureInvoices = Invoice::factory()
            ->count(2)
            ->create(['due_date' => $today->copy()->addDays()]);

        // Execute the command
        $this->artisan('invoice:update-invoice-over-due-status')
            ->assertExitCode(0);

        // Assert only overdue invoices were marked
        foreach ($overdueInvoices as $invoice) {
            $this->assertTrue($invoice->fresh()->isOverdue());
        }

        foreach ([...$currentInvoices, ...$futureInvoices] as $invoice) {
            $this->assertFalse($invoice->fresh()->isOverdue());
        }
    }

    public function test_it_handles_no_overdue_invoices()
    {
        // Create only current/future invoices
        Invoice::factory()
            ->count(2)
            ->create(['due_date' => now()]);
        Invoice::factory()
            ->count(2)
            ->create(['due_date' => now()->addDays(1)]);

        $this->artisan('invoice:update-invoice-over-due-status')
            ->assertExitCode(0);
    }



    protected function tearDown(): void
    {
        parent::tearDown();
        Date::setTestNow(); // Reset time
    }
}
