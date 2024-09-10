<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class DuplicatesController extends Controller
{
    public function show(User $user)
    {
        return Inertia::render('Duplicates', [
            'user' => $user
        ]);
    }
}
