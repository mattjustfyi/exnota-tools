<?php

namespace App\Http\Controllers;

use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Enums\DeleteLogic;

class DuplicatesUpdateController extends Controller
{
    public function update(Request $request, ToolDuplicateRequest $toolDuplicateRequest): JsonResponse
    {
        $request->validate([
            'delete_logic' => ['required', 'string', 'in:' . implode(',', DeleteLogic::getValues())],
        ]);

        $toolDuplicateRequest->update([
            'delete_logic' => DeleteLogic::from($request->delete_logic),
        ]);

        return response()->json([
            'message' => 'Tool duplicate request updated successfully',
            'tool_duplicate_request' => $toolDuplicateRequest,
        ]);
    }
}
