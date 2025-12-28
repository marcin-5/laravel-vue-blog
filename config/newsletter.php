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

    'daily_weekday_time' => env('NEWSLETTER_DAILY_WEEKDAY_TIME', '07:07'),
    'daily_weekend_time' => env('NEWSLETTER_DAILY_WEEKEND_TIME', '11:11'),
    'weekly_day' => env('NEWSLETTER_WEEKLY_DAY', 7), // 5 is Friday
    'weekly_time' => env('NEWSLETTER_WEEKLY_TIME', '19:19'),

];
