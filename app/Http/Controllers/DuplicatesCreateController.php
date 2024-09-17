<?php

namespace App\Http\Controllers;

use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DuplicatesCreateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'notion_object_id' => 'required|string',
        ]);

        $toolDuplicateRequest = ToolDuplicateRequest::create([
            'notion_object_id' => $request->notion_object_id,
        ]);

        return response()->json([
            'message' => 'Tool duplicate request created successfully',
            'data' => $toolDuplicateRequest
        ], 201);
    }
}
