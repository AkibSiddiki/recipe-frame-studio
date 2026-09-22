<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function create(): View
    {
        return view('project.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $videoPath = null;
        $name = $request->input('name');
        $supported = config('recipe-studio.supported_video_extensions', ['mp4', 'mov', 'avi', 'mkv', 'webm']);

        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, $supported)) {
                return back()->withErrors(['video_path' => 'Unsupported video format. Supported: '.implode(', ', $supported)])->withInput();
            }

            $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.'temp_uploads');
            File::ensureDirectoryExists($tempDir);
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$extension;
            $file->move($tempDir, $filename);
            $videoPath = $tempDir.DIRECTORY_SEPARATOR.$filename;

            if (! $name) {
                $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
        } elseif ($request->filled('video_path')) {
            $videoPath = trim((string) $request->input('video_path'), " \t\n\r\0\x0B\"'");
            $videoPath = str_replace('/', DIRECTORY_SEPARATOR, $videoPath);

            if (! file_exists($videoPath)) {
                return back()->withErrors(['video_path' => 'The selected video file does not exist.'])->withInput();
            }

            $extension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
            if (! in_array($extension, $supported)) {
                return back()->withErrors(['video_path' => 'Unsupported video format. Supported: '.implode(', ', $supported)])->withInput();
            }
        } else {
            return back()->withErrors(['video_path' => 'The video path field is required.'])->withInput();
        }

        try {
            $project = $this->projectService->create($videoPath, $name ?: null);

            return redirect()->route('project.show', $project['slug']);
        } catch (\Throwable $e) {
            Log::error('Project creation failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->withErrors(['video_path' => 'Could not create project: '.$e->getMessage()])->withInput();
        }
    }

    public function show(string $slug): View
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            abort(404, 'Project not found.');
        }

        return view('project.show', [
            'project' => $project,
        ]);
    }

    public function thumbnail(string $slug): BinaryFileResponse
    {
        $projectPath = $this->projectService->getProjectPath($slug);
        $thumbnailPath = $projectPath.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.'thumb.jpg';

        if (! file_exists($thumbnailPath)) {
            abort(404, 'Thumbnail not found.');
        }

        return response()->file($thumbnailPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function videoInfo(string $slug): JsonResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        return response()->json($project['video'] ?? []);
    }

    public function recent(): JsonResponse
    {
        return response()->json($this->projectService->getRecent());
    }

    public function frames(string $slug): View
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            abort(404, 'Project not found.');
        }

        return view('project.frames', [
            'project' => $project,
            'frames' => $project['frames'] ?? [],
        ]);
    }

    public function extractFrames(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $project = $this->projectService->load($slug);

        if (! $project) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Project not found'], 404);
            }
            abort(404, 'Project not found.');
        }

        $targetCount = $request->filled('target_count') ? (int) $request->input('target_count') : 24;
        $interval = $request->filled('interval') ? (float) $request->input('interval') : null;

        try {
            $updatedProject = $this->projectService->extractCandidateFrames($slug, $targetCount, $interval);
            $count = count($updatedProject['frames'] ?? []);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Extracted {$count} candidate frames.",
                    'frames' => $updatedProject['frames'] ?? [],
                ]);
            }

            return redirect()->route('project.frames', $slug)
                ->with('status', "Extracted {$count} frames successfully!");
        } catch (\Throwable $e) {
            Log::error('Frame extraction failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to extract frames: '.$e->getMessage()], 500);
            }

            return back()->withErrors(['error' => 'Failed to extract frames: '.$e->getMessage()]);
        }
    }

    public function frameImage(string $slug, string $filename): BinaryFileResponse
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $projectPath = $this->projectService->getProjectPath($slug);
        $framePath = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.$filename;

        if (! file_exists($framePath)) {
            abort(404, 'Frame image not found.');
        }

        return response()->file($framePath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function toggleFrame(Request $request, string $slug): JsonResponse
    {
        $frameId = (string) $request->input('frame_id');
        if (! $frameId) {
            return response()->json(['error' => 'Frame ID is required'], 422);
        }

        $updated = $this->projectService->toggleFrameSelection($slug, $frameId);

        if (! $updated) {
            return response()->json(['error' => 'Frame not found'], 404);
        }

        return response()->json([
            'success' => true,
            'frame' => $updated,
        ]);
    }

    public function captureAtTimestamp(Request $request, string $slug): JsonResponse
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $timestamp = (float) $request->input('timestamp', 0);
        $newFrame = $this->projectService->captureFrameAtTimestamp($slug, $timestamp);

        if (! $newFrame) {
            return response()->json(['error' => 'Could not capture frame at this timestamp'], 500);
        }

        return response()->json([
            'success' => true,
            'frame' => $newFrame,
        ]);
    }

    public function deleteFrame(string $slug, string $filename): JsonResponse
    {
        $filename = basename($filename);
        $deleted = $this->projectService->deleteFrame($slug, $filename);

        return response()->json([
            'success' => $deleted,
        ]);
    }

    public function crop(string $slug): View|RedirectResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            abort(404, 'Project not found.');
        }

        $allFrames = $project['frames'] ?? [];
        if (empty($allFrames)) {
            return redirect()->route('project.frames', $slug)
                ->with('status', 'Please extract frames first before cropping.');
        }

        $selectedFrames = $this->projectService->getSelectedFrames($slug);
        $presets = config('recipe-studio.presets', []);

        return view('project.crop', [
            'project' => $project,
            'selectedFrames' => $selectedFrames,
            'allFrames' => $allFrames,
            'presets' => $presets,
            'currentCrop' => $project['crop'] ?? [
                'ratio' => '4:5',
                'preset' => 'facebook-portrait',
                'default_alignment' => 'center',
            ],
        ]);
    }

    public function saveCrop(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Project not found'], 404);
            }
            abort(404, 'Project not found.');
        }

        $validated = $request->validate([
            'ratio' => ['nullable', 'string'],
            'preset' => ['nullable', 'string'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'default_alignment' => ['nullable', 'string', 'in:center,top,bottom,left,right'],
            'apply_to_all' => ['nullable', 'boolean'],
            'global_crop' => ['nullable', 'array'],
            'frames' => ['nullable', 'array'],
        ]);

        $updatedProject = $this->projectService->saveCropSettings($slug, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Crop settings saved successfully.',
                'crop' => $updatedProject['crop'] ?? [],
            ]);
        }

        return redirect()->route('project.crop', $slug)
            ->with('status', 'Crop settings saved successfully!');
    }

    public function croppedFrameImage(string $slug, string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        $filePath = $this->projectService->getCroppedFramePath($slug, $filename);

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, 'Cropped frame not found.');
        }

        return response()->file($filePath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function watermark(string $slug): View|RedirectResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            abort(404, 'Project not found.');
        }

        $allFrames = $project['frames'] ?? [];
        if (empty($allFrames)) {
            return redirect()->route('project.frames', $slug)
                ->with('status', 'Please extract frames first before adding a watermark.');
        }

        $selectedFrames = $this->projectService->getSelectedFrames($slug);

        $defaultWatermark = config('recipe-studio.watermark', [
            'enabled' => true,
            'type' => 'image',
            'image_path' => 'images/default-watermark.png',
            'position' => 'bottom-right',
            'opacity' => 85,
            'margin' => 30,
            'size' => 18,
            'text' => '@RecipeFrameStudio',
            'color' => '#ffffff',
            'has_shadow' => false,
            'has_pill' => true,
        ]);

        $projectWatermark = is_array($project['watermark'] ?? null) ? $project['watermark'] : [];
        if (empty($projectWatermark)) {
            $projectWatermark = $defaultWatermark;
        } elseif (($projectWatermark['type'] ?? '') === 'image' && empty($projectWatermark['image_path'])) {
            $projectWatermark['image_path'] = $defaultWatermark['image_path'] ?? 'images/default-watermark.png';
        }
        $watermarkConfig = array_merge($defaultWatermark, $projectWatermark);

        return view('project.watermark', [
            'project' => $project,
            'selectedFrames' => $selectedFrames,
            'allFrames' => $allFrames,
            'watermarkConfig' => $watermarkConfig,
        ]);
    }

    public function saveWatermark(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Project not found'], 404);
            }
            abort(404, 'Project not found.');
        }

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'in:text,image,none'],
            'text' => ['nullable', 'string', 'max:100'],
            'image_path' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'opacity' => ['nullable', 'numeric', 'min:10', 'max:100'],
            'size' => ['nullable', 'numeric', 'min:5', 'max:50'],
            'margin' => ['nullable', 'numeric'],
            'color' => ['nullable', 'string', 'max:20'],
            'has_shadow' => ['nullable', 'boolean'],
            'has_pill' => ['nullable', 'boolean'],
        ]);

        $updatedProject = $this->projectService->saveWatermarkSettings($slug, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Watermark settings saved successfully.',
                'watermark' => $updatedProject['watermark'] ?? [],
            ]);
        }

        return redirect()->route('project.watermark', $slug)
            ->with('status', 'Watermark settings saved successfully!');
    }

    public function uploadWatermarkLogo(Request $request, string $slug): JsonResponse
    {
        $project = $this->projectService->load($slug);

        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:10240'],
        ]);

        $logoPath = $this->projectService->uploadWatermarkLogo($slug, $request->file('logo'));

        if (! $logoPath) {
            return response()->json(['error' => 'Failed to upload logo image'], 500);
        }

        return response()->json([
            'success' => true,
            'path' => $logoPath,
            'url' => route('project.watermark.logo.image', $slug),
            'message' => 'Logo uploaded successfully.',
        ]);
    }

    public function watermarkLogoImage(string $slug): BinaryFileResponse
    {
        $project = $this->projectService->load($slug);
        if (! $project) {
            abort(404, 'Project not found.');
        }

        $imagePath = $project['watermark']['image_path'] ?? config('recipe-studio.watermark.image_path', 'images/default-watermark.png');
        if (! $imagePath) {
            $imagePath = 'images/default-watermark.png';
        }

        $projectPath = $this->projectService->getProjectPath($slug);
        $fullPath = $projectPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $imagePath);

        if (! file_exists($fullPath)) {
            $publicPath = public_path(str_replace('/', DIRECTORY_SEPARATOR, $imagePath));
            if (file_exists($publicPath)) {
                $fullPath = $publicPath;
            } else {
                abort(404, 'Watermark logo file not found.');
            }
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function watermarkedFrameImage(string $slug, string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        $filePath = $this->projectService->getWatermarkedFramePath($slug, $filename);

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, 'Watermarked frame not found.');
        }

        return response()->file($filePath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
