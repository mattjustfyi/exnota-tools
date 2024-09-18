<?php

namespace App\DTOs;

use App\Enums\NotionObjectType;
use Carbon\Carbon;

readonly class NotionDTO
{
    public function __construct(
        public string $notion_id,
        public string $title,
        public NotionObjectType $type,
        public string $url,
        public array $property_values = [],
        public Carbon $created_date,
        public Carbon $updated_date
    ) {}
}
