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
            'amount' => $this->faker->randomFloat(),
            'payment_method' => $this->faker->word(),
            'reference_number' => $this->faker->word(),
            'notes' => $this->faker->word(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'invoice_id' => $this->faker->randomNumber(),
        ];
    }
}
