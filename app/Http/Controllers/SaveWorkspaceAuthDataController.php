<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SaveWorkspaceAuthDataController extends Controller
{
    //
}
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
    public function __invoke(SaveWorkspaceAuthDataRequest $request, Workspace $workspace): JsonResponse
    {
        $validatedData = $request->validated();

        $workspace->update([
            'auth_data' => $validatedData['authData'],
        ]);

        return response()->json(['message' => 'Workspace auth data saved successfully']);
    }
}
