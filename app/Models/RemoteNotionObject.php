<?php

namespace App\Models;

use App\Enums\NotionObjectType;
use Illuminate\Support\Carbon;

class RemoteNotionObject
{
    private string $notionID;
    private string $title;
    private NotionObjectType $type;
    private array $propertyValues;
    private Carbon $createdDate;
    private Carbon $updatedDate;

    public function __construct(
        string $notionID,
        string $title,
        NotionObjectType $type,
        array $propertyValues,
        Carbon $createdDate,
        Carbon $updatedDate
    ) {
        $this->notionID = $notionID;
        $this->title = $title;
        $this->type = $type;
        $this->propertyValues = $propertyValues;
        $this->createdDate = $createdDate;
        $this->updatedDate = $updatedDate;
    }

    public function getNotionID(): string
    {
        return $this->notionID;
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
        return $this->propertyValues;
    }

    public function getCreatedDate(): Carbon
    {
        return $this->createdDate;
    }

    public function getUpdatedDate(): Carbon
    {
        return $this->updatedDate;
    }
}
