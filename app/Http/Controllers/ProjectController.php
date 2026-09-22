<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'video_path' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $videoPath = str_replace('/', DIRECTORY_SEPARATOR, $validated['video_path']);

        if (! file_exists($videoPath)) {
            return back()->withErrors(['video_path' => 'The selected video file does not exist.'])->withInput();
        }

        $extension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
        $supported = config('recipe-studio.supported_video_extensions', ['mp4', 'mov', 'avi', 'mkv', 'webm']);
        if (! in_array($extension, $supported)) {
            return back()->withErrors(['video_path' => 'Unsupported video format. Supported: '.implode(', ', $supported)])->withInput();
        }

        $project = $this->projectService->create($videoPath, $validated['name'] ?? null);

        return redirect()->route('project.show', $project['slug']);
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
}
