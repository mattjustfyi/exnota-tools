<?php

namespace App\Models;

use App\Casts\RemoteNotionObjectArrayCast;
use App\Enums\NotionObjectType;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Castable;

class RemoteNotionObject implements Castable
{

    // Define the attributes that should be visible in arrays
    protected $visible = [
        'notion_id',
        'title',
        'type',
        'property_values',
        'created_date',
        'updated_date',
    ];

    private string $notion_id;
    private string $title;
    private NotionObjectType $type;
    private array $property_values;
    private Carbon $created_date;
    private Carbon $updated_date;

    public function __construct(
        string $notion_id,
        string $title,
        NotionObjectType $type,
        array $property_values,
        Carbon $created_date,
        Carbon $updated_date
    ) {
        $this->notion_id = $notion_id;
        $this->title = $title;
        $this->type = $type;
        $this->property_values = $property_values;
        $this->created_date = $created_date;
        $this->updated_date = $updated_date;
    }

    public function getNotionId(): string
    {
        return $this->notion_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): NotionObjectType
    {
        return $this->type;
    }

    public function getPropertyValues(): array
    {
        return $this->property_values;
    }

    public function getCreatedDate(): Carbon
    {
        return $this->created_date;
    }

    public function getUpdatedDate(): Carbon
    {
        return $this->updated_date;
    }

    public static function castUsing(array $arguments)
    {
        return RemoteNotionObjectArrayCast::class;
    }
}
