<?php

use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\NotionAuthSaveController;
use App\Http\Controllers\WorkspaceController;
use App\Jobs\WorkspaceRefresh;
use App\Models\Workspace;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

// Public routes
Route::get('/duplicates', [DuplicatesController::class, 'show']);

Route::get('/duplicates/connect', function () {
    $user = Auth::user();
    $workspaces = $user ? $user->workspaces : null;

    return Inertia::render('Duplicates/Connect', [
        'user' => $user,
        'workspaces' => $workspaces,
        'notionClientId' => env('NOTION_CLIENT_ID'),
        'notionRedirectUri' => url('/api/notion/auth/redirect'),
    ]);
});

Route::get('/api/notion/auth/redirect', function () {
    return Inertia::render('API/Notion/Auth/Redirect');
});

// TODO: This probably shouldn't be "api"
Route::post('/api/notion/auth/save', NotionAuthSaveController::class)->middleware('api');

// Routes protected by auth:sanctum
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/duplicates/workspace/{workspace}', function (Workspace $workspace) {
        $workspace->load('notionObjects');
        $refreshInProgress = DB::table('jobs')
            ->where('queue', 'default')
            ->whereRaw("JSON_EXTRACT(payload, '$.data.commandName') = ?", ['App\\Jobs\\WorkspaceRefresh'])
            ->whereRaw("JSON_EXTRACT(payload, '$.data.command') LIKE ?", ['%"id";i:' . $workspace->id . ';%'])
            ->count() > 0;
        return Inertia::render('Duplicates/Workspace', [
            'workspace' => $workspace,
            'refreshInProgress' => $refreshInProgress,
        ]);
    })->name('duplicates.workspace')->middleware('can:view,workspace');

    Route::post('/test/workspace/{workspace}/refresh', [WorkspaceController::class, 'refresh'])
        ->name('api.test.workspace.refresh')
        ->middleware('can:update,workspace');
});
