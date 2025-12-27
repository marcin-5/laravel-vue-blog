<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Newsletter Schedule
    |--------------------------------------------------------------------------
    |
    | Here you may configure the schedule for the newsletter commands.
    |
    */

    'daily_weekday_time' => env('NEWSLETTER_DAILY_WEEKDAY_TIME', '14:14'),
    'daily_weekend_time' => env('NEWSLETTER_DAILY_WEEKEND_TIME', '08:08'),
    'weekly_day' => env('NEWSLETTER_WEEKLY_DAY', 5), // 5 is Friday
    'weekly_time' => env('NEWSLETTER_WEEKLY_TIME', '09:09'),

];
