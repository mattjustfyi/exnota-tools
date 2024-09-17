<?php

namespace App\Http\Controllers;

use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DuplicatesCreateController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'notion_object_id' => 'required|integer',
        ]);

        // TODO: Update this to not create a new one if there's a job already
        // existing for a notion object id (no point creating a new job if one)
        // already exists.
        // TODO: Then maybe redirect to the existing request page?
        $existingRequest = ToolDuplicateRequest::where('notion_object_id', $request->notion_object_id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'A request has already been created for this Notion object recently',
                'data' => $existingRequest
            ], 409);
        }

        $toolDuplicateRequest = ToolDuplicateRequest::create([
            'notion_object_id' => $request->notion_object_id,
        ]);

        return response()->json([
            'message' => 'Tool duplicate request created successfully',
            'data' => $toolDuplicateRequest
        ], 201);
    }
}
