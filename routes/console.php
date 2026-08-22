<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:sync-gym-alerts')->dailyAt('06:00');
Schedule::command('access:expire')->dailyAt('01:00');
Schedule::command('zkteco:clear-commands')
    ->dailyAt('02:30')
    ->withoutOverlapping();
Schedule::command('access:restriction:dispatch')->everyMinute()->withoutOverlapping();
