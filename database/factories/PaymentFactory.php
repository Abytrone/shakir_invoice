<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'type' => Payment::TYPE_INVOICE,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'card', 'mobile_money']),
            'reference_number' => $this->faker->optional()->uuid(),
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'completed',
            'payable_type' => \App\Models\Invoice::class,
            'payable_id' => \App\Models\Invoice::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
