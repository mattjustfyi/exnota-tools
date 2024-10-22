<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotifyDashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_token',
        'artist_db_notion_id',
        'album_db_notion_id',
        'track_db_notion_id',
        'play_history_db_notion_id',
        'dashboard_page_notion_id',
    ];
}
