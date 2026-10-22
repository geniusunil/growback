<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:send-scheduled-notifications')->everyMinute();

Schedule::command('accounts:delete-scheduled')
    ->everyMinute();
\Illuminate\Support\Facades\Schedule::command('activities:purge-completed --execute')
    ->dailyAt('02:00')
    ->withoutOverlapping();
