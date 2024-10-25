<?php

use App\Console\Commands\SpotifyGetPlaying;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Setting up cron to run schedules:
//
// docker exec -it exnota-tools-local /bin/bash
// apt-get update
// apt-get install cron
// crontab -e
// * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
// service cron start
//
// Note: Sub-second jobs work by Laravel being called every minute, and it then
// keeps the process alive until the next minute. The cron itself is every
// minute. So working with this may have delays until the next minute.
//
// Debugging:
// - Stop in Docker: docker exec -it exnota-tools-local service cron stop
// - Host: ./sail.sh debug schedule:run >> /dev/null 2>&1
//
// locally they suggest to use this instead of installing cron:
// - php artisan schedule:work

// runInBackground ensures fetch-history doesn't block fetch-current
Schedule::command('spotify:fetch-current')->runInBackground()->withoutOverlapping()->everyFiveSeconds();
Schedule::command('spotify:fetch-history')->runInBackground()->withoutOverlapping()->everyThirtySeconds();
// })->everySecond();
