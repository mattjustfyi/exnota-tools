<?php

use App\Events\SpotifyDashboardCurrentPlayingUpdated;
use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\DuplicatesConnectController;
use App\Http\Controllers\DuplicateCreateController;
use App\Http\Controllers\DuplicateDeleteController;
use App\Http\Controllers\DuplicateCancelController;
use App\Http\Controllers\DuplicatesConsentController;
use App\Http\Controllers\DuplicatesCreateAccountController;
use App\Http\Controllers\DuplicatesUpdateController;
use App\Http\Controllers\NotionAuthSaveController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceRefreshController;
use App\Http\Middleware\CreateDuplicateRequestPolicyMiddleware;
use App\Models\SpotifyDashboard;
use App\Models\ToolDuplicateRequest;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Public routes
Route::get('/duplicates', [DuplicatesController::class, 'show'])->name('duplicates');;
Route::get('/duplicates/connect', [DuplicatesConnectController::class, 'show'])->name('duplicates.connect');
Route::get('/api/notion/auth/redirect', function () {
    return Inertia::render('API/Notion/Auth/Redirect');
});

Route::get('/music-dashboard', function () {
    return Inertia::render('MusicDashboard');
})->name('music-dashboard');

// TODO: Remove after testing
Route::post('/spotify/test-event', function () {
    $spotifyDashboard = SpotifyDashboard::where('active', true)->first();

    SpotifyDashboardCurrentPlayingUpdated::dispatch($spotifyDashboard);

    return response()->json(['success' => true]);
});

// TODO: This probably shouldn't be "api"
Route::post('/api/notion/auth/save', NotionAuthSaveController::class)->middleware('api');

Route::get('/spotify/dashboard/{spotifyDashboard}/widget', function (SpotifyDashboard $spotifyDashboard) {
    return Inertia::render('Spotify/Dashboard/Widget', [
        'spotifyDashboard' => $spotifyDashboard,
    ]);
})->name('spotify.embed.widget');

// Routes requiring login
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/duplicates/create-account', [DuplicatesCreateAccountController::class, 'show'])
        ->name('duplicates.create-account');

    Route::post('/duplicates/consent', DuplicatesConsentController::class)
        ->name('duplicates.consent');

    // Routes requiring consent
    Route::middleware('check.consent')->group(function () {
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

            return Inertia::render('Duplicates/Request/Confirm', [
                'toolDuplicateRequest' => $toolDuplicateRequest,
            ]);
        })->name('duplicates.request.confirm')->middleware('can:view,toolDuplicateRequest');

        Route::post('/duplicates/request/{toolDuplicateRequest}/cancel', [DuplicateCancelController::class, 'cancel'])
            ->name('duplicates.cancel-request')
            ->middleware('can:update,toolDuplicateRequest');

        Route::patch('/duplicates/request/{toolDuplicateRequest}', [DuplicatesUpdateController::class, 'update'])
            ->name('duplicates.update-request')
            ->middleware('can:update,toolDuplicateRequest');
    });
});
