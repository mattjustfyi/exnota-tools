<?php

namespace App\Http\Controllers;

use App\Constants\QueueConstants;
use App\Jobs\DuplicateDelete;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\RedirectResponse;

class DuplicateRequestDeleteController extends Controller
{
    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        DuplicateDelete::dispatch($toolDuplicateRequest)->onQueue(QueueConstants::DELETE_QUEUE);
        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
