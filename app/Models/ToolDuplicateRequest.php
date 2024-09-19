<?php

namespace App\Models;

use App\DTOs\NotionDTO;
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
        'search_started_at',
        'search_ended_at',
        'delete_started_at',
        'delete_ended_at',
        'duplicates',
        'delete_results',
    ];

    protected $casts = [
        'search_started_at' => 'datetime',
        'search_ended_at' => 'datetime',
        'delete_started_at' => 'datetime',
        'delete_ended_at' => 'datetime',
    ];

    protected function searchResults(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                $arr = json_decode($value, true);

                $duplicateResultsDTOs = array_map(function ($entry) {
                    $keep = new NotionDTO(
                        $entry['keep']['notion_id'],
                        $entry['keep']['title'],
                        NotionHelper::getNotionObjectType($entry['keep']['type']),
                        $entry['keep']['url'],
                        $entry['keep']['property_values'],
                        Carbon::parse($entry['keep']['created_date']),
                        Carbon::parse($entry['keep']['updated_date']),
                    );

                    $delete = array_map(function ($notionDTO) {
                        return new NotionDTO(
                            $notionDTO['notion_id'],
                            $notionDTO['title'],
                            NotionHelper::getNotionObjectType($notionDTO['type']),
                            $notionDTO['url'],
                            $notionDTO['property_values'],
                            Carbon::parse($notionDTO['created_date']),
                            Carbon::parse($notionDTO['updated_date']),
                        );
                    }, $entry['delete']);

                    return new DuplicateResultsDTO($keep, $delete);
                }, $arr);

                return $duplicateResultsDTOs;
            },
            set: function (array $value) {
                $duplicateResultsDTOs = array_map(function (DuplicateResultsDTO $entry) {
                    $keep = new NotionDTO(
                        $entry->keep->notion_id,
                        $entry->keep->title,
                        $entry->keep->type,
                        $entry->keep->url,
                        $entry->keep->property_values,
                        $entry->keep->created_date,
                        $entry->keep->updated_date,
                    );

                    $delete = array_map(function (NotionDTO $notionDTO) {
                        return [
                            'notion_id' => $notionDTO->notion_id,
                            'title' => $notionDTO->title,
                            'type' => $notionDTO->type,
                            'url' => $notionDTO->url,
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

    protected function deleteResults(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value): array => $value ? json_decode($value, true) : [],
            set: function (array $value): string {
                if (!$this->isArrayOfStrings($value)) {
                    throw new \InvalidArgumentException('The delete_results must be an array of strings.');
                }
                return json_encode($value);
            }
        );
    }

    private function isArrayOfStrings(array $array): bool
    {
        return array_reduce($array, fn($carry, $item) => $carry && is_string($item), true);
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
