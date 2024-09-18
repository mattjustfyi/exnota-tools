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
                $duplicateResultsDTOs = array_map(function (DuplicateResultsDTO $entry) {
                    $keep = new NotionDTO(
                        $entry->keep->notion_id,
                        $entry->keep->title,
                        $entry->keep->type,
                        $entry->keep->property_values,
                        $entry->keep->created_date,
                        $entry->keep->updated_date,
                    );

                    $delete = array_map(function (NotionDTO $notionDTO) {
                        return [
                            'notion_id' => $notionDTO->notion_id,
                            'title' => $notionDTO->title,
                            'type' => $notionDTO->type,
                            'property_values' => $notionDTO->property_values,
                            'created_date' => $notionDTO->created_date->toDateTimeString(),
                            'updated_date' => $notionDTO->updated_date->toDateTimeString(),
                        ];
                    }, $entry->delete);

                    return [
                        'keep' => $keep,
                        'delete' => $delete,
                    ];
                }, $value);

                return json_encode($duplicateResultsDTOs);
            }
        );
    }

    public function notionObject(): BelongsTo
    {
        return $this->belongsTo(NotionObject::class);
    }
}

readonly class DuplicateResultsDTO
{
    public function __construct(
        /**
         * Represents a Tool Duplicate Request.
         *
         * @property array<NotionDTO> $keep The items to keep in the duplicate request.
         * @property array<NotionDTO> $delete The items to delete in the duplicate request.
         */
        public NotionDTO $keep,
        public array $delete,
    ) {}
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
