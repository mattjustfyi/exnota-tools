<?php

namespace App\Http\Controllers;

use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use App\Enums\DeleteLogic;
use Illuminate\Http\RedirectResponse;

class DuplicatesUpdateController extends Controller
{
    public function update(Request $request, ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        $request->validate([
            'delete_logic' => ['required', 'string', 'in:' . implode(',', DeleteLogic::getValues())],
        ]);

        $toolDuplicateRequest->update([
            'delete_logic' => DeleteLogic::from($request->delete_logic),
        ]);

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
