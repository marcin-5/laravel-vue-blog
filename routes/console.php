<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('newsletter:send daily')
    ->weekdays()
    ->dailyAt(config('newsletter.daily_weekday_time', '14:14'));
Schedule::command('newsletter:send daily')
    ->weekends()
    ->dailyAt(config('newsletter.daily_weekend_time', '08:08'));
Schedule::command('newsletter:send weekly')->weeklyOn(
    config('newsletter.weekly_day', 5),
    config('newsletter.weekly_time', '09:09'),
);
