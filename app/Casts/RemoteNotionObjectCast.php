<?php

namespace App\Casts;

use App\Models\RemoteNotionObject;
use App\Enums\NotionObjectType;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RemoteNotionObjectCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Collection
    {
        if (is_null($value)) {
            return null;
        }

        $data = json_decode($value, true);

        return collect($data)->map(function ($item) {
            return new RemoteNotionObject(
                $item['notion_id'],
                $item['title'],
                NotionObjectType::from($item['type']),
                $item['property_values'],
                Carbon::parse($item['created_date']),
                Carbon::parse($item['updated_date'])
            );
        });
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

        if (!$value instanceof Collection || !$this->isCollectionOfRemoteNotionObjects($value)) {
            throw new \InvalidArgumentException('The given value is not a collection of RemoteNotionObject instances.');
        }

        return json_encode($value->map(function (RemoteNotionObject $object) {
            return [
                'notion_id' => $object->getNotionId(),
                'title' => $object->getTitle(),
                'type' => $object->getType()->value,
                'property_values' => $object->getPropertyValues(),
                'created_date' => $object->getCreatedDate()->toIso8601String(),
                'updated_date' => $object->getUpdatedDate()->toIso8601String(),
            ];
        })->all());
    }

    /**
     * Check if the given collection contains only RemoteNotionObject instances.
     *
     * @param Collection $collection
     * @return bool
     */
    private function isCollectionOfRemoteNotionObjects(Collection $collection): bool
    {
        return !$collection->isEmpty() && $collection->every(function ($item) {
            return $item instanceof RemoteNotionObject;
        });
    }
}
