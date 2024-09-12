<?php

namespace App\Http\Controllers;

use App\Jobs\WorkspaceRefresh;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    /**
     * Refresh the specified workspace.
     *
     * @param  Workspace  $workspace
     * @return JsonResponse
     */
    public function refresh(Workspace $workspace): JsonResponse
    {
        WorkspaceRefresh::dispatch($workspace);

        return response()->json(['message' => 'Workspace refresh job dispatched successfully']);
    }
}
