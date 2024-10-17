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

        if ($existingDuplicateRequest && $existingDuplicateRequest->search_ended_at === null) {

            return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $existingDuplicateRequest->id]);
        }

        $toolDuplicateRequest = ToolDuplicateRequest::create([
            'notion_object_id' => $request->notion_object_id,
        ]);

        Log::debug('Dispatching DuplicateSearch job for request: ' . $toolDuplicateRequest->id);
        DuplicateSearch::dispatch($request->user(), $toolDuplicateRequest)->onQueue('default');

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
