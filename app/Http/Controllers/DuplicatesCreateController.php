<?php

namespace App\Http\Controllers;

use App\Jobs\DuplicateSearch;
use App\Models\NotionObject;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DuplicatesCreateController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'notion_object_id' => 'required|integer',
        ]);

        $notionObject = NotionObject::where('id', $request->notion_object_id)->first();

        if ($request->user()->cant('view', $notionObject)) {
            return response()->json([
                'message' => 'You do not have permission to access this Notion object',
            ], 403);
        }

        // TODO: Update this to not create a new one if there's a job already
        // existing for a notion object id (no point creating a new job if one)
        // already exists.
        // TODO: Then maybe redirect to the existing request page?
        $existingDuplicateRequest = ToolDuplicateRequest::where('notion_object_id', $request->notion_object_id)
            ->orderByDesc('created_at')
            ->first();

        if ($existingDuplicateRequest) {
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

        DuplicateSearch::dispatch($toolDuplicateRequest);

        // TODO: If user has already confirmed, instead redirect to /search
        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
