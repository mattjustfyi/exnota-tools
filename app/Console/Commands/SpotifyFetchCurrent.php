<?php

namespace App\Console\Commands;

use App\DTOs\ArtistDTO;
use App\DTOs\CurrentTrackDTO;
use App\Enums\PlayStatus;
use App\Models\SpotifyDashboard;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Notion\Notion;
use SpotifyWebAPI\SpotifyWebAPI;

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
        $workspace = $dashboard->workspace;

        $api = new SpotifyWebAPI();
        $api->setAccessToken($dashboard->access_token);


        try {
            // `getMyCurrentPlaybackInfo` can return null if no playback is
            // happening. The actual endpoint still returns data, however the
            // SDK doesn't seem to.
            // TODO: handle null
            $playbackInfo = $api->getMyCurrentPlaybackInfo();


            // TODO: For speed reasons we probably wanna skip this if the track hasn't changed
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

        // Persist
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

        // Update Notion
        $notion = Notion::create($workspace->authData['access_token']);



        // SpotifyDashboard
        // - play_status
        // - current_track
        //   - name
        //   - spotify_id
        //   - url
        //   - duration_ms
        //   - artist_name
        //   - artist_spotify_id
        //   - artist_url
        //   - artist_image_url
        //   - album_name
        //   - album_spotify_id
        //   - album_url
        //   - album_image_url

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
}
