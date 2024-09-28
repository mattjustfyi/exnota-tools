<?php

namespace App\Enums;

enum SyncStatus: string
{
    case QUEUED = 'queued';
    case IN_PROGRESS = 'in_progress';
    case COMPLETE = 'complete';
    case FAILED = 'failed';
}
