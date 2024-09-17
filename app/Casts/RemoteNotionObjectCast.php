<?php

namespace App\Casts;

use App\Models\RemoteNotionObject;
use App\Enums\NotionObjectType;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RemoteNotionObjectCast implements CastsAttributes
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

        return array_map(function ($item) {
            return new RemoteNotionObject(
                $item['notion_id'],
                $item['title'],
                NotionObjectType::from($item['type']),
                $item['property_values'],
                Carbon::parse($item['created_date']),
                Carbon::parse($item['updated_date'])
            );
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

        if (!is_array($value) || !$this->isArrayOfRemoteNotionObjects($value)) {
            throw new \InvalidArgumentException('The given value is not an array of RemoteNotionObject instances.');
        }

        return json_encode(array_map(function (RemoteNotionObject $object) {
            return [
                'notion_id' => $object->getNotionId(),
                'title' => $object->getTitle(),
                'type' => $object->getType()->value,
                'property_values' => $object->getPropertyValues(),
                'created_date' => $object->getCreatedDate()->toIso8601String(),
                'updated_date' => $object->getUpdatedDate()->toIso8601String(),
            ];
        }, $value));
    }

    /**
     * Check if the given array contains only RemoteNotionObject instances.
     *
     * @param array $array
     * @return bool
     */
    private function isArrayOfRemoteNotionObjects(array $array): bool
    {
        return !empty($array) && array_reduce($array, function ($carry, $item) {
            return $carry && $item instanceof RemoteNotionObject;
        }, true);
    }
}
