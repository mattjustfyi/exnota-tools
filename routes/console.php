<?php

use App\Console\Commands\SpotifyGetPlaying;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Notes:
//
// - Sub-second jobs work by Laravel being called every minute, and it then
//   keeps the process alive until the next minute. The cron itself is every
//   minute. So working with this may have delays until the next minute.
//
// - runInBackground ensures fetch-history doesn't block fetch-current
// Schedule::command('spotify:fetch-current')->runInBackground()->withoutOverlapping()->everyFifteenSeconds();
// Schedule::command('spotify:fetch-history')->runInBackground()->withoutOverlapping()->everyMinute();
