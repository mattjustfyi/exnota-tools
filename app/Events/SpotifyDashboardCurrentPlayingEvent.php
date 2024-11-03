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

class SpotifyDashboardCurrentPlayingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpotifyDashboard $spotifyDashboard;

    /**
     * Create a new event instance.
     */
    public function __construct(SpotifyDashboard $spotifyDashboard)
    {
        $this->spotifyDashboard = $spotifyDashboard;

        Log::debug('SpotifyDashboardCurrentPlayingEvent::__construct');
    }

    public function broadcastWith()
    {
        // This must always be an array. Since it will be parsed with json_encode()
        return [
            'message' => 'This is a test message',
            'timestamp' => now()->toDateTimeString()
        ];
    }

    public function broadcastAs()
    {
        return 'CurrentPlaying';
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
