<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
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
            ->get(PaymentResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_payments(): void
    {
        $invoice = Invoice::factory()->create();
        $payment = Payment::factory()->create([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
        ]);

        $this->actingAsAdmin()
            ->get(PaymentResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_render_create_page(): void
    {
        $this->actingAsAdmin()
            ->get(PaymentResource::getUrl('create'))
            ->assertOk();
    }

}
