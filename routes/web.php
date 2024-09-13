<?php

use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\NotionAuthSaveController;
use App\Models\Workspace;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Group of routes protected by auth:sanctum
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::get('/duplicates', [DuplicatesController::class, 'show']);

Route::get('/duplicates/connect', function () {
    $user = Auth::user();
    $workspaces = $user ? $user->workspaces : null;

    return Inertia::render('Duplicates/Connect', [
        'user' => $user,
        'workspaces' => $workspaces,
        'notionClientId' => env('NOTION_CLIENT_ID'),
        'notionRedirectUri' => app('url')->to('/api/notion/auth/redirect'),
    ]);
});

Route::get('/api/notion/auth/redirect', function () {
    return Inertia::render('API/Notion/Auth/Redirect');
});

Route::post('/api/notion/auth/save', NotionAuthSaveController::class)->middleware('api');

Route::get('/duplicates/workspace/{workspace}', function (Workspace $workspace) {
    return Inertia::render('Duplicates/Workspace', [
        'workspaceId' => $workspace->id,
    ]);
})->name('duplicates.workspace')->middleware('can:view,workspace');
