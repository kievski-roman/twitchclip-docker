<?php

use App\Http\Controllers\ClipController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


/*-------------------------------------------------------------------------
| Гостьові маршрути (можна відкрити без логіну)
|------------------------------------------------------------------------*/
Route::view('/', 'welcome');


// або redirect()->route('clip.form')

Route::get('/clip/search',  [ClipController::class,'showForm'])->name('clip.form');
Route::post('/clip/search', [ClipController::class,'searchUserAndRedirect'])->name('clip.get');
Route::get('/clips/result/{username}', [ClipController::class,'getClips'])->name('clip.result');


//Route::view('/studio-test', 'studio')->name('studio.test');

/*-------------------------------------------------------------------------
| Авторизовані маршрути
|------------------------------------------------------------------------*/
Route::middleware('auth')->group(function () {

    // Dashboard Breeze (можеш замінити на твою сторінку)
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    // Кліпи поточного користувача
    Route::post('/clip/download', [ClipController::class,'download'])
        ->name('clip.download');

    Route::get('/clips', [ClipController::class,'index'])
        ->name('clips.index');
    Route::get('/clips/{clip}', [ClipController::class,'show'])
        ->middleware('can:view,clip')
        ->name('clips.show');

    Route::put('/clips/{clip}/vtt', [ClipController::class,'updateVtt'])
        ->middleware('can:update,clip')
        ->name('clips.vtt');

    Route::post('/clips/{clip}/hardsubs', [ClipController::class,'generateHardSubs'])
        ->middleware('can:update,clip')
        ->name('clips.hardsubs');

    Route::get('/clips/{clip}/download', [ClipController::class,'downloadHardSub'])
        ->middleware('can:download,clip')
        ->name('clips.download');

    Route::patch('clips/{clip}/style', [ClipController::class, 'updateStyle'])
        ->name('clips.style');
    // (опційно) якщо тобі потрібна сторінка профілю, сгенеруй контролер:
    // php artisan make:controller ProfileController
    // і розкоментуй profile-updated


});


/*-------------------------------------------------------------------------
| Breeze auth маршрути (/login, /register, ... )
|------------------------------------------------------------------------*/
require __DIR__.'/auth.php';

