<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Image;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $customers = Customer::all();

        // Create 50 products
        Product::factory(50)->make()->each(function ($product) use ($categories, $customers) {
            // Assign a random category to each product
            $product->category_id = $categories->random()->category_id;
            $product->save();

            // Create 1 to 3 images for each product
            Image::factory(rand(1, 3))->create([
                'product_id' => $product->product_id,
            ]);

            // Create 0 to 5 reviews for each product
            Review::factory(rand(0, 5))->create([
                'product_id' => $product->product_id,
                'customer_id' => $customers->random()->customer_id,
            ]);
        });
    }
}