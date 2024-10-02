<?php

namespace App\Jobs;

use App\Constants\CacheKey;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DuplicateDeleteDelay
{
    public static function getDelay(int $workspaceId, ToolDuplicateRequest $toolDuplicateRequest): int
    {
        $delay = 0;

        if ($toolDuplicateRequest->delete_list_count > 500) {
            $delay = max($delay, 5000);
        }

        $now = Carbon::now();
        $tenMinutesAgo = $now->subMinutes(10)->timestamp;

        $errorCount = Redis::zcount(CacheKey::getDuplicateDeleteErrorKey($workspaceId), $tenMinutesAgo, '+inf');

        if ($errorCount >= 1) {
            $delay = max($delay, 10000);
        }

        return $delay;
    }

    public static function logSuccess(int $workspaceId): void
    {
        $now = Carbon::now()->timestamp;
        Redis::zadd(CacheKey::getDuplicateDeleteSuccessKey($workspaceId), $now, $now);
    }

    public static function logError(int $workspaceId): void
    {
        $now = Carbon::now()->timestamp;
        Redis::zadd(CacheKey::getDuplicateDeleteErrorKey($workspaceId), $now, $now);
    }
}
