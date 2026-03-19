<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $method = $this->faker->randomElement(PaymentMethod::cases());

        return [
            'type' => Payment::TYPE_INVOICE,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'payment_method' => $method,
            'payment_source' => $method->requiresSource() ? $this->faker->company() : null,
            'source_number' => $method->requiresSource() ? $this->faker->numerify('##########') : null,
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
