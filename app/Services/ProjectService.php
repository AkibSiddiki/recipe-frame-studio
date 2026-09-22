<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectService
{
    public function __construct(private FfmpegService $ffmpeg) {}

    public function create(string $videoPath, ?string $name = null): array
    {
        $filename = basename($videoPath);
        if (! $name) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
        }

        $slug = $this->generateSlug($name);
        $projectPath = $this->getProjectPath($slug);

        $directories = [
            'source',
            'frames',
            'thumbnails',
            'output',
            'temp',
        ];

        foreach ($directories as $dir) {
            File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.$dir);
        }

        $newVideoPath = $projectPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.$filename;
        File::copy($videoPath, $newVideoPath);

        $videoService = app(VideoService::class);
        $videoMetadata = $videoService->analyze($newVideoPath);

        $videoMetadata['original_path'] = $videoPath;
        $videoMetadata['filename'] = $filename;

        $duration = $videoMetadata['duration'] ?? 0;
        $thumbnailPath = $projectPath.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.'thumb.jpg';
        $this->ffmpeg->generateThumbnail($newVideoPath, $duration * 0.25, $thumbnailPath);

        $now = date('c');

        $projectData = [
            'name' => $name,
            'slug' => $slug,
            'created_at' => $now,
            'updated_at' => $now,
            'video' => $videoMetadata,
            'frames' => [],
            'crop' => [
                'ratio' => '4:5',
                'width' => $videoMetadata['width'] ?? 1080,
                'height' => isset($videoMetadata['width']) ? (int) ($videoMetadata['width'] * 1.25) : 1350,
            ],
            'watermark' => new \stdClass,
            'steps' => [],
            'export' => [
                'format' => 'jpg',
                'quality' => 90,
            ],
        ];

        $this->save($slug, $projectData);

        return $projectData;
    }

    public function load(string $slug): ?array
    {
        $projectPath = $this->getProjectPath($slug);
        $jsonPath = $projectPath.DIRECTORY_SEPARATOR.'project.json';

        if (! File::exists($jsonPath)) {
            return null;
        }

        $content = File::get($jsonPath);

        return json_decode($content, true);
    }

    public function save(string $slug, array $data): void
    {
        $projectPath = $this->getProjectPath($slug);
        $jsonPath = $projectPath.DIRECTORY_SEPARATOR.'project.json';

        $data['updated_at'] = date('c');

        File::ensureDirectoryExists($projectPath);
        File::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function getRecent(int $limit = 10): array
    {
        $basePath = $this->getProjectsBasePath();
        if (! File::exists($basePath)) {
            return [];
        }

        $projects = [];
        $directories = File::directories($basePath);

        foreach ($directories as $dir) {
            $slug = basename($dir);
            $data = $this->load($slug);
            if ($data) {
                $thumbPath = $dir.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.'thumb.jpg';
                $data['thumbnail_url'] = File::exists($thumbPath) ? $thumbPath : null;
                $projects[] = $data;
            }
        }

        usort($projects, function ($a, $b) {
            $timeA = strtotime($a['updated_at'] ?? '0');
            $timeB = strtotime($b['updated_at'] ?? '0');

            return $timeB <=> $timeA;
        });

        return array_slice($projects, 0, $limit);
    }

    public function getProjectsBasePath(): string
    {
        $path = config('recipe-studio.projects_directory', storage_path('app/projects'));
        File::ensureDirectoryExists($path);

        return $path;
    }

    public function generateSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while ($this->exists($slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function getProjectPath(string $slug): string
    {
        return $this->getProjectsBasePath().DIRECTORY_SEPARATOR.$slug;
    }

    public function exists(string $slug): bool
    {
        return File::exists($this->getProjectPath($slug));
    }

    public function delete(string $slug): bool
    {
        if (! $this->exists($slug)) {
            return false;
        }

        return File::deleteDirectory($this->getProjectPath($slug));
    }
}
