<?php

use Illuminate\Support\Facades\Route;

Route::get('/checkout/payment-methods', \App\Modules\Checkout\Http\Controllers\CheckoutPaymentMethodController::class)
    ->name('api.checkout.payment-methods');
