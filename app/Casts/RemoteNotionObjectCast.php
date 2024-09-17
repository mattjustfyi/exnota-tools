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
    public function get(Model $model, string $key, mixed $value, array $attributes): ?RemoteNotionObject
    {
        if (is_null($value)) {
            return null;
        }

        $data = json_decode($value, true);

        return new RemoteNotionObject(
            $data['notion_id'],
            $data['title'],
            NotionObjectType::from($data['type']),
            $data['property_values'],
            Carbon::parse($data['created_date']),
            Carbon::parse($data['updated_date'])
        );
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

        if (!$value instanceof RemoteNotionObject) {
            throw new \InvalidArgumentException('The given value is not a RemoteNotionObject instance.');
        }

        return json_encode([
            'notion_id' => $value->getNotionID(),
            'title' => $value->getTitle(),
            'type' => $value->getType()->value,
            'property_values' => $value->getPropertyValues(),
            'created_date' => $value->getCreatedDate()->toIso8601String(),
            'updated_date' => $value->getUpdatedDate()->toIso8601String(),
        ]);
    }
}
