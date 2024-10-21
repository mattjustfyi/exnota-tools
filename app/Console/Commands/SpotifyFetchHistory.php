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
        // TODO: Is there an SDK?

        // Starting from oldest, compare to entries in out DB
        // - spotify_artist database with columns
        //   - spotify_id
        //   - notion_id
        //   - user_id
        //   - name
        // - spotify_album database with columns
        //   - spotify_id
        //   - notion_id
        //   - user_id
        //   - name
        //   - image_url
        //   - album_type
        //   - type
        //   - release_date
        //   - spotify_artist (has many)
        // - spotify_track database with columns
        //   - spotify_id
        //   - notion_id
        //   - user_id
        //   - name
        //   - duration_ms
        //   - spotify_artist (has many)
        //   - spotify_album (has one)
        //   - popularity
        // - spotify_play_history database with columns
        //   - played_at
        //   - spotify_track_id


        // For each one that doesn't exist:

        // If Artist is new, create in Notion, save to DB
        // - Database: spotify_dashboard
        //   - album_database_id (notion_object_id)
        //   - artist_database_id (notion_object_id)
        //   - track_database_id (notion_object_id)
        //   - play_history_database_id (notion_object_id)
        //   - dashboard_id (notion_object_id)
        //   - spotify_auth_token

        // If Album is new, create in Notion, save to DB

        // If Song is new, create in Notion, save to DB

        // Create Play History entry in Notion, save record in DB

    }
}
