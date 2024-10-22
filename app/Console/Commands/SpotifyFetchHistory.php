<?php

namespace App\Console\Commands;

use App\Models\SpotifyAlbum;
use App\Models\SpotifyArtist;
use App\Models\SpotifyDashboard;
use App\Models\SpotifyTrack;
use App\Models\User;
use DateTimeImmutable;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Notion\Common\File;
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

        // TODO:
        // Please keep in mind that metadata, cover art and artist images must
        // be accompanied by a link back to the applicable artist, album, track,
        // or playlist on the Spotify Service. You must also attribute content
        // from Spotify with the logo.
        foreach (array_reverse($tracks->items) as $track) {
            // If 'played_at' from Spotify for the user already exists in
            // spotify_play_history, skip (if that's there, assume the rest is too)
            Log::debug('Track: ' . $track->played_at);

            // Save artist
            foreach ($track->track->artists as $spotifyArtist) {
                $this->saveArtist($notion, $dashboard, $spotifyArtist);
            }

            // Save album
            $spotifyAlbum = $track->track->album;
            $spotifyAlbumId = $spotifyAlbum->id;
            Log::debug('Album ID: ' . $spotifyAlbumId);
            foreach ($spotifyAlbum->artists as $spotifyArtist) {
                $this->saveArtist($notion, $dashboard, $spotifyArtist);
            }
            $this->saveAlbum($notion, $dashboard, $spotifyAlbum);

            // Save track
            $spotifyTrack = $track->track;
            $this->saveTrack($notion, $dashboard, $spotifyTrack);
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

    private function saveArtist($notion, $dashboard, $spotifyArtist)
    {
        // TODO: Get image from artist endpoint and save as well
        $spotifyArtistId = $spotifyArtist->id;
        Log::debug('Artist ID: ' . $spotifyArtistId);
        $artist = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $spotifyArtistId)
            ->first();

        if ($artist && $artist->notion_id) {
            Log::debug('Artist exists in DB and Notion');
            return;
        } else if ($artist) {
            Log::debug('Artist exists in DB, but not Notion');

            $this->saveArtistToNotion($notion, $dashboard, $spotifyArtist);
        } else {
            Log::debug('Artist does not exist in DB');
            // Save to DB
            $artist = SpotifyArtist::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyArtistId,
                'name' => $spotifyArtist->name,
            ]);

            // Create in Notion
            $notionId = $this->saveArtistToNotion($notion, $dashboard, $spotifyArtist);
            $artist->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function saveArtistToNotion($notion, $dashboard, $spotifyArtist): string
    {
        $databaseId = $dashboard->artist_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($spotifyArtist->name);
        $spotifyId = \Notion\Pages\Properties\RichTextProperty::fromString($spotifyArtist->id);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId);

        $page = $notion->pages()->create($page);

        return $page->id;
    }

    private function saveAlbum($notion, $dashboard, $spotifyAlbum)
    {
        $spotifyAlbumId = $spotifyAlbum->id;
        Log::debug('Album ID: ' . $spotifyAlbumId);

        $album = SpotifyAlbum::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $spotifyAlbumId)->first();

        if ($album && $album->notion_id) {
            Log::debug('Album exists in DB and Notion');
            return;
        } else if ($album) {
            Log::debug('Album exists in DB, but not Notion');

            $this->saveAlbumToNotion($notion, $dashboard, $album);
        } else {
            Log::debug('Album does not exist in DB');

            $imageUrl = null;
            $maxWidth = 0;
            foreach ($spotifyAlbum->images as $image) {
                if ($image->width > $maxWidth) {
                    $maxWidth = $image->width;
                    $imageUrl = $image->url;
                }
            }

            // Save to DB
            $album = SpotifyAlbum::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyAlbumId,
                'name' => $spotifyAlbum->name,
                'image_url' => $imageUrl,
                'album_type' => $spotifyAlbum->album_type,
                'type' => $spotifyAlbum->type,
                'release_date' => $spotifyAlbum->release_date,
                'spotify_artist_ids' => array_map(fn($a) => $a->id, $spotifyAlbum->artists),
            ]);

            // Create in Notion
            $notionId = $this->saveAlbumToNotion($notion, $dashboard, $album);
            $album->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function saveAlbumToNotion($notion, $dashboard, $album): string
    {
        $databaseId = $dashboard->album_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($album->name);
        $spotifyId = \Notion\Pages\Properties\RichTextProperty::fromString($album->spotify_id);
        $albumType = \Notion\Pages\Properties\RichTextProperty::fromString($album->album_type);
        $releaseDate  = \Notion\Pages\Properties\Date::create(new DateTimeImmutable($album->release_date));
        $imageFile = File::createExternal($album->image_url);
        $imageFiles = \Notion\Pages\Properties\Files::create($imageFile);

        $artists = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->whereIn('spotify_id', $album->spotify_artist_ids)
            ->get();
        $artistNotionIDs = $artists->map(fn($a) => $a->notion_id)->toArray();
        $artistRelation = \Notion\Pages\Properties\Relation::create(...$artistNotionIDs);

        // TODO: Update to add as cover image

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Type", $albumType)
            ->addProperty("Release Date", $releaseDate)
            ->addProperty("Image", $imageFiles)
            ->addProperty("Artists", $artistRelation)
            ->changeCover($imageFile);

        $page = $notion->pages()->create($page);

        // TODO: Add relation to Artists

        return $page->id;
    }

    private function saveTrack($notion, $dashboard, $spotifyTrack)
    {
        $spotifyTrackId = $spotifyTrack->id;
        Log::debug('Track ID: ' . $spotifyTrackId);

        $track = SpotifyTrack::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $spotifyTrackId)->first();

        if ($track && $track->notion_id) {
            Log::debug('Track exists in DB and Notion');
            return;
        } else if ($track) {
            Log::debug('Track exists in DB, but not Notion');

            $this->saveTrackToNotion($notion, $dashboard, $track);
        } else {
            Log::debug('Track does not exist in DB');

            // Save to DB
            $track = SpotifyTrack::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyTrackId,
                'name' => $spotifyTrack->name,
                'duration_ms' => $spotifyTrack->duration_ms,
                'spotify_artist_ids' => array_map(fn($a) => $a->id, $spotifyTrack->artists),
                'spotify_album_id' => $spotifyTrack->album->id,
                'popularity' => $spotifyTrack->popularity,
            ]);

            // Create in Notion
            $notionId = $this->saveTrackToNotion($notion, $dashboard, $track);
            $track->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function saveTrackToNotion($notion, $dashboard, $track): string
    {
        $databaseId = $dashboard->track_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($track->name);
        $spotifyId = \Notion\Pages\Properties\RichTextProperty::fromString($track->spotify_id);
        $durationMs = \Notion\Pages\Properties\Number::create($track->duration_ms);
        $popularity = \Notion\Pages\Properties\Number::create($track->popularity);

        $artists = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->whereIn('spotify_id', $track->spotify_artist_ids)
            ->get();
        $artistNotionIDs = $artists->map(fn($a) => $a->notion_id)->toArray();
        $artistRelation = \Notion\Pages\Properties\Relation::create(...$artistNotionIDs);

        $album = SpotifyAlbum::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $track->spotify_album_id)
            ->first();
        $albumNotionID = $album->notion_id;
        $albumRelation = \Notion\Pages\Properties\Relation::create($albumNotionID);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Duration", $durationMs)
            ->addProperty("Popularity", $popularity)
            ->addProperty("Artists", $artistRelation)
            ->addProperty("Album", $albumRelation);

        $page = $notion->pages()->create($page);

        return $page->id;
    }
}
