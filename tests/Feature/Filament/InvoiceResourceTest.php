<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class InvoiceResourceTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpActsAsAdmin();
    }

    public function test_can_render_list_page(): void
    {
        $this->actingAsAdmin()
            ->get(InvoiceResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_invoices(): void
    {
        $invoices = Invoice::factory()->count(2)->create();

        $this->actingAsAdmin()
            ->get(InvoiceResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText($invoices->first()->invoice_number);
    }

    public function test_can_render_create_page(): void
    {
        $this->actingAsAdmin()
            ->get(InvoiceResource::getUrl('create'))
            ->assertOk();
    }

    public function test_can_render_edit_page(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAsAdmin()
            ->get(InvoiceResource::getUrl('edit', ['record' => $invoice]))
            ->assertOk();
    }
}
