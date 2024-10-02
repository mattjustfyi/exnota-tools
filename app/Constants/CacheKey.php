<?php

namespace App\Constants;

class CacheKey
{
    public const DUPLICATE_DELETE_DELAY_SIZE_MINIMUM = 'duplicate:delete:delay:size-minimum';
    public const DUPLICATE_DELETE_DELAY_SIZE_DELAY = 'duplicate:delete:delay:size-delay';
    public const DUPLICATE_DELETE_DELAY_ERROR_LOOKBACK_MINUTES = 'duplicate:delete:delay:error-lookback-minutes';
    public const DUPLICATE_DELETE_DELAY_ERROR_DELAY = 'duplicate:delete:delay:error-delay';

    public static function getDuplicateDeleteSuccessKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:success";
    }

    public static function getDuplicateDeleteErrorKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:error";
    }
}
