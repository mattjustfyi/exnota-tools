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

        $workspace = Workspace::create([
            'auth_data' => $validatedData['authData']
        ]);

        return response()->json([
            'message' => 'New workspace created successfully',
            'workspace_id' => $workspace->id
        ]);
    }
}
