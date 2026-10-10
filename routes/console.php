<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled commands go here, e.g.:
//   Schedule::command('your:command')->hourly();
// A real `* * * * * php artisan schedule:run` cron entry is required on
// whatever host this deploys to.
