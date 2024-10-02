<?php

namespace App\Constants;

class CacheKey
{
    public const DUPLICATE_DELETE_LIST_COUNT_MIN = 'duplicate:delete:delay:size-minimum';
    public const DUPLICATE_DELETE_LIST_COUNT_DELAY = 'duplicate:delete:delay:size-delay';
    public const DUPLICATE_DELETE_ERROR_LOOKBACK_MINUTES = 'duplicate:delete:delay:error-lookback-minutes';
    public const DUPLICATE_DELETE_ERROR_DELAY = 'duplicate:delete:delay:error-delay';

    public static function getDuplicateDeleteSuccessKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:success";
    }

    public static function getDuplicateDeleteErrorKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:error";
    }
}
