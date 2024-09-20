<?php

namespace App\Http\Controllers;

use App\Jobs\DuplicateDelete;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\RedirectResponse;

class DuplicateRequestDeleteController extends Controller
{
    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        DuplicateDelete::dispatch($toolDuplicateRequest)->onQueue('delete-1');
        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
