<?php

namespace App\Models;

use App\Casts\RemoteNotionObjectArrayCast;
use App\Casts\RemoteNotionObjectArrayOfArraysCast;
use App\Enums\NotionObjectType;
use App\Helpers\NotionHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'duplicates',
    ];

    protected $casts = [
        // 'search_results' => RemoteNotionObject::class,
        'searched_at' => 'datetime',
        'duplicates' => RemoteNotionObjectArrayOfArraysCast::class,
    ];

    protected function searchResults(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                $arr = json_decode($value, true);

                $notionDTOs = array_map(function ($entry) {
                    return new NotionDTO(
                        $entry['notion_id'],
                        $entry['title'],
                        NotionHelper::getNotionObjectType($entry['type']),
                        $entry['property_values'],
                        Carbon::parse($entry['created_date']),
                        Carbon::parse($entry['updated_date']),
                    );
                }, $arr);

                return $notionDTOs;
            },
            set: function (array $value) {
                $notionDTOs = array_map(function (NotionDTO $entry) {
                    return [
                        'notion_id' => $entry->notion_id,
                        'title' => $entry->title,
                        'type' => $entry->type,
                        'property_values' => $entry->property_values,
                        'created_date' => $entry->created_date->toDateTimeString(),
                        'updated_date' => $entry->updated_date->toDateTimeString(),
                    ];
                }, $value);

                return json_encode($notionDTOs);
            }
        );
    }

    public function notionObject(): BelongsTo
    {
        return $this->belongsTo(NotionObject::class);
    }
}

readonly class NotionDTO
{
    public function __construct(
        public string $notion_id,
        public string $title,
        public NotionObjectType $type,
        public array $property_values = [],
        public Carbon $created_date,
        public Carbon $updated_date
    ) {}
}
