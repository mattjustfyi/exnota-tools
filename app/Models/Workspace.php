<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'authData',
        'user_avatar',
        'workspace_name',
        'workspace_icon',
        'sync_status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'authData',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the NotionObjects for the Workspace.
     */
    public function notionObjects(): HasMany
    {
        return $this->hasMany(NotionObject::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'authData' => 'json',
        'sync_status' => SyncStatus::class,
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'user_avatar' => null,
        'workspace_name' => null,
        'workspace_icon' => null,
        'sync_status' => SyncStatus::COMPLETE,
    ];
}
