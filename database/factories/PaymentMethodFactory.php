<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'card_number' => $this->faker->creditCardNumber(),
            'expiration_date' => $this->faker->creditCardExpirationDateString(),
            'cvv' => $this->faker->numerify('###'),
        ];
    }
}