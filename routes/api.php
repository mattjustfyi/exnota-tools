<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkspaceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// TODO: Test policy
Route::get('/test/workspace/{workspace}/refresh', [WorkspaceController::class, 'refresh'])
    ->name('api.test.workspace.refresh')
    ->middleware('can:view,workspace');
