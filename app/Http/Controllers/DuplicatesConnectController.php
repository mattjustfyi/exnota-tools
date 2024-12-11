<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DuplicatesConnectController extends Controller
{
    public function show()
    {
        // Client pings periodically with status 'waiting'. Ignore those.
        if (!request()->has('status')) {
            // This message will appear a second time after the user has
            // connected their Notion account, because the client reloads the
            // page.
            Log::channel('slack')->info('[User] Pressed start.');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user) {
            if (empty($user->consent)) {
                return to_route('duplicates.create-account');
            }

            $latestWorkspace = $user->workspaces()->latest('updated_at')->first();
            if ($latestWorkspace) {
                return to_route('duplicates.workspace', ['workspace' => $latestWorkspace->id]);
            }
        }

        $workspaces = $user ? $user->workspaces : null;

        return Inertia::render('Duplicates/Connect', [
            'notionClientId' => env('NOTION_CLIENT_ID'),
            'notionRedirectUri' => url('/api/notion/auth/redirect'),
        ]);
    }
}
