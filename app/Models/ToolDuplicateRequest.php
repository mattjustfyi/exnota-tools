<?php

namespace App\Models;

use App\DTOs\NotionDTO;
use App\Enums\DeleteLogic;
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
        'duplicates',
        'duplicates_sample',
        'duplicates_count',
        'search_started_at',
        'search_ended_at',
        'delete_logic',
        'delete_started_at',
        'delete_ended_at',
        'delete_results',
        'delete_results_count',
        'not_deleted_count',
        'delete_list',
        'delete_list_count',
        'delete_batch_id',
        'search_batch_id',
        'search_results',
        'search_results_count',
        'search_failed',
        'delete_cancelled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // These fields can be very large, so hide from serialization.
        'duplicates',
        'delete_results',
        'delete_list',
        'search_results',
    ];

    protected $casts = [
        'search_started_at' => 'datetime',
        'search_ended_at' => 'datetime',
        'delete_started_at' => 'datetime',
        'delete_ended_at' => 'datetime',
        'search_failed' => 'boolean',
        'delete_cancelled' => 'boolean',
        'search_results_count' => 'integer',
        'duplicates_count' => 'integer',
        'delete_list_count' => 'integer',
        'delete_results_count' => 'integer',
        'not_deleted_count' => 'integer',
        'delete_logic' => DeleteLogic::class,
    ];

    protected $attributes = [
        'delete_logic' => DeleteLogic::KEEP_OLDEST,
    ];

    protected function duplicates(): Attribute
    {
        return $this->getDupeDTOAttribute('duplicates');
    }

    protected function duplicatesSample(): Attribute
    {
        return $this->getDupeDTOAttribute('duplicates_sample');
    }

    private function getDupeDTOAttribute(string $attributeName)
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                $arr = json_decode($value, true);

                return array_map(function ($group) {
                    return array_map(function ($notionData) {
                        return new NotionDTO(
                            $notionData['notion_id'],
                            $notionData['title'],
                            NotionHelper::getNotionObjectType($notionData['type']),
                            $notionData['url'],
                            Carbon::parse($notionData['created_date']),
                            Carbon::parse($notionData['updated_date'])
                        );
                    }, $group);
                }, $arr);
            },
            set: function (array $value) {
                return json_encode(array_map(function ($group) {
                    return array_map(function (NotionDTO $notionDTO) {
                        return [
                            'notion_id' => $notionDTO->notion_id,
                            'title' => $notionDTO->title,
                            'type' => $notionDTO->type->value,
                            'url' => $notionDTO->url,
                            'created_date' => $notionDTO->created_date->toDateTimeString(),
                            'updated_date' => $notionDTO->updated_date->toDateTimeString(),
                        ];
                    }, $group);
                }, $value));
            }
        );
    }

    protected function deleteResults(): Attribute
    {
        return $this->getJsonArrayAttribute('delete_results');
    }

    protected function deleteList(): Attribute
    {
        return $this->getJsonArrayAttribute('delete_list');
    }

    private function getJsonArrayAttribute(string $attributeName)
    {
        return Attribute::make(
            get: fn(?string $value): array => $value ? json_decode($value, true) : [],
            set: function (array $value) use ($attributeName): string {
                if (!$this->isArrayOfStrings($value)) {
                    throw new \InvalidArgumentException("The {$attributeName} must be an array of strings.");
                }
                return json_encode($value);
            }
        );
    }

    private function isArrayOfStrings(array $array): bool
    {
        return array_reduce($array, fn($carry, $item) => $carry && is_string($item), true);
    }

    protected function searchResults(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                $arr = json_decode($value, true);

                return array_map(function ($entry) {
                    return new NotionDTO(
                        $entry['notion_id'],
                        $entry['title'],
                        NotionHelper::getNotionObjectType($entry['type']),
                        $entry['url'],
                        Carbon::parse($entry['created_date']),
                        Carbon::parse($entry['updated_date'])
                    );
                }, $arr);
            },
            set: function (array $value) {
                return json_encode(array_map(function (NotionDTO $dto) {
                    return [
                        'notion_id' => $dto->notion_id,
                        'title' => $dto->title,
                        'type' => $dto->type->value,
                        'url' => $dto->url,
                        'created_date' => $dto->created_date->toDateTimeString(),
                        'updated_date' => $dto->updated_date->toDateTimeString(),
                    ];
                }, $value));
            }
        );
    }

    public function notionObject(): BelongsTo
    {
        return $this->belongsTo(NotionObject::class);
    }
}

