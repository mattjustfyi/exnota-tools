<?php

namespace App\Constants;

class CacheKey
{
    public const DUPLICATE_DELETE_DELAY = 'duplicate_delete-delay';

    public static function getDuplicateDeleteSuccessKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:success";
    }

    public static function getDuplicateDeleteErrorKey(int $workspaceId): string
    {
        return "duplicate:delete:w:{$workspaceId}:error";
    }
}
