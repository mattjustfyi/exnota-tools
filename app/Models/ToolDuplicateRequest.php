<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolDuplicateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'notion_object_id',
        'search_results',
    ];

    protected $casts = [
        'search_results' => 'array',
    ];

    public function notionObject(): BelongsTo
    {
        return $this->belongsTo(NotionObject::class);
    }
}
