<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('payment_method_id');
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->string('card_number');
            $table->string('expiration_date');
            $table->string('cvv');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};