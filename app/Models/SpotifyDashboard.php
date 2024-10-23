<?php

namespace App\Models;

use App\DTOs\CurrentTrackDTO;
use App\Enums\PlayStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SpotifyDashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'access_token',
        'access_token_expires_at',
        'artist_db_notion_id',
        'album_db_notion_id',
        'track_db_notion_id',
        'play_history_db_notion_id',
        'dashboard_page_notion_id',
        'play_status',
        'current_track',
        'spotify_track_id_in_notion',
    ];

    protected $casts = [
        'play_status' => PlayStatus::class,
        'access_token_expires_at' => 'datetime',
    ];

    protected function currentTrack(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? CurrentTrackDTO::fromArray(json_decode($value, true)) : null,
            set: fn (?CurrentTrackDTO $value) => $value ? json_encode($value->toArray()) : null
        );
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
