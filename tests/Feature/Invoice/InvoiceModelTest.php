<?php

namespace Invoice;

use App\Constants\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_marked_overdue_properly(): void
    {
        $invoice1 = Invoice::factory()->create([
            'due_date' => now()->subDays(5),
            'status' => InvoiceStatus::SENT,
        ]);

        $invoice2 = Invoice::factory()->create([
            'due_date' => now()->addDays(5),
            'status' => InvoiceStatus::SENT,
        ]);

        $this->assertTrue($invoice1->isOverdue());
        $this->assertTrue(!$invoice2->isOverdue());
        $this->assertTrue($invoice2->isSent());

    }

    public function test_invoice_is_marked_sent_properly(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->markAsSent();

        $this->assertTrue($invoice->isSent());

    }

    public function test_invoice_is_marked_as_draft_properly(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::SENT,
        ]);

        $invoice->markAsDraft();

        $this->assertTrue($invoice->isDraft());

    }
    public function test_invoice_is_marked_as_cancelled_properly(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::SENT,
        ]);

        $invoice->markAsCancelled();

        $this->assertTrue($invoice->isCancelled());

    }
}
