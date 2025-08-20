<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'shipping_address',
        'billing_address',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }
}