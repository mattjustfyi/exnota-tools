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
        return Inertia::render('Duplicates', [
            'user' => $user
        ]);
    }
}
