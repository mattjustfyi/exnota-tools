<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DuplicatesConnectController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $workspaces = $user ? $user->workspaces : null;

        return Inertia::render('Duplicates/Connect', [
            'user' => $user,
            'workspaces' => $workspaces,
            'notionClientId' => env('NOTION_CLIENT_ID'),
            'notionRedirectUri' => url('/api/notion/auth/redirect'),
        ]);
    }
}
