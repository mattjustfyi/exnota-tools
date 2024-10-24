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

            // Save play history
            $this->savePlayHistory($notion, $dashboard, $track);
        }

        // TODO: Genres
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

            $notionId = $this->saveArtistToNotion($notion, $dashboard, $spotifyArtist);
            $artist->update([
                'notion_id' => $notionId,
            ]);
        } else {
            Log::debug('Artist does not exist in DB');
            // Save to DB
            $artist = SpotifyArtist::create([
                'spotify_dashboard_id' => $dashboard->id,
                'spotify_id' => $spotifyArtistId,
                'name' => $spotifyArtist->name,
            ]);

            // Create in Notion
            Log::debug('About to save artist to Notion');
            $notionId = $this->saveArtistToNotion($notion, $dashboard, $spotifyArtist);

            Log::debug('About to update with Notion ID: ' . $notionId);
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

            $notionId = $this->saveAlbumToNotion($notion, $dashboard, $album);
            $album->update([
                'notion_id' => $notionId,
            ]);
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

        $page = \Notion\Pages\Page::create($parent)
            ->addProperty("Name", $title)
            ->addProperty("Spotify ID", $spotifyId)
            ->addProperty("Type", $albumType)
            ->addProperty("Release Date", $releaseDate)
            ->addProperty("Image", $imageFiles)
            ->addProperty("Artists", $artistRelation)
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
            ->addProperty("Song", $trackRelation)
            ->addProperty("Album", $albumRelation)
            ->addProperty("Artists", $artistRelation)
            ->addProperty("Name", $name);

        $page = $notion->pages()->create($page);

        return $page->id;
    }
}
