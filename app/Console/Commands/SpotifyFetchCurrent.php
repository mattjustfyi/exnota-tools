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
use Illuminate\Support\Facades\Http;
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
        $dashboard = SpotifyDashboard::where('active', true)->first();
        if (!$dashboard) {
            Log::debug('No active dashboards found.');
            return;
        }

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
        // Some icons
        // - https://tabler.io/icons/icon/playlist
        // - https://tabler.io/icons/icon/device-audio-tape
        // - https://tabler.io/icons/icon/disc
        // - https://tabler.io/icons/icon/device-speaker
        $iconUrl = config('app.url') . '/images/spotify/vinyl.svg';
        // Using the SDK didn't work. It errored commenting about missing properties.
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $dashboard->workspace->authData['access_token'],
            'Content-Type' => 'application/json',
            'Notion-Version' => '2022-06-28',
        ])->patch('https://api.notion.com/v1/pages/' . $dashboard->dashboard_page_notion_id, [
            'properties' => [
                'title' => [
                    'title' => [
                        [
                            'text' => [
                                'content' => 'Music'
                            ]
                        ]
                    ]
                ]
            ],
            'icon' => [
                // 'emoji' => '🎧'
                'type' => 'external',
                'external' => [
                    'url' => $iconUrl
                ]
            ]
        ]);

        if ($response->successful()) {
            // Handle successful response
            Log::debug('Notion page updated successfully.');
        } else {
            // Handle error response
            Log::error('Error updating Notion page: ' . $response->body());
            throw new Exception('Error updating Notion page: ' . $response->body());
        }

        // Flag as successful in DB
        $dashboard->spotify_track_id_in_notion = null;
        $dashboard->save();
    }

    private function updateNotionToPlaying($dashboard)
    {
        // Using the SDK didn't work. It errored commenting about missing properties.
        $artistName = $dashboard->current_track->artists[0]->name;
        if (strlen($artistName) > 15) {
            $artistName = substr($artistName, 0, 12) . '...';
        }
        $title = $artistName . ' • ' . $dashboard->current_track->name . ' (Music)';

        // TODO: Add credit perhaps like this: https://opensource.stackexchange.com/questions/11864/using-icons-with-mit-licensing-on-the-website
        //
        // Some icons:
        // - https://tabler.io/icons/icon/volume
        // - https://tabler.io/icons/icon/wave-sine
        // - https://tabler.io/icons/icon/headphones
        // - https://tabler.io/icons/icon/music
        // - https://tabler.io/icons/icon/file-music

        $iconUrl = config('app.url') . '/images/spotify/headphones.svg';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $dashboard->workspace->authData['access_token'],
            'Content-Type' => 'application/json',
            'Notion-Version' => '2022-06-28',
        ])->patch('https://api.notion.com/v1/pages/' . $dashboard->dashboard_page_notion_id, [
            'properties' => [
                'title' => [
                    'title' => [
                        [
                            'text' => [
                                'content' => $title
                            ]
                        ]
                    ]
                ]
            ],
            'icon' => [
                // 'emoji' => '🎵'
                'type' => 'external',
                'external' => [
                    'url' => $iconUrl
                ]
            ]
        ]);

        if ($response->successful()) {
            // Handle successful response
            Log::debug('Notion page updated successfully.');
        } else {
            // Handle error response
            Log::error('Error updating Notion page: ' . $response->body());
            throw new Exception('Error updating Notion page: ' . $response->body());
        }

        // Flag as successful in DB
        $dashboard->spotify_track_id_in_notion = $dashboard->current_track->spotify_id;
        $dashboard->save();
    }
}
