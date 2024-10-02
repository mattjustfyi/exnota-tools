<?php

namespace App\Jobs;

use App\Constants\CacheKey;
use App\Constants\QueueConstants;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DuplicateDeleteDelay
{
    public static function getDelay(string $queue): int
    {
        $now = Carbon::now();
        $twoMinutesAgo = $now->subMinutes(2)->timestamp;

        $successCount = 0;
        $errorCount = 0;
        if ($queue === QueueConstants::DELETE_QUEUE_1) {
            $successCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_QUEUE_1_SUCCESS, $twoMinutesAgo, '+inf');
            $errorCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_QUEUE_1_ERROR, $twoMinutesAgo, '+inf');
        } elseif ($queue === QueueConstants::DELETE_QUEUE_2) {
            $successCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_QUEUE_2_SUCCESS, $twoMinutesAgo, '+inf');
            $errorCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_QUEUE_2_ERROR, $twoMinutesAgo, '+inf');
        } else {
            throw new \InvalidArgumentException('Invalid queue');
        }

        $totalCount = $successCount + $errorCount;

        if ($totalCount === 0) {
            return 0;
        }

        $errorRatio = $errorCount / $totalCount;

        Log::debug("Error ratio for $queue: " . number_format($errorRatio * 100, 1) . '% (' . $errorCount . '/' . $totalCount . ')');
        if ($errorRatio >= 0.04) {
            return 8000;
        } elseif ($errorRatio >= 0.01) {
            return 4000;
        } else {
            return 0;
        }
    }

    public static function logSuccess(string $queue): void
    {
        $now = Carbon::now()->timestamp;

        if ($queue === QueueConstants::DELETE_QUEUE_1) {
            Redis::zadd(CacheKey::DUPLICATE_DELETE_QUEUE_1_SUCCESS, $now, $now);
        } elseif ($queue === QueueConstants::DELETE_QUEUE_2) {
            Redis::zadd(CacheKey::DUPLICATE_DELETE_QUEUE_2_SUCCESS, $now, $now);
        } else {
            throw new \InvalidArgumentException('Invalid queue');
        }
    }

    public static function logError(string $queue): void
    {
        $now = Carbon::now()->timestamp;

        if ($queue === QueueConstants::DELETE_QUEUE_1) {
            Redis::zadd(CacheKey::DUPLICATE_DELETE_QUEUE_1_ERROR, $now, $now);
        } elseif ($queue === QueueConstants::DELETE_QUEUE_2) {
            Redis::zadd(CacheKey::DUPLICATE_DELETE_QUEUE_2_ERROR, $now, $now);
        } else {
            throw new \InvalidArgumentException('Invalid queue');
        }
    }
}
