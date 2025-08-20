<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add nullable() to the new required columns
            $table->string('order_code')->unique()->nullable()->after('order_id');
            $table->string('buyer_name')->nullable()->after('customer_id');
            $table->string('buyer_phone')->nullable()->after('buyer_name');
            $table->string('payment_proof')->nullable()->after('status');

            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the columns if migration is rolled back
            $table->dropColumn(['order_code', 'buyer_name', 'buyer_phone', 'payment_proof']);

            // Revert customer_id to its original state
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};