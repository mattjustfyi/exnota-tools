<?php

namespace App\Helpers;

use App\Models\SpotifyDashboard;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyHelper
{
    /**
     * Returns a Spotify Web API instance and refreshes the access token if necessary.
     *
     */
    public static function getAPIForDashboard(SpotifyDashboard $dashboard): SpotifyWebAPI
    {
        $session = new Session(
            env('SPOTIFY_CLIENT_ID'),
            env('SPOTIFY_CLIENT_SECRET'),
            env('SPOTIFY_REDIRECT_URI')
        );

        $accessToken = $dashboard->access_token;

        // Need to refresh access token?
        if (is_null($dashboard->access_token_expires_at) || $dashboard->access_token_expires_at->lte(now()->addMinutes(5))) {
            // Get new token and expiry
            $session->refreshAccessToken($dashboard->refresh_token);
            $accessToken = $session->getAccessToken();
            $expiresAtInt = $session->getTokenExpiration();
            $expiresAt = \Carbon\Carbon::createFromTimestamp($expiresAtInt);

            // Persist token and expiry
            $dashboard->access_token = $accessToken;
            $dashboard->access_token_expires_at = $expiresAt;
            $dashboard->save();
        }

        $api = new SpotifyWebAPI();
        $api->setAccessToken($accessToken);
        return $api;
    }
}
