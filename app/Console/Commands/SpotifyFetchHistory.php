<?php

namespace App\Console\Commands;

use App\Helpers\SpotifyHelper;
use App\Models\SpotifyAlbum;
use App\Models\SpotifyArtist;
use App\Models\SpotifyDashboard;
use App\Models\SpotifyPlayHistory;
use App\Models\SpotifyTrack;
use App\Models\User;
use DateTimeImmutable;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
        $dashboard = SpotifyDashboard::where('active', true)->first();
        if (!$dashboard) {
            Log::debug('No active dashboards found.');
            return;
        }

        $api = SpotifyHelper::getAPIForDashboard($dashboard);

        try {
            $tracks = $api->getMyRecentTracks([
                // TODO: Update to 50
                'limit' => 50,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching recent tracks: ' . $e->getMessage());
            throw $e;
        }

        $workspace = $dashboard->workspace;
        $notion = Notion::create($workspace->authData['access_token']);

        // TODO: Optimization: Make sure we loop through play history in reverse
        // chronological order and when we reach one that's already in the
        // database AND has been exported to Notion, then stop.

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
                $this->saveArtist($api, $notion, $dashboard, $spotifyArtist->id);
            }

            // Save album
            $spotifyAlbum = $track->track->album;
            $spotifyAlbumId = $spotifyAlbum->id;
            Log::debug('Album ID: ' . $spotifyAlbumId);
            foreach ($spotifyAlbum->artists as $spotifyArtist) {
                $this->saveArtist($api, $notion, $dashboard, $spotifyArtist->id);
            }
            $this->saveAlbum($api, $notion, $dashboard, $spotifyAlbum->id);

            // Save track
            $spotifyTrack = $track->track;
            $this->saveTrack($notion, $dashboard, $spotifyTrack);

            // Save play history
            $this->savePlayHistory($notion, $dashboard, $track);
        }

        if (count($tracks->items) > 1) {
            $this->updateLast5PlayedInNotion($notion, $dashboard);
        }
    }

    private function saveArtist(SpotifyWebAPI $api, $notion, $dashboard, string $spotifyArtistId)
    {
        Log::debug('Artist ID: ' . $spotifyArtistId);
        $artist = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $spotifyArtistId)
            ->first();

        if ($artist && $artist->notion_id) {
            Log::debug('Artist exists in DB and Notion');
            return;
        } else if ($artist) {
            Log::debug('Artist exists in DB, but not Notion');

            $notionId = $this->saveArtistToNotion($notion, $dashboard, $artist);
            $artist->update([
                'notion_id' => $notionId,
            ]);
        } else {
            Log::debug('Artist does not exist in DB');

            try {
                $spotifyArtistFull = $api->getArtist($spotifyArtistId);
            } catch (Exception $e) {
                Log::error('Error fetching artist with ID ' . $spotifyArtistId . ': ' . $e->getMessage());
                throw $e;
            }

            $imageUrl = null;
            $maxWidth = 0;
            foreach ($spotifyArtistFull->images as $image) {
                if ($image->width > $maxWidth) {
                    $maxWidth = $image->width;
                    $imageUrl = $image->url;
                }
            }

            // Save to DB
            $artist = SpotifyArtist::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyArtistId,
                'name' => $spotifyArtistFull->name,
                'genres' => $spotifyArtistFull->genres,
                'image_url' => $imageUrl,
                'url' => $spotifyArtistFull->external_urls->spotify,
            ]);

            // Create in Notion
            Log::debug('About to save artist to Notion');
            $notionId = $this->saveArtistToNotion($notion, $dashboard, $artist);

            Log::debug('About to update with Notion ID: ' . $notionId);
            $artist->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function saveArtistToNotion($notion, $dashboard, SpotifyArtist $spotifyArtist): string
    {
        $databaseId = $dashboard->artist_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($spotifyArtist->name);
        $spotifyId = \Notion\Pages\Properties\RichTextProperty::fromString($spotifyArtist->spotify_id);

        $genres = \Notion\Pages\Properties\MultiSelect::fromNames(...$spotifyArtist->genres);
        $url = \Notion\Pages\Properties\Url::create($spotifyArtist->url);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Genres", $genres)
            ->addProperty("URL", $url);

        // Have encountered an artist without an image
        if ($spotifyArtist->image_url) {
            $imageFile = File::createExternal($spotifyArtist->image_url);
            $imageFiles = \Notion\Pages\Properties\Files::create($imageFile);
            $page = $page->addProperty("Image", $imageFiles)
                ->changeCover($imageFile);
        }

        $page = $notion->pages()->create($page);

        return $page->id;
    }

    private function saveAlbum(SpotifyWebAPI $api, $notion, $dashboard, string $spotifyAlbumId)
    {
        Log::debug('Album ID: ' . $spotifyAlbumId);

        $album = SpotifyAlbum::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $spotifyAlbumId)->first();

        if ($album && $album->notion_id) {
            Log::debug('Album exists in DB and Notion');
            return;
        } else if ($album) {
            Log::debug('Album exists in DB, but not Notion');

            $notionId = $this->saveAlbumToNotion($notion, $dashboard, $album);
            $album->update([
                'notion_id' => $notionId,
            ]);
        } else {
            Log::debug('Album does not exist in DB');

            try {
                $spotifyAlbumFull = $api->getAlbum($spotifyAlbumId);
            } catch (Exception $e) {
                Log::error('Error fetching album with ID ' . $spotifyAlbumId . ': ' . $e->getMessage());
                throw $e;
            }

            $imageUrl = null;
            $maxWidth = 0;
            foreach ($spotifyAlbumFull->images as $image) {
                if ($image->width > $maxWidth) {
                    $maxWidth = $image->width;
                    $imageUrl = $image->url;
                }
            }

            // Save to DB
            $album = SpotifyAlbum::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyAlbumId,
                'name' => $spotifyAlbumFull->name,
                'image_url' => $imageUrl,
                'album_type' => $spotifyAlbumFull->album_type,
                'type' => $spotifyAlbumFull->type,
                'release_date' => $spotifyAlbumFull->release_date,
                'spotify_artist_ids' => array_map(fn($a) => $a->id, $spotifyAlbumFull->artists),
                'genres' => $spotifyAlbumFull->genres,
                'url' => $spotifyAlbumFull->external_urls->spotify,
            ]);

            // Create in Notion
            $notionId = $this->saveAlbumToNotion($notion, $dashboard, $album);
            $album->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function saveAlbumToNotion($notion, $dashboard, SpotifyAlbum $album): string
    {
        $databaseId = $dashboard->album_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $title    = \Notion\Pages\Properties\Title::fromString($album->name);
        $spotifyId = \Notion\Pages\Properties\RichTextProperty::fromString($album->spotify_id);
        $albumType = \Notion\Pages\Properties\RichTextProperty::fromString($album->album_type);
        $releaseDate  = \Notion\Pages\Properties\Date::create(new DateTimeImmutable($album->release_date));

        $genres = \Notion\Pages\Properties\MultiSelect::fromNames(...$album->genres);
        $imageFile = File::createExternal($album->image_url);
        $imageFiles = \Notion\Pages\Properties\Files::create($imageFile);

        $artists = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->whereIn('spotify_id', $album->spotify_artist_ids)
            ->get();
        $artistNotionIDs = $artists->map(fn($a) => $a->notion_id)->toArray();
        $artistRelation = \Notion\Pages\Properties\Relation::create(...$artistNotionIDs);
        $url = \Notion\Pages\Properties\Url::create($album->url);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Type", $albumType)
            ->addProperty("Release Date", $releaseDate)
            ->addProperty("Genres", $genres)
            ->addProperty("Image", $imageFiles)
            ->addProperty("Artists", $artistRelation)
            ->addProperty("URL", $url)
            ->changeCover($imageFile);

        $page = $notion->pages()->create($page);

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

            $notionId = $this->saveTrackToNotion($notion, $dashboard, $track);
            $track->update([
                'notion_id' => $notionId,
            ]);
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
                'url' => $spotifyTrack->external_urls->spotify,
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
        $url = \Notion\Pages\Properties\Url::create($track->url);

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Duration", $durationMs)
            ->addProperty("Popularity", $popularity)
            ->addProperty("Artists", $artistRelation)
            ->addProperty("Album", $albumRelation)
            ->addProperty("URL", $url);

        $page = $notion->pages()->create($page);

        return $page->id;
    }

    private function savePlayHistory($notion, $dashboard, $track)
    {
        $playedAt = new DateTimeImmutable($track->played_at);
        $spotifyTrackId = $track->track->id;

        $playHistory = SpotifyPlayHistory::where('spotify_dashboard_id', $dashboard->id)
            ->where('played_at', $playedAt)
            ->first();

        if ($playHistory && $playHistory->notion_id) {
            Log::debug('Track exists in DB');
            return;
        } else if ($playHistory) {
            Log::debug('Track exists in DB, but not Notion');

            $notionId = $this->savePlayHistoryToNotion($notion, $dashboard, $playHistory);
            $playHistory->update([
                'notion_id' => $notionId,
            ]);
        } else {
            Log::debug('Track does not exist in DB');

            // Save to DB
            $playHistory = SpotifyPlayHistory::create([
                'spotify_dashboard_id' => $dashboard->id,
                'played_at' => $playedAt,
                'spotify_track_id' => $spotifyTrackId,
            ]);

            // Create in Notion
            $notionId = $this->savePlayHistoryToNotion($notion, $dashboard, $playHistory);
            $playHistory->update([
                'notion_id' => $notionId,
            ]);
        }
    }

    private function savePlayHistoryToNotion($notion, $dashboard, $playHistory): string
    {
        $databaseId = $dashboard->play_history_db_notion_id;
        $parent = \Notion\Pages\PageParent::database($databaseId);

        $playedAt = \Notion\Pages\Properties\Date::create(new DateTimeImmutable($playHistory->played_at));
        $playedAtTimestamp = \Notion\Pages\Properties\Number::create($playHistory->played_at->getTimestamp() * 1000);
        $track = SpotifyTrack::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $playHistory->spotify_track_id)
            ->first();
        $trackNotionID = $track->notion_id;
        $trackRelation = \Notion\Pages\Properties\Relation::create($trackNotionID);

        $album = SpotifyAlbum::where('spotify_dashboard_id', $dashboard->id)
            ->where('spotify_id', $track->spotify_album_id)
            ->first();
        $albumNotionID = $album->notion_id;
        $albumRelation = \Notion\Pages\Properties\Relation::create($albumNotionID);

        $artists = SpotifyArtist::where('spotify_dashboard_id', $dashboard->id)
            ->whereIn('spotify_id', $track->spotify_artist_ids)
            ->get(['name', 'notion_id']);
        $artistNotionIDs = $artists->pluck('notion_id')->toArray();
        $artistRelation = \Notion\Pages\Properties\Relation::create(...$artistNotionIDs);

        $artistNames = $artists->pluck('name')->toArray();
        $trackName = $track->name;
        $artistNamesString = implode(', ', $artistNames);
        $name = \Notion\Pages\Properties\Title::fromString("$trackName - $artistNamesString");

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Played At", $playedAt)
            // Dates don't keep second precision in Notion, so we need to
            // save the timestamp as a number to do proper sorting and
            // comparisons, e.g. for the "last 5 played" formula.
            ->addProperty("Played At Timestamp", $playedAtTimestamp)
            ->addProperty("Song", $trackRelation)
            ->addProperty("Album", $albumRelation)
            ->addProperty("Artists", $artistRelation)
            ->addProperty("Name", $name);

        $page = $notion->pages()->create($page);

        return $page->id;
    }

    private function updateLast5PlayedInNotion($notion, $dashboard)
    {
        $databaseId = $dashboard->play_history_db_notion_id;

        $last5Played = SpotifyPlayHistory::where('spotify_dashboard_id', $dashboard->id)
            ->orderBy('played_at', 'desc')
            ->take(5)
            ->get();

        if ($last5Played->count() == 0) {
            Log::debug('No play history to update last 5 played.');
            return;
        }

        $oldestPlayedAt = $last5Played->last()->played_at;
        $oldestPlayedAtTimestamp = $oldestPlayedAt->getTimestamp() * 1000;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $dashboard->workspace->authData['access_token'],
            'Content-Type' => 'application/json',
            'Notion-Version' => '2022-06-28',
        ])->patch('https://api.notion.com/v1/databases/' . $databaseId, [
            'properties' => [
                'In last 5' => [
                    'formula' => [
                        // Dates don't keep second precision in Notion, so we
                        // need to compare timestamps that we've saved as
                        // numbers.
                        //
                        // Also, date compares like `prop("Played At") >
                        // parseDate("2024-10-29T00:00Z")` work in the Notion UI
                        // but seem to give a type error when updating via API.
                        // They work when comparing two dates from parseDate or
                        // two dates from prop(), but not when mixed. Even when
                        // I formatted the prop() to a string and parsed it back
                        // again.
                        'expression' => "toNumber(prop(\"Played At Timestamp\")) >= toNumber(" . $oldestPlayedAtTimestamp . ")"
                    ]
                ]
            ]
        ]);


        if ($response->failed()) {
            Log::error('Failed to update Notion database: ' . $response->body());
            throw new Exception('Failed to update Notion database: ' . $response->body());
        } else {
            Log::debug('Successfully updated Notion database with last 5 played formula.');
        }
    }
}
