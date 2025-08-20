<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        // Create 30 orders
        Order::factory(30)->make()->each(function ($order) use ($customers, $products) {
            $order->customer_id = $customers->random()->customer_id;
            $order->save();

            // Create 1 to 5 order items for each order
            $orderProducts = $products->random(rand(1, 5));
            foreach ($orderProducts as $product) {
                $quantity = rand(1, 3);
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ]);
            }
        });
    }
}