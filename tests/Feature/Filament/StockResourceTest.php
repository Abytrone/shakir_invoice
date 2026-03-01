<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\StockResource;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class StockResourceTest extends TestCase
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
            ->get(StockResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_stocks(): void
    {
        $stocks = Stock::factory()->count(2)->create();

        $this->actingAsAdmin()
            ->get(StockResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText((string) $stocks->first()->quantity);
    }

}
