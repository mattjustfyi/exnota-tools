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

        return response()->json([
            'message' => 'Request received successfully',
            'data' => $validatedData
        ]);
    }
}
