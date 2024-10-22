<?php

namespace App\Console\Commands;

use App\Models\SpotifyArtist;
use App\Models\SpotifyDashboard;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Notion\Notion;
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
        $dashboard = SpotifyDashboard::first();
        $workspace = $dashboard->workspace;
        // -----
        // History
        // -----

        // $session = new Session(
        //     env('SPOTIFY_CLIENT_ID'),
        //     env('SPOTIFY_CLIENT_SECRET'),
        //     env('SPOTIFY_REDIRECT_URI')
        // );

        $api = new SpotifyWebAPI();
        $api->setAccessToken($dashboard->access_token);




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

        try {
            $tracks = $api->getMyRecentTracks([
                // TODO: Update to 50
                'limit' => 5,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching recent tracks: ' . $e->getMessage());
            throw $e;
        }

        $notion = Notion::create($workspace->authData['access_token']);

        foreach (array_reverse($tracks->items) as $track) {
            // If 'played_at' from Spotify for the user already exists in
            // spotify_play_history, skip (if that's there, assume the rest is too)
            Log::debug('Track: ' . $track->played_at);

            // Add artist
            foreach ($track->track->artists as $spotifyArtist) {
                $spotifyArtistId = $spotifyArtist->id;
                Log::debug('Artist ID: ' . $spotifyArtistId);
                $artist = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
                    ->where('spotify_id', $spotifyArtistId)
                    ->first();

                if ($artist && $artist->notion_id) {
                    Log::debug('Artist exists in DB and Notion');
                    continue;
                } else if ($artist) {
                    Log::debug('Artist exists in DB, but not Notion');

                    $this->createArtistInNotion($notion, $dashboard, $spotifyArtist);
                } else {
                    Log::debug('Artist does not exist in DB');
                    // Save to DB
                    $artist = SpotifyArtist::create([
                        'spotify_dashboard_id' => $dashboard->id,
                        'spotify_id' => $spotifyArtistId,
                        'name' => $spotifyArtist->name,
                    ]);

                    // Create in Notion
                    $notionId = $this->createArtistInNotion($notion, $dashboard, $spotifyArtist);
                    $artist->update([
                        'notion_id' => $notionId,
                    ]);
                }
            }
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

    private function createArtistInNotion($notion, $dashboard, $spotifyArtist): string
    {
        $databaseId = $dashboard->artist_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($spotifyArtist->name);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title);

        $page = $notion->pages()->create($page);

        return $page->id;
    }
}
