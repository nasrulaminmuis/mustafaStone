<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
            'timestamp' => $this->faker->dateTimeThisYear(),
        ];
    }
}