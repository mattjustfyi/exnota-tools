<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('spotify.dashboard.{spotifyDashboardId}', function (User $user, int $spotifyDashboardId) {
    // TODO: Add auth check
    return true;
});
