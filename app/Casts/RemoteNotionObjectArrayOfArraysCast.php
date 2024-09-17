<?php

namespace App\Casts;

use App\Models\RemoteNotionObject;
use App\Enums\NotionObjectType;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RemoteNotionObjectArrayOfArraysCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        $data = json_decode($value, true);

        return array_map(function ($subArray) {
            return collect($subArray)->map(function ($item) {
                return new RemoteNotionObject(
                    $item['notion_id'],
                    $item['title'],
                    NotionObjectType::from($item['type']),
                    $item['property_values'],
                    Carbon::parse($item['created_date']),
                    Carbon::parse($item['updated_date'])
                );
            })->all();
        }, $data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_array($value) || !$this->isArrayOfArraysOfRemoteNotionObjects($value)) {
            throw new \InvalidArgumentException('The given value is not an array of arrays of RemoteNotionObject instances.');
        }

        return json_encode(array_map(function ($subArray) {
            return array_map(function (RemoteNotionObject $object) {
                return [
                    'notion_id' => $object->getNotionId(),
                    'title' => $object->getTitle(),
                    'type' => $object->getType()->value,
                    'property_values' => $object->getPropertyValues(),
                    'created_date' => $object->getCreatedDate()->toIso8601String(),
                    'updated_date' => $object->getUpdatedDate()->toIso8601String(),
                ];
            }, $subArray);
        }, $value));
    }

    /**
     * Check if the given array contains only arrays of RemoteNotionObject instances.
     *
     * @param array $array
     * @return bool
     */
    private function isArrayOfArraysOfRemoteNotionObjects(array $array): bool
    {
        return !empty($array) && array_reduce($array, function ($carry, $item) {
            return $carry && is_array($item) && !empty($item) && array_reduce($item, function ($innerCarry, $innerItem) {
                return $innerCarry && $innerItem instanceof RemoteNotionObject;
            }, true);
        }, true);
    }
}
