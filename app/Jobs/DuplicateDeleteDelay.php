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
        if ($toolDuplicateRequest->delete_list_count > 500) {
            return 5000;
        }

        $now = Carbon::now();
        $twoMinutesAgo = $now->subMinutes(2)->timestamp;

        $successCount = Redis::zcount(CacheKey::getDuplicateDeleteSuccessKey($workspaceId), $twoMinutesAgo, '+inf');
        $errorCount = Redis::zcount(CacheKey::getDuplicateDeleteErrorKey($workspaceId), $twoMinutesAgo, '+inf');

        $totalCount = $successCount + $errorCount;

        if ($totalCount === 0) {
            return 0;
        }

        $errorRatio = $errorCount / $totalCount;

        Log::debug("Error ratio for workspace $workspaceId: " . number_format($errorRatio * 100, 1) . '% (' . $errorCount . '/' . $totalCount . ')');
        if ($errorRatio >= 0.04) {
            return 8000;
        } elseif ($errorRatio >= 0.01) {
            return 4000;
        } else {
            return 0;
        }
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
