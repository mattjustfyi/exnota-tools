<?php

namespace App\Constants;

class CacheKey
{
    public const DUPLICATE_DELETE_DELAY_SIZE_MINIMUM_COUNT = 'duplicate:delete:delay:size-minimum-count';
    public const DUPLICATE_DELETE_DELAY_SIZE_DELAY_SECONDS = 'duplicate:delete:delay:size-delay-seconds';
    public const DUPLICATE_DELETE_DELAY_ERROR_LOOKBACK_MINUTES = 'duplicate:delete:delay:error-lookback-minutes';
    public const DUPLICATE_DELETE_DELAY_ERROR_DELAY_SECONDS = 'duplicate:delete:delay:error-delay-seconds';
    public const DUPLICATE_DELETE_DELAY_ERROR_LOOKBACK_COUNT = 'duplicate:delete:delay:error-lookback-count';

    public static function getDuplicateDeleteSuccessKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:success";
    }

    public static function getDuplicateDeleteErrorKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:error";
    }
}
