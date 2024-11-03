<?php

namespace App\Events;

use App\Models\SpotifyDashboard;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SpotifyDashboardCurrentPlayingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpotifyDashboard $spotifyDashboard;

    /**
     * Create a new event instance.
     */
    public function __construct(SpotifyDashboard $spotifyDashboard)
    {
        $this->spotifyDashboard = $spotifyDashboard;

        Log::debug('SpotifyDashboardCurrentPlayingUpdated::__construct');
    }

    public function broadcastWith()
    {
        // This must always be an array. Since it will be parsed with json_encode()
        return [
            'current_track' => $this->spotifyDashboard->current_track,
        ];
    }

    public function broadcastAs()
    {
        return 'CurrentPlayingUpdated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('spotify.dashboard.' . $this->spotifyDashboard->id),
        ];
    }
}
