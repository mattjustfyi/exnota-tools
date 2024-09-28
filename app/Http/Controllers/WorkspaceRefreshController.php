<?php

namespace App\Http\Controllers;

use App\Enums\SyncStatus;
use App\Jobs\WorkspaceRefresh;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceRefreshController extends Controller
{
    /**
     * Refresh the specified workspace.
     *
     * @param  Workspace  $workspace
     * @return RedirectResponse
     */
    public function refresh(Workspace $workspace): RedirectResponse
    {
        $workspace->update(['sync_status' => SyncStatus::QUEUED]);
        WorkspaceRefresh::dispatch($workspace);

        return redirect()->route('duplicates.workspace', ['workspace' => $workspace->id]);
    }
}
