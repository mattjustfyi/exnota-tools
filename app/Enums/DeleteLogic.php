<?php

namespace App\Enums;

enum DeleteLogic: string
{
    case KEEP_OLDEST = 'keep_oldest';
    case KEEP_LATEST = 'keep_latest';

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
