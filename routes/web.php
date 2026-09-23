<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Native\Desktop\Dialog;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/project/create', [ProjectController::class, 'create'])->name('project.create');
Route::post('/project', [ProjectController::class, 'store'])->name('project.store');
Route::get('/project/{slug}', [ProjectController::class, 'show'])->name('project.show');
Route::delete('/project/{slug}', [ProjectController::class, 'destroy'])->name('project.destroy');
Route::get('/project/{slug}/thumbnail', [ProjectController::class, 'thumbnail'])->name('project.thumbnail');
Route::get('/project/{slug}/video-info', [ProjectController::class, 'videoInfo'])->name('project.video-info');
Route::get('/project/{slug}/frames', [ProjectController::class, 'frames'])->name('project.frames');
Route::post('/project/{slug}/extract-frames', [ProjectController::class, 'extractFrames'])->name('project.extract-frames');
Route::get('/project/{slug}/frame-image/{filename}', [ProjectController::class, 'frameImage'])->name('project.frame.image');
Route::post('/project/{slug}/frames/toggle', [ProjectController::class, 'toggleFrame'])->name('project.frames.toggle');
Route::post('/project/{slug}/frames/capture-at', [ProjectController::class, 'captureAtTimestamp'])->name('project.frames.capture-at');
Route::delete('/project/{slug}/frames/{filename}', [ProjectController::class, 'deleteFrame'])->name('project.frames.delete');

Route::get('/project/{slug}/crop', [ProjectController::class, 'crop'])->name('project.crop');
Route::post('/project/{slug}/crop', [ProjectController::class, 'saveCrop'])->name('project.crop.save');
Route::get('/project/{slug}/frame/{filename}/cropped', [ProjectController::class, 'croppedFrameImage'])->name('project.frame.cropped');

Route::get('/project/{slug}/watermark', [ProjectController::class, 'watermark'])->name('project.watermark');
Route::post('/project/{slug}/watermark', [ProjectController::class, 'saveWatermark'])->name('project.watermark.save');
Route::post('/project/{slug}/watermark/logo', [ProjectController::class, 'uploadWatermarkLogo'])->name('project.watermark.logo');
Route::get('/project/{slug}/watermark/logo', [ProjectController::class, 'watermarkLogoImage'])->name('project.watermark.logo.image');
Route::get('/project/{slug}/frame/{filename}/watermarked', [ProjectController::class, 'watermarkedFrameImage'])->name('project.frame.watermarked');

Route::get('/project/{slug}/steps', [ProjectController::class, 'steps'])->name('project.steps');
Route::post('/project/{slug}/steps', [ProjectController::class, 'saveSteps'])->name('project.steps.save');
Route::get('/project/{slug}/frame/{filename}/step-image', [ProjectController::class, 'stepFrameImage'])->name('project.frame.step-image');

Route::get('/project/{slug}/export', [ProjectController::class, 'export'])->name('project.export');
Route::post('/project/{slug}/export', [ProjectController::class, 'saveCollage'])->name('project.export.save');
Route::get('/project/{slug}/export/collage-image', [ProjectController::class, 'collageImage'])->name('project.export.collage-image');
Route::get('/project/{slug}/export/download/collage', [ProjectController::class, 'downloadCollage'])->name('project.export.download.collage');
Route::get('/project/{slug}/export/download/zip', [ProjectController::class, 'downloadZip'])->name('project.export.download.zip');
Route::get('/project/{slug}/export/download/step/{filename}', [ProjectController::class, 'downloadStepCard'])->name('project.export.download.step');

Route::post('/project/{slug}/apply-last-setup/{type}', [ProjectController::class, 'applyLastSetup'])->name('project.apply-last-setup');

Route::get('/projects/recent', [ProjectController::class, 'recent'])->name('projects.recent');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/ffmpeg-detect', [SettingsController::class, 'detectFfmpeg'])->name('settings.ffmpeg-detect');
Route::get('/settings/ffmpeg-status', [SettingsController::class, 'ffmpegStatus'])->name('settings.ffmpeg-status');
Route::post('/settings/clear-data', [SettingsController::class, 'clearData'])->name('settings.clear-data');

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
