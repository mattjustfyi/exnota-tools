<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\ToolDuplicateRequest;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function show(Workspace $workspace)
    {
        $workspace->load('notionObjects');

        // Couldn't get back slashes escaping properly, so subtituted with wildcards.
        $incompleteJobs = DB::table('jobs')
            ->whereRaw('payload ~ ?', '"displayName":"App.{2}Jobs.{2}WorkspaceRefresh"')
            ->whereRaw('payload ~ ?', 'App.{2}Models.{2}Workspace.{1}";s:[0-9]+:.{1}"id.{1}";i:' . strval($workspace->id))
            ->count();

        // Start showing results if <= 4 jobs remaining AND > 1 result
        $showResults = $incompleteJobs <= 4;

        $toolDuplicateRequests = ToolDuplicateRequest::whereIn('notion_object_id', $workspace->notionObjects->pluck('id'))
            ->get();

        return Inertia::render('Duplicates/Workspace', [
            'workspace' => $workspace,
            'refreshInProgress' => $incompleteJobs > 0,
            'showResults' => $showResults,
        ]);
    }
}
