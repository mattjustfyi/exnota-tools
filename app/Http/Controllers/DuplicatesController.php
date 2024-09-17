<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DuplicatesController extends Controller
{
    public function show(Request $request, User $user)
    {
        // Check if the authenticated user matches the requested user
        if (Auth::id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if the user has a workspace with Notion objects
        $workspace = $user->workspaces()->first();
        if (!$workspace || !$workspace->notionObjects()->exists()) {
            abort(404, 'No Notion objects found for this user.');
        }

        return Inertia::render('Duplicates', [
            'user' => $user
        ]);
    }
}
