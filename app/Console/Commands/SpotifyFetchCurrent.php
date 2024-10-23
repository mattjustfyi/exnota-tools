<?php

namespace App\Console\Commands;

use App\DTOs\ArtistDTO;
use App\DTOs\CurrentTrackDTO;
use App\Enums\PlayStatus;
use App\Helpers\SpotifyHelper;
use App\Models\SpotifyDashboard;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Notion\Notion;

class SpotifyFetchCurrent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spotify:fetch-current';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches current playing track from Spotify and updates users Notion.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::debug('Fetch current!');

        // -----
        // Active
        // -----

        // Every 5 seconds

        // Ping spotify API for current status
        // Later this scheduler will create a job for each user. For now, the
        // user is hardcoded.
        $dashboard = SpotifyDashboard::first();

        $api = SpotifyHelper::getAPIForDashboard($dashboard);


        try {
            // `getMyCurrentPlaybackInfo` can return null if no playback is
            // happening. The actual endpoint still returns data, however the
            // SDK doesn't seem to.
            // TODO: handle null
            $playbackInfo = $api->getMyCurrentPlaybackInfo();
        } catch (Exception $e) {
            Log::error('Error fetching playback info: ' . $e->getMessage());
            throw $e;
        }

        // Not playing
        if (!$playbackInfo || !$playbackInfo->is_playing) {
            // Update DB if it hasn't already been.
            if ($dashboard->current_track) {
                Log::debug('No playback is happening, clearing current track data.');
                $dashboard->play_status = PlayStatus::NOT_PLAYING;
                $dashboard->current_track = null;
                $dashboard->save();
            }

            // Update Notion if it hasn't already been.
            if ($dashboard->spotify_track_id_in_notion) {
                $this->updateNotionToNotPlaying($dashboard);
            }

            return;
        }

        // Playing

        // If DB has same track, no need to update
        if (
            $dashboard->current_track &&
            $dashboard->current_track->spotify_id === $playbackInfo->item->id
        ) {
            Log::debug('Current track has not changed, skipping updates.');

            // Update Notion if it hasn't already been.
            if ($dashboard->spotify_track_id_in_notion !== $playbackInfo->item->id) {
                Log::debug('Current track has not changed, but track ID in Notion is different, updating.');
                $this->updateNotionToPlaying($dashboard);
            }

            return;
        }

        // Get artist images
        try {
            $artistDTOs = [];
            foreach ($playbackInfo->item->artists as $artist) {
                $artistData = $api->getArtist($artist->id);
                $artistDTO = new ArtistDTO(
                    $artistData->name,
                    $artistData->id,
                    $artistData->external_urls->spotify,
                    $this->getLargestImage($artistData->images)
                );
                $artistDTOs[] = $artistDTO;
            }
        } catch (Exception $e) {
            Log::error('Error fetching recent tracks: ' . $e->getMessage());
            throw $e;
        }

        // Persist dashboard updates
        $dashboard = DB::transaction(function () use ($playbackInfo, $artistDTOs, $dashboard) {
            $dto = new CurrentTrackDTO(
                $playbackInfo->item->name,
                $playbackInfo->item->id,
                $playbackInfo->item->external_urls->spotify,
                $playbackInfo->item->duration_ms,
                $artistDTOs,
                $playbackInfo->item->album->name,
                $playbackInfo->item->album->id,
                $playbackInfo->item->album->external_urls->spotify,
                $this->getLargestImage($playbackInfo->item->album->images)
            );

            $dashboard->play_status = $playbackInfo->is_playing ? PlayStatus::PLAYING : PlayStatus::NOT_PLAYING;
            $dashboard->current_track = $dto;
            $dashboard->save();

            return $dashboard;
        });

        $this->updateNotionToPlaying($dashboard);
    }

    /**
     * Get the largest image from an array of images.
     *
     * @param array $images
     * @return string|null
     */
    private function getLargestImage(array $images): ?string
    {
        $largestImageURL = null;
        $maxWidth = 0;

        foreach ($images as $image) {
            if ($image->width > $maxWidth) {
                $maxWidth = $image->width;
                $largestImageURL = $image->url;
            }
        }

        return $largestImageURL;
    }

    private function updateNotionToNotPlaying($dashboard)
    {
        $notion = Notion::create($dashboard->workspace->authData['access_token']);
        // // Update dashboard title, icon, cover image
        // $page = $notion->pages()->retrieve($dashboard->notion_id);
        // $page->update([
        //     'properties' => [
        //         'Name' => $dashboard->name,
        //         'Icon' => $dashboard->icon,
        //         'Cover Image' => $dashboard->cover_image,
        //     ],
        // ]);

        // TODO: Update spotify_track_id_in_notion
    }

    private function updateNotionToPlaying($dashboard)
    {
        $notion = Notion::create($dashboard->workspace->authData['access_token']);
        // // Update dashboard title, icon, cover image
        // $page = $notion->pages()->retrieve($dashboard->notion_id);
        // $page->update([
        //     'properties' => [
        //         'Name' => $dashboard->name,
        //         'Icon' => $dashboard->icon,
        //         'Cover Image' => $dashboard->cover_image,
        //     ],
        // ]);

        // TODO: Update spotify_track_id_in_notion
    }
}
