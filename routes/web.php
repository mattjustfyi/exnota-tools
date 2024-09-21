<?php

use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\DuplicatesCreateController;
use App\Http\Controllers\DuplicateRequestDeleteController;
use App\Http\Controllers\NotionAuthSaveController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceRefreshController;
use App\Http\Middleware\CreateDuplicateRequestPolicyMiddleware;
use App\Jobs\WorkspaceRefresh;
use App\Models\ToolDuplicateRequest;
use App\Models\Workspace;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
Route::get('/duplicates', [DuplicatesController::class, 'show'])->name('duplicates');;

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

    Route::get('/duplicates/workspace/{workspace}', [WorkspaceController::class, 'show'])
        ->name('duplicates.workspace')
        ->middleware('can:view,workspace');

    Route::post('/test/workspace/{workspace}/refresh', [WorkspaceRefreshController::class, 'refresh'])
        ->name('api.test.workspace.refresh')
        ->middleware('can:update,workspace');

    Route::post('/duplicates/request', [DuplicatesCreateController::class, 'create'])
        ->name('duplicates.create-request')
        ->middleware(CreateDuplicateRequestPolicyMiddleware::class);

    Route::post('/duplicates/request/{toolDuplicateRequest}/delete', [DuplicateRequestDeleteController::class, 'delete'])
        ->name('duplicates.delete-request')
        ->middleware('can:update,toolDuplicateRequest');

    Route::get('/duplicates/request/{toolDuplicateRequest}/confirm', function (ToolDuplicateRequest $toolDuplicateRequest) {
        $toolDuplicateRequest->loadMissing('notionObject');

        // Couldn't get back slashes escaping properly, so subtituted with wildcards.
        $incompleteJobs = DB::table('jobs')
            ->whereRaw('payload ~ ?', '"displayName":"App.{2}Jobs.{2}DuplicateSearch"')
            ->whereRaw('payload ~ ?', ';s:[0-9]+:.{1}"App.{2}Models.{2}ToolDuplicateRequest.{1}";s:[0-9]+:.{1}"id.{1}";i:' . strval($toolDuplicateRequest->id) . ';')
            ->count();

        $searchInProgress = $incompleteJobs > 0;

        return Inertia::render('Duplicates/Request/Confirm', [
            'toolDuplicateRequest' => $toolDuplicateRequest,
            'searchInProgress' => $searchInProgress,
        ]);
    })->name('duplicates.request.confirm')->middleware('can:view,toolDuplicateRequest');
});
