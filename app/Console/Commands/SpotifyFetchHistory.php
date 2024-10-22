<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SpotifyWebAPI\SpotifyWebAPI;

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

        // Later this scheduler will create a job for each user. For now, the
        // user is hardcoded.
        $user = User::findOrFail(43);
        // -----
        // History
        // -----

        // $session = new Session(
        //     env('SPOTIFY_CLIENT_ID'),
        //     env('SPOTIFY_CLIENT_SECRET'),
        //     env('SPOTIFY_REDIRECT_URI')
        // );

        $api = new SpotifyWebAPI();
        $api->setAccessToken(env('SPOTIFY_ACCESS_TOKEN'));




        // Every 1 minute

        // Get recently played from Spotify
        // TODO: Is there an SDK?
        // Get spotify auth id from spotify_dashboard.authData->access_token (for user)

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
        //   - played_at (index)
        //   - spotify_track_id
        //   - user_id

        // For each entry, in reverse order, from spotify...

        $tracks = $api->getMyRecentTracks([
            'limit' => 50,
        ]);

        foreach (array_reverse($tracks->items) as $track) {
            // If 'played_at' from Spotify for the user already exists in
            // spotify_play_history, skip (if that's there, assume the rest is too)
            Log::debug('Track: ' . $track->played_at);
        }


        // Otherwise, check locally if there's a spotify_artist.notion_id entry
        // - If so, skip this step
        // - If not, but there's a spotify_artist.spotify_id entry, create in Notion, save to DB
        // - If not, save to DB and save to Notion
        // Get notion database ID from spotify_dashboard.artist_notion_object_id->data->id

        // Repeat for spotify_album and spotify_track
        // - Get notion database ID from spotify_dashboard.album_notion_object_id->data->id, track_notion_object_id

        // Create Play History entry in Notion, save record in DB
        // - get db id from spotify_dashboard.play_history_notion_object_id
    }
}
