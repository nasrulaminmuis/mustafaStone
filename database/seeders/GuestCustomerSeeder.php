<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class GuestCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan firstOrCreate untuk menghindari duplikat jika seeder dijalankan lagi
        Customer::firstOrCreate(
            ['customer_id' => 1], // Kondisi pencarian
            [
                // Data yang akan dibuat jika tidak ditemukan
                'first_name' => 'Guest',
                'last_name' => 'User',
                'email' => 'guest@example.com',
                'phone_number' => '0000000000',
                'shipping_address' => 'N/A',
                'billing_address' => 'N/A',
            ]
        );
    }
}