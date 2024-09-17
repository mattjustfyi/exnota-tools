<?php

namespace App\Models;

use App\Casts\RemoteNotionObjectArrayCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolDuplicateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'notion_object_id',
        'search_results',
        'searched_at',
    ];

    protected $casts = [
        'search_results' => RemoteNotionObjectArrayCast::class,
        'searched_at' => 'datetime',
    ];

    public function notionObject(): BelongsTo
    {
        return $this->belongsTo(NotionObject::class);
    }
}
