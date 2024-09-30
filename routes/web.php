<?php

use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\DuplicateCreateController;
use App\Http\Controllers\DuplicateDeleteController;
use App\Http\Controllers\DuplicateCancelController;
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

    Route::post('/duplicates/request', [DuplicateCreateController::class, 'create'])
        ->name('duplicates.create-request')
        ->middleware(CreateDuplicateRequestPolicyMiddleware::class);

    Route::post('/duplicates/request/{toolDuplicateRequest}/delete', [DuplicateDeleteController::class, 'delete'])
        ->name('duplicates.delete-request')
        ->middleware('can:update,toolDuplicateRequest');

    Route::get('/duplicates/request/{toolDuplicateRequest}/confirm', function (ToolDuplicateRequest $toolDuplicateRequest) {
        $toolDuplicateRequest->loadMissing('notionObject');

        // TODO: Get sample more performantly
        $sorted_duplicates = is_array($toolDuplicateRequest->duplicates) ? $toolDuplicateRequest->duplicates : [];
        usort($sorted_duplicates, function ($a, $b) {
            return (is_array($b->delete) ? count($b->delete) : 0) - (is_array($a->delete) ? count($a->delete) : 0);
        });
        $duplicates_sample = array_slice($sorted_duplicates, 0, 5);

        $delete_results_count = is_array($toolDuplicateRequest->delete_results) ? count($toolDuplicateRequest->delete_results) : 0;

        $not_deleted_ids = is_array($toolDuplicateRequest->delete_list) && is_array($toolDuplicateRequest->delete_results)
            ? array_diff($toolDuplicateRequest->delete_list, $toolDuplicateRequest->delete_results)
            : [];
        $not_deleted_ids_count = count($not_deleted_ids);

        return Inertia::render('Duplicates/Request/Confirm', [
            'toolDuplicateRequest' => $toolDuplicateRequest,
            // The number of pages that have been deleted
            'delete_results_count' => $delete_results_count,
            'duplicates_sample' => $duplicates_sample,
            'not_deleted_ids_count' => $not_deleted_ids_count,
        ]);
    })->name('duplicates.request.confirm')->middleware('can:view,toolDuplicateRequest');

    Route::post('/duplicates/request/{toolDuplicateRequest}/cancel', [DuplicateCancelController::class, 'cancel'])
        ->name('duplicates.cancel-request')
        ->middleware('can:update,toolDuplicateRequest');
});
