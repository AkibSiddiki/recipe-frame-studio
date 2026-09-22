<?php

namespace App\Http\Controllers;

use App\Services\AppSettingService;
use App\Services\FfmpegService;
use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settingService,
        private readonly FfmpegService $ffmpegService,
        private readonly ProjectService $projectService,
    ) {}

    public function index(): View
    {
        return view('settings', [
            'settings' => $this->settingService->all(),
            'ffmpegStatus' => $this->ffmpegService->getStatus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ffmpeg_path' => ['nullable', 'string'],
            'default_format' => ['required', 'string', 'in:jpeg,png,webp'],
            'default_quality' => ['required', 'integer', 'min:1', 'max:100'],
            'default_crop_ratio' => ['required', 'string', 'in:4:5,1:1,16:9,9:16'],
        ]);

        foreach ($validated as $key => $value) {
            $this->settingService->set($key, $value);
        }

        return redirect()->route('settings')->with('status', 'Settings updated successfully.');
    }

    public function detectFfmpeg(): JsonResponse
    {
        $path = $this->ffmpegService->detectPath();

        if ($path) {
            $this->settingService->set('ffmpeg_path', $path);
        }

        return response()->json(['path' => $path]);
    }

    public function ffmpegStatus(): JsonResponse
    {
        return response()->json($this->ffmpegService->getStatus());
    }

    public function clearData(Request $request): RedirectResponse
    {
        $deletedCount = $this->projectService->deleteAll();

        $tempDirs = [
            storage_path('app'.DIRECTORY_SEPARATOR.'temp_uploads'),
            storage_path('app'.DIRECTORY_SEPARATOR.'temp'),
        ];

        foreach ($tempDirs as $tempDir) {
            if (File::exists($tempDir)) {
                File::cleanDirectory($tempDir);
            }
        }

        $message = $deletedCount > 0
            ? "Application data cleared: {$deletedCount} project(s) and temporary files were deleted."
            : 'Application data cleared: All stored projects and temporary cache files have been purged.';

        return redirect()->route('settings')->with('status', $message);
    }
}
