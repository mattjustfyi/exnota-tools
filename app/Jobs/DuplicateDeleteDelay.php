<?php

namespace App\Jobs;

use App\Constants\CacheKey;
use App\Models\ToolDuplicateRequest;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DuplicateDeleteDelay
{
    public static function getDelay(int $workspaceId, ToolDuplicateRequest $toolDuplicateRequest): int
    {
        $delay = 0;

        $deleteListCountMin = Redis::get(CacheKey::DUPLICATE_DELETE_DELAY_SIZE_MINIMUM) ?? 500;
        $deleteListCountDelaySeconds = Redis::get(CacheKey::DUPLICATE_DELETE_DELAY_SIZE_DELAY_SECONDS) ?? 5000;
        $errorLookbackMinutes = Redis::get(CacheKey::DUPLICATE_DELETE_DELAY_ERROR_LOOKBACK_MINUTES) ?? 10;
        $errorFoundDelaySeconds = Redis::get(CacheKey::DUPLICATE_DELETE_DELAY_ERROR_DELAY_SECONDS) ?? 10000;

        if ($toolDuplicateRequest->delete_list_count > $deleteListCountMin) {
            $delay = max($delay, $deleteListCountDelaySeconds);
        }

        $now = Carbon::now();
        $lookbackTime = $now->subMinutes($errorLookbackMinutes)->timestamp;

        $errorCount = Redis::zcount(CacheKey::getDuplicateDeleteErrorKey($workspaceId), $lookbackTime, '+inf');

        if ($errorCount >= 1) {
            $delay = max($delay, $errorFoundDelaySeconds);
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
