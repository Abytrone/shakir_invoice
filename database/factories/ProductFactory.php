<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'type' => $this->faker->randomElement(['product', 'service']),
            'unit_price' => $this->faker->randomFloat(nbMaxDecimals: 2, max: 1000),
            'tax_rate' => $this->faker->randomFloat(nbMaxDecimals: 2, max: 20),
            'discount_rate' => $this->faker->randomFloat(nbMaxDecimals: 2, max: 20),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
