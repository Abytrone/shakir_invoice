<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'quantity' => 1,
            'unit_price' => $this->faker->randomFloat(2, 1, 100),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
        ];
    }
}
