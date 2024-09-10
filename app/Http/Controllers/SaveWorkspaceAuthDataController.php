<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveWorkspaceAuthDataRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class SaveWorkspaceAuthDataController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SaveWorkspaceAuthDataRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // Logic to create or consolidate workspace based on authData
        // This is a placeholder and should be replaced with actual implementation
        $workspace = Workspace::updateOrCreate(
            ['some_unique_identifier' => $validatedData['authData']['unique_id'] ?? null],
            ['auth_data' => $validatedData['authData']]
        );

        return response()->json([
            'message' => 'Workspace auth data saved successfully',
            'workspace_id' => $workspace->id
        ]);
    }
}
