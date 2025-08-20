<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_date' => $this->faker->dateTimeThisYear(),
            'status' => $this->faker->randomElement(['Pending', 'Shipped', 'Delivered', 'Cancelled']),
        ];
    }
}