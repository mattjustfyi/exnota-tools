<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyAlbum extends Model
{
    use HasFactory;

    protected $fillable = [
        'spotify_id',
        'name',
        'spotify_dashboard_id',
        'notion_id',
        'image_url',
        'album_type',
        'type',
        'release_date',
        'spotify_artist_ids',
        'genres',
    ];

    protected $casts = [
        'release_date' => 'date',
        'spotify_artist_ids' => 'array',
        'genres' => 'array',
    ];

    public function spotifyDashboard(): BelongsTo
    {
        return $this->belongsTo(SpotifyDashboard::class);
    }
}
