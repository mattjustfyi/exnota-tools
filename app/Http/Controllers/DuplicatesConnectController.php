<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DuplicatesConnectController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user) {
            $latestWorkspace = $user->workspaces()->latest('updated_at')->first();
            if ($latestWorkspace) {
                return to_route('duplicates.workspace', ['workspace' => $latestWorkspace->id]);
            }
        }

        $workspaces = $user ? $user->workspaces : null;

        return Inertia::render('Duplicates/Connect', [
            'user' => $user,
            'workspaces' => $workspaces,
            'notionClientId' => env('NOTION_CLIENT_ID'),
            'notionRedirectUri' => url('/api/notion/auth/redirect'),
        ]);
    }
}
