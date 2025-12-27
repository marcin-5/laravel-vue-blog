<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('newsletter:send daily')
    ->weekdays()
    ->dailyAt('14:14');
Schedule::command('newsletter:send daily')
    ->weekends()
    ->dailyAt('08:08');
Schedule::command('newsletter:send weekly')->weeklyOn(5, '09:09');
