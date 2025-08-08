<?php

use App\Http\Controllers\ClipApiController;
use App\Http\Controllers\ClipController;
use App\Http\Controllers\WhisperProxyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/clips/{clip}/status', [ClipApiController::class, 'status'])
    ->name('api.clips.status');
Route::post('/transcribe', [WhisperProxyController ::class, 'transcribe']);
Route::get('/clips/{username}', [ClipApiController::class, 'getClipsJson'])
    ->name('api.clips.index');

