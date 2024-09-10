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
        // $validatedData = $request->validated();

        // $workspace = Workspace::updateOrCreateFromAuthData($validatedData['authData']);

        // return response()->json([
        //     'message' => 'Workspace created or updated successfully',
        //     'workspace_id' => $workspace->id
        // ]);

        return response()->json('hi');
    }
}
