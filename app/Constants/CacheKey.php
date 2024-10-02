<?php

namespace App\Constants;

class CacheKey
{
    public const DUPLICATE_DELETE_DELAY = 'duplicate_delete-delay';
    public const DUPLICATE_DELETE_LIST_COUNT_MIN = 'duplicate_delete_list_count_min';
    public const DUPLICATE_DELETE_LIST_COUNT_DELAY = 'duplicate_delete_list_count_delay';
    public const DUPLICATE_DELETE_ERROR_LOOKBACK_MINUTES = 'duplicate_delete_error_lookback_minutes';
    public const DUPLICATE_DELETE_ERROR_DELAY = 'duplicate_delete_error_delay';

    public static function getDuplicateDeleteSuccessKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:success";
    }

    public static function getDuplicateDeleteErrorKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:error";
    }
}
