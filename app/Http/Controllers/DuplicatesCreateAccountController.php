<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DuplicatesCreateAccountController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user) {
            if (!empty($user->consent)) {
                $latestWorkspace = $user->workspaces()->latest('updated_at')->first();
                if ($latestWorkspace) {
                    return to_route('duplicates.workspace', ['workspace' => $latestWorkspace->id]);
                }
            }
        }

        return Inertia::render('Duplicates/CreateAccount');
    }
}
