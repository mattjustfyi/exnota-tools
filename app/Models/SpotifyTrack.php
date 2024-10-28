<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'spotify_id',
        'notion_id',
        'spotify_dashboard_id',
        'name',
        'duration_ms',
        'spotify_artist_ids',
        'spotify_album_id',
        'popularity',
        'url',
    ];

    protected $casts = [
        'spotify_artist_ids' => 'array',
        'duration_ms' => 'integer',
        'popularity' => 'integer',
    ];

    public function spotifyDashboard(): BelongsTo
    {
        return $this->belongsTo(SpotifyDashboard::class);
    }
}
