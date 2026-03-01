<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SaleResource;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class SaleResourceTest extends TestCase
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
            ->get(SaleResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_sales(): void
    {
        $sales = Sale::factory()->count(2)->create();

        $this->actingAsAdmin()
            ->get(SaleResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText($sales->first()->reference);
    }

    public function test_can_render_create_page(): void
    {
        Client::factory()->create();
        $this->actingAsAdmin()
            ->get(SaleResource::getUrl('create'))
            ->assertOk();
    }

    public function test_can_render_edit_page(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAsAdmin()
            ->get(SaleResource::getUrl('edit', ['record' => $sale]))
            ->assertOk();
    }
}
