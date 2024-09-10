<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaveWorkspaceAuthDataController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/workspaces/{workspace}/auth-data', SaveWorkspaceAuthDataController::class);
