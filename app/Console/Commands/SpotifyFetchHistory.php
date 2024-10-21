<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SpotifyFetchHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spotify:fetch-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches history from Spotify and updates users Notion.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::debug('Fetch history!');

        // -----
        // History
        // -----

        // Every 1 minute

        // Get recently played from Spotify

        // Starting from oldest, compare to entries in out DB

        // For each one that doesn't exist:

        // If Artist is new, create in Notion, save to DB

        // If Album is new, create in Notion, save to DB

        // If Song is new, create in Notion, save to DB

        // Create Play History entry in Notion, save record in DB

        // -----
        // Active
        // -----

        // Every 5 seconds

        // Ping spotify API for current status

        // If currently_playing_type is not 'track' or is_playing is false
        // - if our local status is not_playing, do nothing
        // - if our local status is playing, update to not_playing and
        //   - Update dashboard title, icon, cover image

        // If local status is not_playing, update to playing with track ID
        // - Update dashboard title, icon, cover image

        // If local status is playing, compare track ID
        // - If same, do nothing
        // - If different, update to playing with new track ID and
        //   - Update dashboard title, icon, cover image

    }
}
