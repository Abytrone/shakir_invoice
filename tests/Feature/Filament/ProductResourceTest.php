<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class ProductResourceTest extends TestCase
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
            ->get(ProductResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_products(): void
    {
        $products = Product::factory()->count(3)->create();

        $this->actingAsAdmin()
            ->get(ProductResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText($products->first()->name);
    }

    public function test_can_render_create_page(): void
    {
        $this->actingAsAdmin()
            ->get(ProductResource::getUrl('create'))
            ->assertOk();
    }

    public function test_can_render_edit_page(): void
    {
        $product = Product::factory()->create();

        $this->actingAsAdmin()
            ->get(ProductResource::getUrl('edit', ['record' => $product]))
            ->assertOk();
    }
}
