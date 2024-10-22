<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyPlayHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'notion_id',
        'spotify_dashboard_id',
        'spotify_track_id',
        'played_at',
    ];

    protected $casts = [
        'played_at' => 'datetime',
    ];

    public function spotifyDashboard(): BelongsTo
    {
        return $this->belongsTo(SpotifyDashboard::class);
    }
}
