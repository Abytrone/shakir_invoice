<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->word(),
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now(),
            'tax_rate' => $this->faker->randomFloat(2, 1, 3),
            'discount_rate' => $this->faker->randomFloat(2, 1, 3),
            'status' => 'draft',
            'notes' => $this->faker->word(),
            'terms' => $this->faker->word(),
            'is_recurring' => $this->faker->boolean(),
            'recurring_frequency' => $this->faker->word(),
            'next_recurring_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'client_id' => Client::factory(),
        ];
    }
}
