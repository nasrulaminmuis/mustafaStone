<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        // In a real app, you'd fetch the actual product price
        $price = $this->faker->randomFloat(2, 10, 200);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'subtotal' => $quantity * $price,
        ];
    }
}