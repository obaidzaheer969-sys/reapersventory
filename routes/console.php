<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:expire')->hourly();
Schedule::command('subscriptions:notify-expiring')->dailyAt('09:00');
Schedule::command('inventory:notify-low-stock')->dailyAt('08:00');
