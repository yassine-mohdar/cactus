<?php

return [
    'base_currency' => env('APP_CURRENCY', 'MAD'),
    'multi_currency_enabled' => false,
    'supported_currencies' => ['MAD'],
    'conversion_adjustment_percent' => 0.0,
    'price_rounding_strategy' => 'none',
    'default_tax_rate' => 20.0,
    'default_payment_terms_days' => 0,
];
