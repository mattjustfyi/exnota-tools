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
        $workspace = Workspace::create();

        return response()->json([
            'message' => 'Workspace created successfully',
            'data' => [
                'id' => $workspace->id,
            ]
        ], 201);
    }
}
