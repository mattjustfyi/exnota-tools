<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyArtist extends Model
{
    use HasFactory;

    protected $fillable = [
        'spotify_id',
        'name',
        'spotify_dashboard_id',
        'notion_id',
        'genres',
        'image_url',
    ];

    protected $casts = [
        'genres' => 'array',
    ];

    public function spotifyDashboard(): BelongsTo
    {
        return $this->belongsTo(SpotifyDashboard::class, 'spotify_dashboard_id');
    }
}
