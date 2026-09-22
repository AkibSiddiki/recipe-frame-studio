<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Native\Desktop\Facades\Dialog;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/project/create', [ProjectController::class, 'create'])->name('project.create');
Route::post('/project', [ProjectController::class, 'store'])->name('project.store');
Route::get('/project/{slug}', [ProjectController::class, 'show'])->name('project.show');
Route::get('/project/{slug}/thumbnail', [ProjectController::class, 'thumbnail'])->name('project.thumbnail');
Route::get('/project/{slug}/video-info', [ProjectController::class, 'videoInfo'])->name('project.video-info');
Route::get('/projects/recent', [ProjectController::class, 'recent'])->name('projects.recent');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/ffmpeg-detect', [SettingsController::class, 'detectFfmpeg'])->name('settings.ffmpeg-detect');
Route::get('/settings/ffmpeg-status', [SettingsController::class, 'ffmpegStatus'])->name('settings.ffmpeg-status');

// NativePHP Dialog endpoints with graceful fallbacks
Route::get('/api/dialog/open-video', function () {
    try {
        $path = Dialog::new()
            ->title('Select Cooking Video')
            ->filter('Video Files', ['mp4', 'mov', 'avi', 'mkv', 'webm'])
            ->open();

        return response()->json(['path' => $path]);
    } catch (Throwable $e) {
        return response()->json(['path' => null, 'message' => 'Native dialog unavailable: '.$e->getMessage()]);
    }
})->name('api.dialog.open-video');

Route::get('/api/dialog/open-ffmpeg', function () {
    try {
        $path = Dialog::new()
            ->title('Select FFmpeg Executable')
            ->filter('Executables', ['exe'])
            ->open();

        return response()->json(['path' => $path]);
    } catch (Throwable $e) {
        return response()->json(['path' => null, 'message' => 'Native dialog unavailable: '.$e->getMessage()]);
    }
})->name('api.dialog.open-ffmpeg');
