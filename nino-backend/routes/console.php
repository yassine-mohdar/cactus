<?php

use App\Modules\Shipping\Services\SenditService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(SenditService::class)->syncActiveShipments();
})
    ->name('shipping:sendit-sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
