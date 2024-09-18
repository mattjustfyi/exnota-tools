<?php

namespace App\Http\Controllers;

use App\Jobs\WorkspaceRefresh;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class WorkspaceRefreshController extends Controller
{
    /**
     * Refresh the specified workspace.
     *
     * @param  Workspace  $workspace
     * @return JsonResponse
     */
    public function refresh(Workspace $workspace)
    {
        WorkspaceRefresh::dispatch($workspace);

        return to_route('duplicates.workspace', ['workspace' => $workspace->id]);
    }
}
