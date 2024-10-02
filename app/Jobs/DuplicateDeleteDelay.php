<?php

namespace App\Jobs;

use App\Constants\CacheKey;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class DuplicateDeleteDelay
{
    public static function getDelay(): int
    {
        $now = Carbon::now();
        $twoMinutesAgo = $now->subMinutes(2)->timestamp;

        $successCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_SUCCESS, $twoMinutesAgo, '+inf');
        $errorCount = Redis::zcount(CacheKey::DUPLICATE_DELETE_ERROR, $twoMinutesAgo, '+inf');

        $totalCount = $successCount + $errorCount;

        if ($totalCount === 0) {
            return 0;
        }

        $errorRatio = $errorCount / $totalCount;

        return $errorRatio > 0.1 ? 2000 : 0;
    }
}
