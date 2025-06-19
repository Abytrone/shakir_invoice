<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\GenerateRecurringInvoices;
use App\Constants\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateRecurringInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow('2023-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_recurring_invoices_for_eligible_invoices()
    {
        // Create a paid recurring invoice ready for regeneration
        $originalInvoice = Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'next_recurring_date' => now(),
                'recurring_frequency' => 'monthly',
            ]);

        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $originalInvoice->id,
        ]);

        // Run the command
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        // Assert new invoice was created
        $newInvoice = Invoice::query()
            ->where('id', '!=', $originalInvoice->id)
            ->first();
        $this->assertNotNull($newInvoice);
        $this->assertEquals('draft', $newInvoice->status);
        $this->assertTrue($newInvoice->is_recurring);
        $this->assertEquals(now()->toDateString(), $newInvoice->issue_date->toDateString());

        // Assert items were copied
        $this->assertCount(2, $newInvoice->items);

        // Assert original invoice was updated
        $this->assertTrue($originalInvoice->fresh()->has_next);
    }

    #[Test]
    public function it_does_not_generate_invoices_for_non_recurring_invoices()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => false,
                'status' => 'paid',
                'next_recurring_date' => now(),
            ]);

        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(1, Invoice::count());
    }

    #[Test] public function it_does_not_generate_invoices_for_unpaid_invoices()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => InvoiceStatus::UNPAID,
                'next_recurring_date' => now(),
            ]);

        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(1, Invoice::count());
    }

    #[Test] public function it_does_not_generate_invoices_for_invoices_that_already_have_next()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => true,
                'next_recurring_date' => now(),
            ]);

        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(1, Invoice::count());
    }

    #[Test]
    public function it_does_generate_invoices_for_overdue_but_paid()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'recurring_frequency' => 'monthly',
                'due_date' => now()->subDays(20),
            ]);
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(2, Invoice::count());
    }

    #[Test]
    public function it_generates_invoices_that_are_monthly_with_7_days_window()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'recurring_frequency' => 'monthly',
                'due_date' => now()->addDays(rand(0, 7)),
            ]);
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(2, Invoice::count());
    }

    #[Test]
    public function it_does_not_generate_monthly_invoice_with_window_greater_than_7_days()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'recurring_frequency' => 'monthly',
                'due_date' => now()->addDays(rand(8, 30)),
            ]);
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(1, Invoice::count());
    }

    #[Test]
    public function it_generates_invoices_that_are_yearly_with_20_days_window()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'recurring_frequency' => 'yearly',
                'due_date' => now()->addDays(rand(0,20)),
            ]);
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(2, Invoice::count());
    }

    #[Test]
    public function it_does_not_generate_yearly_invoice_with_window_greater_than_20_days()
    {
        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'recurring_frequency' => 'yearly',
                'due_date' => now()->addDays(rand(21, 100)),
            ]);
        $this->artisan(GenerateRecurringInvoices::class)
            ->assertExitCode(0);

        $this->assertEquals(1, Invoice::count());
    }


    #[Test] public function it_generates_correct_invoice_numbers()
    {
        // Create existing invoice to a test sequence
        Invoice::factory()->create();

        Invoice::factory()
            ->create([
                'is_recurring' => true,
                'status' => 'paid',
                'has_next' => false,
                'next_recurring_date' => now(),
            ]);

        $this->artisan(GenerateRecurringInvoices::class);

        $newInvoice = Invoice::orderBy('id', 'desc')->first();
        $this->assertEquals('INV000002', $newInvoice->invoice_number);
    }

    #[Test] public function it_does_not_generate_invoices_when_recurring_is_stopped()
    {
        $invoice = Invoice::factory()->create([
            'is_recurring' => true,
            'status' => 'paid',
            'recurring_end_date' => now()->subDay(), // Ended yesterday
            'due_date' => now()
        ]);

        $this->artisan(GenerateRecurringInvoices::class);

        $this->assertEquals(1, Invoice::count());
        $this->assertFalse($invoice->fresh()->has_next);
    }
}
