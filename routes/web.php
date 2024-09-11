<?php

use App\Http\Controllers\DuplicatesController;
use App\Http\Controllers\SaveWorkspaceAuthDataController;
use Illuminate\Foundation\Application;
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
    return Inertia::render('Duplicates/Connect', [
        'notionClientId' => env('NOTION_CLIENT_ID'),
        'notionRedirectUri' => app('url')->to('/api/notion/auth/redirect'),
    ]);
});

Route::get('/api/notion/auth/redirect', function () {
    return Inertia::render('API/Notion/Auth/Redirect');
});

Route::post('/workspaces/auth-data', SaveWorkspaceAuthDataController::class)->middleware('api');
