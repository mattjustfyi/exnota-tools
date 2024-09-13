<?php

namespace App\Models;

use App\Enums\NotionObjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotionObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'type',
        'name',
        'data',
    ];

    protected $casts = [
        'type' => NotionObjectType::class,
        'data' => 'json',
    ];

    /**
     * Get the workspace that owns the notion object.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
