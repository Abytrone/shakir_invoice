<?php

namespace Filament;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_list_page_hides_create_button_without_create_invoice_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user);

        Livewire::test(ListInvoices::class)
            ->assertActionHidden('create');
    }

    public function test_list_page_hides_edit_action_without_update_invoice_permission(): void
    {
        $invoice = Invoice::factory()->create();

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user);

        Livewire::test(ListInvoices::class)
            ->assertTableActionHidden('edit', $invoice);
    }

    public function test_list_page_hides_delete_action_without_delete_invoice_permission(): void
    {
        $invoice = Invoice::factory()->create();

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user);

        Livewire::test(ListInvoices::class)
            ->assertTableActionHidden('delete', $invoice);
    }
}
