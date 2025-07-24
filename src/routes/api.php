<?php

use App\Http\Controllers\ClipApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/clips/{clip}/status', [ClipApiController::class, 'status'])
    ->name('api.clips.status');
