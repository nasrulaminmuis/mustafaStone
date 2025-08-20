<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('laravel', function () {
    return view('laravel');
})->name('laravel');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('admin', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('admin/product', 'admin.product')->name('admin.product');
    Volt::route('admin/sale', 'admin.sale')->name('admin.sale');
    Volt::route('admin/report', 'admin.report')->name('admin.report');
});

Route::redirect('/', 'user/landing');

Volt::route('user/landing', 'user.landing')->name('user.landing');
Volt::route('user/product', 'user.product')->name('user.product');
Volt::route('user/productdetail/{product}', 'user.productdetail')->name('user.productdetail');
Volt::route('user/bucket', 'user.bucket')->name('user.bucket');
Volt::route('user/form', 'user.form')->name('user.form');
Volt::route('user/profile', 'user.profile')->name('user.profile');

require __DIR__.'/auth.php';
