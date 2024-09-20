<?php

namespace App\Http\Controllers;

use App\Jobs\DuplicateDelete;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\RedirectResponse;

class DuplicateRequestDeleteController extends Controller
{
    private const DELETE_QUEUE = 'delete-1';

    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        DuplicateDelete::dispatch($toolDuplicateRequest)->onQueue(self::DELETE_QUEUE);
        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
