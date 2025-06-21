<?php

namespace Tests\Feature\Models;

use App\Constants\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
        $this->assertTrue(! $invoice2->isOverdue());

    }
}
