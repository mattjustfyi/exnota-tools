<?php

namespace App\Http\Controllers;

use App\Jobs\DuplicateSearch;
use App\Models\NotionObject;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateCreateController extends Controller
{
    public function create(Request $request)
    {
        // Simulate 2 seconds of lag
        sleep(2);

        $request->validate([
            'notion_object_id' => 'required|integer',
        ]);

        $notionObject = NotionObject::where('id', $request->notion_object_id)->first();

        if ($request->user()->cant('view', $notionObject)) {
            return response()->json([
                'message' => 'You do not have permission to access this Notion object',
            ], 403);
        }

        $existingDuplicateRequest = ToolDuplicateRequest::where('notion_object_id', $request->notion_object_id)
            ->latest()
            ->first();

        if ($existingDuplicateRequest) {
            // Determine if we should redirect to the last request by whether
            // there are any incomplete jobs for the last request. This seems
            // the most inline with the user's potential actions: If there are
            // no jobs, the user needs to be able to search again. While there
            // are jobs, they should wait.
            //
            // I attempted to use the existence of `search_ended_at` but that
            // fails when there is a bug and `search_ended_at` is never set. And
            // probably also if the job fails. 

            // Couldn't get back slashes escaping properly, so subtituted with wildcards.
            $incompleteJobs = DB::table('jobs')
                ->whereRaw('payload ~ ?', '"displayName":"App.{2}Jobs.{2}DuplicateSearch"')
                ->whereRaw('payload ~ ?', ';s:[0-9]+:.{1}"App.{2}Models.{2}ToolDuplicateRequest.{1}";s:[0-9]+:.{1}"id.{1}";i:' . strval($existingDuplicateRequest->id) . ';')
                ->count();

            if ($incompleteJobs > 0) {
                return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $existingDuplicateRequest->id]);
            }
        }

        $toolDuplicateRequest = ToolDuplicateRequest::create([
            'notion_object_id' => $request->notion_object_id,
        ]);

        Log::debug('Dispatching DuplicateSearch job for request: ' . $toolDuplicateRequest->id);
        DuplicateSearch::dispatch($toolDuplicateRequest)->onQueue('default');

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
