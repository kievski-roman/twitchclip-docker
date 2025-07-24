<?php

use App\Http\Controllers\ClipController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


/*-------------------------------------------------------------------------
| Гостьові маршрути (можна відкрити без логіну)
|------------------------------------------------------------------------*/
Route::view('/', 'welcome');
//Route::get('/test', function () {
//    $start = microtime(true);
//    $html = view('welcome')->render();
//    $duration = round((microtime(true) - $start) * 1000); // мс
//    return "Render time: {$duration}ms";
//});

// або redirect()->route('clip.form')

Route::get('/clip/search',  [ClipController::class,'showForm'])->name('clip.form');
Route::post('/clip/search', [ClipController::class,'searchUserAndRedirect'])->name('clip.get');
Route::get('/clips/result/{username}', [ClipController::class,'getClips'])->name('clip.result');

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

    Route::put('/clips/{clip}/srt', [ClipController::class,'updateSrt'])
        ->middleware('can:update,clip')
        ->name('clips.srt');

    Route::post('/clips/{clip}/hardsubs', [ClipController::class,'generateHardSubs'])
        ->middleware('can:update,clip')
        ->name('clips.hardsubs');

    Route::get('/clips/{clip}/download', [ClipController::class,'downloadHardSub'])
        ->middleware('can:download,clip')
        ->name('clips.download');

    // (опційно) якщо тобі потрібна сторінка профілю, сгенеруй контролер:
    // php artisan make:controller ProfileController
    // і розкоментуй profile-updated


});
/*-------------------------------------------------------------------------
| Breeze auth маршрути (/login, /register, ... )
|------------------------------------------------------------------------*/
require __DIR__.'/auth.php';
