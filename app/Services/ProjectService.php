<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
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
            'watermark' => config('recipe-studio.watermark', [
                'enabled' => true,
                'type' => 'image',
                'image_path' => 'images/default-watermark.png',
                'position' => 'bottom-right',
                'opacity' => 85,
                'margin' => 30,
                'size' => 18,
                'text' => '@রান্নাঘরেরডায়েরি',
                'color' => '#ffffff',
                'has_shadow' => false,
                'has_pill' => true,
            ]),
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

    public function deleteAll(): int
    {
        $basePath = $this->getProjectsBasePath();
        if (! File::exists($basePath)) {
            return 0;
        }

        $directories = File::directories($basePath);
        $count = 0;

        foreach ($directories as $dir) {
            if (File::deleteDirectory($dir)) {
                $count++;
            }
        }

        return $count;
    }

    public function extractCandidateFrames(string $slug, ?int $targetCount = 24, ?float $interval = null): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $project = $this->load($slug);
        if (! $project) {
            throw new \RuntimeException("Project {$slug} not found.");
        }

        $projectPath = $this->getProjectPath($slug);
        $video = $project['video'] ?? [];
        $filename = $video['filename'] ?? (isset($video['original_path']) ? basename($video['original_path']) : null);
        $videoPath = $projectPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($videoPath)) {
            // Fallback to original path if source copy missing
            $videoPath = $video['original_path'] ?? null;
        }

        if (! $videoPath || ! File::exists($videoPath)) {
            throw new \RuntimeException('Source video file could not be found.');
        }

        $duration = (float) ($video['duration'] ?? 30.0);
        if ($duration <= 0) {
            $duration = 30.0;
        }

        if ($interval === null || $interval <= 0) {
            $count = max(1, $targetCount ?? 24);
            $interval = max(0.5, round($duration / $count, 2));
        }

        $framesDir = $projectPath.DIRECTORY_SEPARATOR.'frames';
        File::ensureDirectoryExists($framesDir);
        File::cleanDirectory($framesDir);

        $outputPattern = $framesDir.DIRECTORY_SEPARATOR.'frame_%04d.jpg';
        $success = $this->ffmpeg->extractFrames($videoPath, $outputPattern, $interval);

        if (! $success) {
            throw new \RuntimeException('FFmpeg frame extraction failed.');
        }

        // Collect all extracted frames sorted
        $frameFiles = File::glob($framesDir.DIRECTORY_SEPARATOR.'*.jpg');
        sort($frameFiles);

        $frames = [];
        foreach ($frameFiles as $idx => $filePath) {
            $index = $idx + 1;
            $frameFilename = basename($filePath);
            $frameTime = round($idx * $interval, 2);

            $frames[] = [
                'id' => 'frame_'.sprintf('%04d', $index),
                'filename' => $frameFilename,
                'timestamp' => $frameTime,
                'formatted_time' => $this->ffmpeg->formatDuration($frameTime),
                'selected' => true,
                'width' => $video['width'] ?? 1080,
                'height' => $video['height'] ?? 1920,
            ];
        }

        $project['frames'] = $frames;
        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        return $project;
    }

    public function toggleFrameSelection(string $slug, string $frameId): ?array
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $updatedFrame = null;
        $frames = $project['frames'] ?? [];

        foreach ($frames as &$frame) {
            if (($frame['id'] ?? '') === $frameId || ($frame['filename'] ?? '') === $frameId) {
                $frame['selected'] = ! ($frame['selected'] ?? false);
                $updatedFrame = $frame;
                break;
            }
        }

        if ($updatedFrame !== null) {
            $project['frames'] = $frames;
            $project['updated_at'] = date('c');
            $this->save($slug, $project);
        }

        return $updatedFrame;
    }

    public function captureFrameAtTimestamp(string $slug, float $timestamp): ?array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $video = $project['video'] ?? [];
        $filename = $video['filename'] ?? (isset($video['original_path']) ? basename($video['original_path']) : null);
        $videoPath = $projectPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($videoPath)) {
            $videoPath = $video['original_path'] ?? null;
        }

        if (! $videoPath || ! File::exists($videoPath)) {
            return null;
        }

        $framesDir = $projectPath.DIRECTORY_SEPARATOR.'frames';
        File::ensureDirectoryExists($framesDir);

        $frameFilename = 'frame_custom_'.time().'_'.round($timestamp).'.jpg';
        $outputPath = $framesDir.DIRECTORY_SEPARATOR.$frameFilename;

        $success = $this->ffmpeg->extractFrameAt($videoPath, $timestamp, $outputPath);
        if (! $success) {
            return null;
        }

        $newFrame = [
            'id' => 'frame_custom_'.uniqid(),
            'filename' => $frameFilename,
            'timestamp' => round($timestamp, 2),
            'formatted_time' => $this->ffmpeg->formatDuration($timestamp),
            'selected' => true,
            'width' => $video['width'] ?? 1080,
            'height' => $video['height'] ?? 1920,
        ];

        $frames = $project['frames'] ?? [];
        $frames[] = $newFrame;

        // Sort frames chronologically by timestamp
        usort($frames, fn ($a, $b) => ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0));

        $project['frames'] = $frames;
        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        return $newFrame;
    }

    public function deleteFrame(string $slug, string $frameId): bool
    {
        $project = $this->load($slug);
        if (! $project) {
            return false;
        }

        $projectPath = $this->getProjectPath($slug);
        $frames = $project['frames'] ?? [];
        $found = false;
        $remainingFrames = [];

        foreach ($frames as $frame) {
            if (($frame['id'] ?? '') === $frameId || ($frame['filename'] ?? '') === $frameId) {
                $found = true;
                $filePath = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.$frame['filename'];
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            } else {
                $remainingFrames[] = $frame;
            }
        }

        if ($found) {
            $project['frames'] = $remainingFrames;
            $project['updated_at'] = date('c');
            $this->save($slug, $project);
        }

        return $found;
    }

    public function getSelectedFrames(string $slug): array
    {
        $project = $this->load($slug);
        if (! $project) {
            return [];
        }

        $frames = $project['frames'] ?? [];
        $selected = array_values(array_filter($frames, fn ($f) => ! empty($f['selected'])));

        // If no frames are explicitly selected, fallback to all candidate frames
        return ! empty($selected) ? $selected : $frames;
    }

    public function saveCropSettings(string $slug, array $cropConfig): ?array
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $croppedDir = $projectPath.DIRECTORY_SEPARATOR.'cropped';

        // Update project crop config
        $project['crop'] = array_merge($project['crop'] ?? [], [
            'ratio' => $cropConfig['ratio'] ?? ($project['crop']['ratio'] ?? '4:5'),
            'preset' => $cropConfig['preset'] ?? ($project['crop']['preset'] ?? 'facebook-portrait'),
            'width' => (int) ($cropConfig['width'] ?? ($project['crop']['width'] ?? 1080)),
            'height' => (int) ($cropConfig['height'] ?? ($project['crop']['height'] ?? 1350)),
            'default_alignment' => $cropConfig['default_alignment'] ?? 'center',
            'updated_at' => date('c'),
        ]);

        // If per-frame coordinates or global batch coordinates were provided
        $frames = $project['frames'] ?? [];
        $frameCrops = $cropConfig['frames'] ?? [];
        $applyToAll = ! empty($cropConfig['apply_to_all']);
        $globalCrop = $cropConfig['global_crop'] ?? null;

        foreach ($frames as &$frame) {
            $frameId = $frame['id'] ?? $frame['filename'] ?? '';

            if ($applyToAll && $globalCrop) {
                $frame['crop'] = [
                    'x' => (int) round($globalCrop['x'] ?? 0),
                    'y' => (int) round($globalCrop['y'] ?? 0),
                    'width' => (int) round($globalCrop['width'] ?? 0),
                    'height' => (int) round($globalCrop['height'] ?? 0),
                    'ratio' => $project['crop']['ratio'],
                    'alignment' => $project['crop']['default_alignment'],
                ];
            } elseif (isset($frameCrops[$frameId])) {
                $c = $frameCrops[$frameId];
                $frame['crop'] = [
                    'x' => (int) round($c['x'] ?? 0),
                    'y' => (int) round($c['y'] ?? 0),
                    'width' => (int) round($c['width'] ?? 0),
                    'height' => (int) round($c['height'] ?? 0),
                    'ratio' => $c['ratio'] ?? $project['crop']['ratio'],
                    'alignment' => $c['alignment'] ?? $project['crop']['default_alignment'],
                ];
            }
        }

        $project['frames'] = $frames;
        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        // Clear existing cached cropped images so they regenerate with new crop coordinates
        if (File::isDirectory($croppedDir)) {
            File::cleanDirectory($croppedDir);
        }

        return $project;
    }

    public function getCroppedFramePath(string $slug, string $frameId, bool $regenerate = false): ?string
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $framesDir = $projectPath.DIRECTORY_SEPARATOR.'frames';
        $croppedDir = $projectPath.DIRECTORY_SEPARATOR.'cropped';

        if (! File::isDirectory($croppedDir)) {
            File::makeDirectory($croppedDir, 0755, true, true);
        }

        // Locate frame
        $frame = null;
        foreach ($project['frames'] ?? [] as $f) {
            if (($f['id'] ?? '') === $frameId || ($f['filename'] ?? '') === $frameId) {
                $frame = $f;
                break;
            }
        }

        if (! $frame) {
            return null;
        }

        $srcPath = $framesDir.DIRECTORY_SEPARATOR.$frame['filename'];
        if (! File::exists($srcPath)) {
            return null;
        }

        $croppedFilename = pathinfo($frame['filename'], PATHINFO_FILENAME).'_cropped.jpg';
        $croppedPath = $croppedDir.DIRECTORY_SEPARATOR.$croppedFilename;

        if (! $regenerate && File::exists($croppedPath)) {
            return $croppedPath;
        }

        // Perform crop using PHP GD
        $cropCoords = $this->resolveFrameCropCoordinates($frame, $project['crop'] ?? []);

        return $this->cropImageFile($srcPath, $croppedPath, $cropCoords) ? $croppedPath : null;
    }

    private function resolveFrameCropCoordinates(array $frame, array $projectCrop): array
    {
        $saved = $frame['crop'] ?? null;
        if ($saved && ! empty($saved['width']) && ! empty($saved['height'])) {
            return [
                'x' => (int) ($saved['x'] ?? 0),
                'y' => (int) ($saved['y'] ?? 0),
                'width' => (int) $saved['width'],
                'height' => (int) $saved['height'],
            ];
        }

        // Compute smart default crop based on ratio and alignment
        $ratioStr = $projectCrop['ratio'] ?? '4:5';
        $parts = explode(':', $ratioStr);
        $targetRatio = (count($parts) === 2 && (float) $parts[1] > 0)
            ? (float) $parts[0] / (float) $parts[1]
            : (4.0 / 5.0);

        $srcW = (int) ($frame['width'] ?? 1920);
        $srcH = (int) ($frame['height'] ?? 1080);
        if ($srcW <= 0 || $srcH <= 0) {
            $srcW = 1920;
            $srcH = 1080;
        }

        $srcRatio = $srcW / $srcH;
        $alignment = $projectCrop['default_alignment'] ?? 'center';

        if ($srcRatio > $targetRatio) {
            // Source is wider than target: fit height, crop width
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropY = 0;
            $cropX = match ($alignment) {
                'left' => 0,
                'right' => max(0, $srcW - $cropW),
                default => (int) round(($srcW - $cropW) / 2),
            };
        } else {
            // Source is taller than target: fit width, crop height
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = match ($alignment) {
                'top' => 0,
                'bottom' => max(0, $srcH - $cropH),
                default => (int) round(($srcH - $cropH) / 2),
            };
        }

        return [
            'x' => max(0, $cropX),
            'y' => max(0, $cropY),
            'width' => min($srcW, $cropW),
            'height' => min($srcH, $cropH),
        ];
    }

    private function cropImageFile(string $srcPath, string $destPath, array $coords): bool
    {
        $x = max(0, (int) ($coords['x'] ?? 0));
        $y = max(0, (int) ($coords['y'] ?? 0));
        $width = max(1, (int) ($coords['width'] ?? 100));
        $height = max(1, (int) ($coords['height'] ?? 100));

        // Use GD
        if (extension_loaded('gd')) {
            $imageInfo = @getimagesize($srcPath);
            if (! $imageInfo) {
                return false;
            }

            $srcImg = match ($imageInfo[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
                IMAGETYPE_PNG => @imagecreatefrompng($srcPath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($srcPath),
                default => null,
            };

            if (! $srcImg) {
                return false;
            }

            $srcW = imagesx($srcImg);
            $srcH = imagesy($srcImg);

            // Bounds check
            $x = min($x, max(0, $srcW - 10));
            $y = min($y, max(0, $srcH - 10));
            $width = min($width, $srcW - $x);
            $height = min($height, $srcH - $y);

            $cropped = imagecrop($srcImg, [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
            ]);

            imagedestroy($srcImg);

            if ($cropped !== false) {
                $saved = imagejpeg($cropped, $destPath, 92);
                imagedestroy($cropped);

                return $saved;
            }
        }

        return false;
    }

    public function saveWatermarkSettings(string $slug, array $config): ?array
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $watermarkedDir = $projectPath.DIRECTORY_SEPARATOR.'watermarked';

        $currentWatermark = is_array($project['watermark'] ?? null) ? $project['watermark'] : [];

        $type = $config['type'] ?? ($currentWatermark['type'] ?? 'text');
        $enabled = isset($config['enabled']) ? (bool) $config['enabled'] : ($type !== 'none');
        $positions = is_array($config['positions'] ?? null) ? $config['positions'] : ($currentWatermark['positions'] ?? []);

        $project['watermark'] = [
            'enabled' => $enabled,
            'type' => $type,
            'text' => $config['text'] ?? ($currentWatermark['text'] ?? '@RecipeFrameStudio'),
            'image_path' => $config['image_path'] ?? ($currentWatermark['image_path'] ?? null),
            'position' => $config['position'] ?? ($currentWatermark['position'] ?? 'bottom-right'),
            'positions' => $positions,
            'opacity' => (int) ($config['opacity'] ?? ($currentWatermark['opacity'] ?? 80)),
            'size' => (int) ($config['size'] ?? ($currentWatermark['size'] ?? 15)),
            'margin' => (int) ($config['margin'] ?? ($currentWatermark['margin'] ?? 30)),
            'color' => $config['color'] ?? ($currentWatermark['color'] ?? '#ffffff'),
            'has_shadow' => ! empty($config['has_shadow']),
            'has_pill' => isset($config['has_pill']) ? (bool) $config['has_pill'] : true,
            'updated_at' => date('c'),
        ];

        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        // Clear existing cached watermarked frames and downstream steps/output
        if (File::isDirectory($watermarkedDir)) {
            File::cleanDirectory($watermarkedDir);
        }

        $stepsDir = $projectPath.DIRECTORY_SEPARATOR.'steps';
        if (File::isDirectory($stepsDir)) {
            File::cleanDirectory($stepsDir);
        }

        $outputDir = $projectPath.DIRECTORY_SEPARATOR.'output';
        if (File::isDirectory($outputDir)) {
            File::cleanDirectory($outputDir);
        }

        return $project;
    }

    public function uploadWatermarkLogo(string $slug, $file): ?string
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $watermarkDir = $projectPath.DIRECTORY_SEPARATOR.'watermark';
        if (! File::isDirectory($watermarkDir)) {
            File::makeDirectory($watermarkDir, 0755, true, true);
        }

        $extension = $file instanceof UploadedFile
            ? $file->getClientOriginalExtension()
            : pathinfo($file, PATHINFO_EXTENSION);
        $extension = strtolower($extension ?: 'png');

        $filename = 'logo_'.time().'.'.$extension;
        $destPath = $watermarkDir.DIRECTORY_SEPARATOR.$filename;

        if ($file instanceof UploadedFile) {
            $file->move($watermarkDir, $filename);
        } else {
            File::copy($file, $destPath);
        }

        $relativePath = 'watermark/'.$filename;

        // Auto update project watermark config to use this image
        $currentWatermark = is_array($project['watermark'] ?? null) ? $project['watermark'] : [];
        $currentWatermark['image_path'] = $relativePath;
        $currentWatermark['type'] = 'image';
        $currentWatermark['enabled'] = true;

        $project['watermark'] = $currentWatermark;
        $this->save($slug, $project);

        return $relativePath;
    }

    public function getWatermarkedFramePath(string $slug, string $frameId, bool $regenerate = false): ?string
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $croppedPath = $this->getCroppedFramePath($slug, $frameId);
        if (! $croppedPath || ! File::exists($croppedPath)) {
            return null;
        }

        $watermarkConfig = is_array($project['watermark'] ?? null) ? $project['watermark'] : [];
        if (empty($watermarkConfig['enabled']) || ($watermarkConfig['type'] ?? 'none') === 'none') {
            return $croppedPath;
        }

        $positions = is_array($watermarkConfig['positions'] ?? null) ? $watermarkConfig['positions'] : [];
        if (isset($positions[$frameId])) {
            $watermarkConfig['position'] = $positions[$frameId];
        } elseif (isset($positions[basename($frameId)])) {
            $watermarkConfig['position'] = $positions[basename($frameId)];
        }

        $projectPath = $this->getProjectPath($slug);
        $watermarkedDir = $projectPath.DIRECTORY_SEPARATOR.'watermarked';
        if (! File::isDirectory($watermarkedDir)) {
            File::makeDirectory($watermarkedDir, 0755, true, true);
        }

        $watermarkedFilename = pathinfo($croppedPath, PATHINFO_FILENAME).'_wm.jpg';
        $watermarkedPath = $watermarkedDir.DIRECTORY_SEPARATOR.$watermarkedFilename;

        if (! $regenerate && File::exists($watermarkedPath)) {
            return $watermarkedPath;
        }

        $success = $this->applyWatermarkToImage($projectPath, $croppedPath, $watermarkedPath, $watermarkConfig);

        return $success ? $watermarkedPath : $croppedPath;
    }

    private function applyWatermarkToImage(string $projectPath, string $srcPath, string $destPath, array $config): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $srcImg = @imagecreatefromjpeg($srcPath);
        if (! $srcImg) {
            return false;
        }

        $canvasW = imagesx($srcImg);
        $canvasH = imagesy($srcImg);

        $type = $config['type'] ?? 'image';
        $position = $config['position'] ?? 'bottom-right';
        $margin = (int) ($config['margin'] ?? 30);
        $opacity = max(10, min(100, (int) ($config['opacity'] ?? 80)));
        $sizePercent = max(5, min(50, (int) ($config['size'] ?? 15)));

        if ($type === 'image') {
            $imageRelPath = ! empty($config['image_path']) ? $config['image_path'] : 'images/default-watermark.png';
            $logoFullPath = $projectPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $imageRelPath);
            if (! File::exists($logoFullPath)) {
                $publicPath = public_path(str_replace('/', DIRECTORY_SEPARATOR, $imageRelPath));
                if (File::exists($publicPath)) {
                    $logoFullPath = $publicPath;
                }
            }

            if (File::exists($logoFullPath)) {
                $logoInfo = @getimagesize($logoFullPath);
                if ($logoInfo) {
                    $logoImg = match ($logoInfo[2]) {
                        IMAGETYPE_PNG => @imagecreatefrompng($logoFullPath),
                        IMAGETYPE_JPEG => @imagecreatefromjpeg($logoFullPath),
                        IMAGETYPE_WEBP => @imagecreatefromwebp($logoFullPath),
                        default => null,
                    };

                    if ($logoImg) {
                        imagealphablending($logoImg, true);
                        imagesavealpha($logoImg, true);

                        $origLogoW = imagesx($logoImg);
                        $origLogoH = imagesy($logoImg);

                        $targetLogoW = (int) round($canvasW * ($sizePercent / 100));
                        $targetLogoH = (int) round($origLogoH * ($targetLogoW / max(1, $origLogoW)));

                        $resizedLogo = imagecreatetruecolor($targetLogoW, $targetLogoH);
                        imagealphablending($resizedLogo, false);
                        imagesavealpha($resizedLogo, true);
                        $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
                        imagefill($resizedLogo, 0, 0, $transparent);

                        imagecopyresampled($resizedLogo, $logoImg, 0, 0, 0, 0, $targetLogoW, $targetLogoH, $origLogoW, $origLogoH);
                        imagedestroy($logoImg);

                        [$posX, $posY] = $this->calculateWatermarkCoordinates($canvasW, $canvasH, $targetLogoW, $targetLogoH, $position, $margin);

                        imagealphablending($srcImg, true);
                        if ($opacity >= 100) {
                            imagecopy($srcImg, $resizedLogo, $posX, $posY, 0, 0, $targetLogoW, $targetLogoH);
                        } else {
                            $this->imagecopymergeAlpha($srcImg, $resizedLogo, $posX, $posY, 0, 0, $targetLogoW, $targetLogoH, $opacity);
                        }
                        imagedestroy($resizedLogo);
                    }
                }
            }
        } elseif ($type === 'text') {
            $text = trim((string) ($config['text'] ?? '@RecipeFrameStudio'));
            if ($text !== '') {
                $fontSize = max(14, (int) round($canvasW * ($sizePercent / 100) * 0.25));
                $fontFile = 'C:/Windows/Fonts/arial.ttf';
                $useTtf = function_exists('imagettftext') && file_exists($fontFile);

                if ($useTtf) {
                    $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
                    $textW = abs($bbox[4] - $bbox[0]);
                    $textH = abs($bbox[5] - $bbox[1]);
                } else {
                    $fontIndex = 5;
                    $textW = strlen($text) * imagefontwidth($fontIndex);
                    $textH = imagefontheight($fontIndex);
                }

                $hasPill = ! empty($config['has_pill']);
                $padX = $hasPill ? (int) round($fontSize * 0.8) : 0;
                $padY = $hasPill ? (int) round($fontSize * 0.5) : 0;

                $totalW = $textW + ($padX * 2);
                $totalH = $textH + ($padY * 2);

                [$boxX, $boxY] = $this->calculateWatermarkCoordinates($canvasW, $canvasH, $totalW, $totalH, $position, $margin);

                imagealphablending($srcImg, true);

                // Draw translucent pill backdrop
                if ($hasPill) {
                    $alpha = (int) round(127 - (127 * ($opacity / 100) * 0.8));
                    $pillColor = imagecolorallocatealpha($srcImg, 15, 23, 42, $alpha);
                    imagefilledrectangle($srcImg, $boxX, $boxY, $boxX + $totalW, $boxY + $totalH, $pillColor);
                }

                // Text color
                $hexColor = $config['color'] ?? '#ffffff';
                [$r, $g, $b] = $this->hexToRgb($hexColor);
                $textAlpha = (int) round(127 - (127 * ($opacity / 100)));

                if ($useTtf) {
                    $textX = $boxX + $padX;
                    $textY = $boxY + $padY + $textH;

                    // Drop shadow
                    if (! empty($config['has_shadow'])) {
                        $shadowColor = imagecolorallocatealpha($srcImg, 0, 0, 0, min(127, $textAlpha + 20));
                        imagettftext($srcImg, $fontSize, 0, $textX + 2, $textY + 2, $shadowColor, $fontFile, $text);
                    }

                    $textColor = imagecolorallocatealpha($srcImg, $r, $g, $b, $textAlpha);
                    imagettftext($srcImg, $fontSize, 0, $textX, $textY, $textColor, $fontFile, $text);
                } else {
                    $textX = $boxX + $padX;
                    $textY = $boxY + $padY;
                    $textColor = imagecolorallocatealpha($srcImg, $r, $g, $b, $textAlpha);
                    imagestring($srcImg, 5, $textX, $textY, $text, $textColor);
                }
            }
        }

        $saved = imagejpeg($srcImg, $destPath, 92);
        imagedestroy($srcImg);

        return $saved;
    }

    private function calculateWatermarkCoordinates(int $canvasW, int $canvasH, int $elementW, int $elementH, string $position, int $margin): array
    {
        $x = match ($position) {
            'top-left', 'left', 'bottom-left' => $margin,
            'top-center', 'center', 'bottom-center' => (int) round(($canvasW - $elementW) / 2),
            default => $canvasW - $elementW - $margin, // bottom-right, right, top-right
        };

        $y = match ($position) {
            'top-left', 'top-center', 'top-right' => $margin,
            'left', 'center', 'right' => (int) round(($canvasH - $elementH) / 2),
            default => $canvasH - $elementH - $margin, // bottom-left, bottom-center, bottom-right
        };

        return [$x, $y];
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return [255, 255, 255];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function imagecopymergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct): void
    {
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
        imagedestroy($cut);
    }

    public function getRecipeSteps(string $slug): array
    {
        $project = $this->load($slug);
        if (! $project) {
            return [];
        }

        $selectedFrames = $this->getSelectedFrames($slug);
        $savedSteps = is_array($project['steps'] ?? null) ? $project['steps'] : [];
        $style = $savedSteps['style'] ?? [
            'layout' => 'bottom-banner',
            'size' => 'medium',
            'bg_color' => '#0f172a',
            'bg_opacity' => 85,
            'text_color' => '#ffffff',
            'badge_color' => '#f59e0b',
            'has_shadow' => true,
        ];

        $savedItems = is_array($savedSteps['items'] ?? null) ? $savedSteps['items'] : [];
        $itemsByFrame = [];
        foreach ($savedItems as $item) {
            $fId = $item['frame_id'] ?? '';
            if ($fId) {
                $itemsByFrame[$fId] = $item;
            }
        }

        $items = [];
        foreach ($selectedFrames as $index => $frame) {
            $filename = $frame['filename'] ?? '';
            $id = $frame['id'] ?? $filename;
            $fId = $filename ?: $id;
            $existing = $itemsByFrame[$fId] ?? ($itemsByFrame[$id] ?? null);

            $items[] = [
                'frame_id' => $fId,
                'filename' => $filename,
                'step_number' => (int) ($existing['step_number'] ?? ($index + 1)),
                'title' => $existing['title'] ?? 'Step '.($index + 1),
                'description' => $existing['description'] ?? '',
                'ingredients' => $existing['ingredients'] ?? '',
                'enabled' => isset($existing['enabled']) ? (bool) $existing['enabled'] : true,
            ];
        }

        return [
            'style' => $style,
            'items' => $items,
        ];
    }

    public function saveRecipeSteps(string $slug, array $data): ?array
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $currentSteps = is_array($project['steps'] ?? null)
            ? $project['steps']
            : (is_array($project['recipe_steps'] ?? null) ? $project['recipe_steps'] : []);

        $defaultStyle = [
            'layout' => 'bottom-banner',
            'size' => 'medium',
            'bg_color' => '#0f172a',
            'bg_opacity' => 85,
            'text_color' => '#ffffff',
            'badge_color' => '#f59e0b',
            'has_shadow' => true,
        ];

        $style = array_merge(
            $defaultStyle,
            $currentSteps['style'] ?? [],
            $data['style'] ?? []
        );

        $items = $data['items'] ?? ($currentSteps['items'] ?? []);

        $stepsData = [
            'style' => $style,
            'items' => $items,
            'updated_at' => date('c'),
        ];

        $project['steps'] = $stepsData;
        $project['recipe_steps'] = $stepsData;

        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        $stepsDir = $this->getProjectPath($slug).DIRECTORY_SEPARATOR.'steps';
        if (File::isDirectory($stepsDir)) {
            File::cleanDirectory($stepsDir);
        }

        return $project['steps'];
    }

    public function getStepFramePath(string $slug, string $frameId, bool $regenerate = false): ?string
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $wmPath = $this->getWatermarkedFramePath($slug, $frameId);
        if (! $wmPath || ! File::exists($wmPath)) {
            return null;
        }

        $recipeSteps = $this->getRecipeSteps($slug);
        $style = $recipeSteps['style'] ?? [];
        $items = $recipeSteps['items'] ?? [];

        $stepItem = null;
        foreach ($items as $item) {
            if (($item['frame_id'] ?? '') === $frameId || ($item['filename'] ?? '') === $frameId) {
                $stepItem = $item;
                break;
            }
        }

        if (! $stepItem || empty($stepItem['enabled']) || ($style['layout'] ?? '') === 'none') {
            return $wmPath;
        }

        $projectPath = $this->getProjectPath($slug);
        $stepsDir = $projectPath.DIRECTORY_SEPARATOR.'steps';
        if (! File::isDirectory($stepsDir)) {
            File::makeDirectory($stepsDir, 0755, true, true);
        }

        $stepFilename = pathinfo($wmPath, PATHINFO_FILENAME).'_step.jpg';
        $stepPath = $stepsDir.DIRECTORY_SEPARATOR.$stepFilename;

        if (! $regenerate && File::exists($stepPath)) {
            return $stepPath;
        }

        $success = $this->renderStepOverlayToImage($wmPath, $stepPath, $stepItem, $style);

        return $success ? $stepPath : $wmPath;
    }

    private function renderStepOverlayToImage(string $srcPath, string $destPath, array $step, array $style): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $srcImg = @imagecreatefromjpeg($srcPath);
        if (! $srcImg) {
            return false;
        }

        $canvasW = imagesx($srcImg);
        $canvasH = imagesy($srcImg);

        $layout = $style['layout'] ?? 'bottom-banner';
        $size = $style['size'] ?? 'medium';
        $opacity = max(10, min(100, (int) ($style['bg_opacity'] ?? 85)));
        $bgColorHex = $style['bg_color'] ?? '#0f172a';
        $textColorHex = $style['text_color'] ?? '#ffffff';
        $badgeColorHex = $style['badge_color'] ?? '#f59e0b';
        $hasShadow = ! empty($style['has_shadow']);

        [$bgR, $bgG, $bgB] = $this->hexToRgb($bgColorHex);
        [$textR, $textG, $textB] = $this->hexToRgb($textColorHex);
        [$badgeR, $badgeG, $badgeB] = $this->hexToRgb($badgeColorHex);

        $fontFile = 'C:/Windows/Fonts/arial.ttf';
        $fontBold = 'C:/Windows/Fonts/arialbd.ttf';
        if (! file_exists($fontBold)) {
            $fontBold = $fontFile;
        }
        $useTtf = function_exists('imagettftext') && file_exists($fontFile);

        $scaleFactor = match ($size) {
            'small' => 0.85,
            'large' => 1.2,
            default => 1.0,
        };

        $baseTitleSize = max(18, (int) round($canvasW * 0.032 * $scaleFactor));
        $baseDescSize = max(14, (int) round($canvasW * 0.022 * $scaleFactor));
        $baseBadgeSize = max(13, (int) round($canvasW * 0.02 * $scaleFactor));

        $stepNum = $step['step_number'] ?? 1;
        $badgeText = sprintf('#%02d', $stepNum);
        $titleText = trim((string) ($step['title'] ?? ''));
        $descText = trim((string) ($step['description'] ?? ''));

        imagealphablending($srcImg, true);

        $bgAlpha = (int) round(127 - (127 * ($opacity / 100)));
        $bgColor = imagecolorallocatealpha($srcImg, $bgR, $bgG, $bgB, $bgAlpha);
        $textColor = imagecolorallocate($srcImg, $textR, $textG, $textB);
        $shadowColor = imagecolorallocatealpha($srcImg, 0, 0, 0, 80);

        if ($layout === 'badge-only') {
            $badgeW = (int) round($baseBadgeSize * 3.5);
            $badgeH = (int) round($baseBadgeSize * 2.2);
            $badgeX = (int) round($canvasW * 0.05);
            $badgeY = (int) round($canvasH * 0.05);

            $badgeColor = imagecolorallocate($srcImg, $badgeR, $badgeG, $badgeB);
            imagefilledrectangle($srcImg, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeColor);

            $badgeTextColor = imagecolorallocate($srcImg, 15, 23, 42);
            if ($useTtf) {
                imagettftext($srcImg, $baseBadgeSize, 0, $badgeX + (int) round($badgeW * 0.2), $badgeY + (int) round($badgeH * 0.7), $badgeTextColor, $fontBold, $badgeText);
            } else {
                imagestring($srcImg, 5, $badgeX + 10, $badgeY + 8, $badgeText, $badgeTextColor);
            }
        } elseif ($layout === 'top-banner') {
            $bannerH = (int) round($canvasH * 0.18 * $scaleFactor);
            imagefilledrectangle($srcImg, 0, 0, $canvasW, $bannerH, $bgColor);

            $badgeW = (int) round($baseBadgeSize * 3.5);
            $badgeH = (int) round($baseBadgeSize * 2.0);
            $badgeX = (int) round($canvasW * 0.04);
            $badgeY = (int) round(($bannerH - $badgeH) / 2);

            $badgeColor = imagecolorallocate($srcImg, $badgeR, $badgeG, $badgeB);
            imagefilledrectangle($srcImg, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeColor);
            $badgeTextColor = imagecolorallocate($srcImg, 15, 23, 42);

            if ($useTtf) {
                imagettftext($srcImg, $baseBadgeSize, 0, $badgeX + (int) round($badgeW * 0.18), $badgeY + (int) round($badgeH * 0.72), $badgeTextColor, $fontBold, $badgeText);
            }

            $textX = $badgeX + $badgeW + (int) round($canvasW * 0.03);
            if ($titleText !== '') {
                $titleY = (int) round($bannerH * 0.45);
                if ($useTtf) {
                    if ($hasShadow) {
                        imagettftext($srcImg, $baseTitleSize, 0, $textX + 2, $titleY + 2, $shadowColor, $fontBold, $titleText);
                    }
                    imagettftext($srcImg, $baseTitleSize, 0, $textX, $titleY, $textColor, $fontBold, $titleText);
                } else {
                    imagestring($srcImg, 5, $textX, $titleY - 15, $titleText, $textColor);
                }
            }
            if ($descText !== '') {
                $descY = (int) round($bannerH * 0.8);
                $maxW = $canvasW - $textX - (int) round($canvasW * 0.04);
                $lines = $this->wrapText($baseDescSize, $fontFile, $descText, $maxW);
                if (! empty($lines[0])) {
                    if ($useTtf) {
                        imagettftext($srcImg, $baseDescSize, 0, $textX, $descY, $textColor, $fontFile, $lines[0]);
                    } else {
                        imagestring($srcImg, 3, $textX, $descY - 10, $lines[0], $textColor);
                    }
                }
            }
        } elseif ($layout === 'lower-third') {
            $cardW = (int) round($canvasW * 0.92);
            $cardH = (int) round($canvasH * 0.22 * $scaleFactor);
            $cardX = (int) round(($canvasW - $cardW) / 2);
            $cardY = $canvasH - $cardH - (int) round($canvasH * 0.04);

            imagefilledrectangle($srcImg, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $bgColor);

            $badgeW = (int) round($baseBadgeSize * 3.6);
            $badgeH = (int) round($baseBadgeSize * 2.0);
            $badgeX = $cardX + (int) round($cardW * 0.04);
            $badgeY = $cardY + (int) round($cardH * 0.16);

            $badgeColor = imagecolorallocate($srcImg, $badgeR, $badgeG, $badgeB);
            imagefilledrectangle($srcImg, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeColor);
            $badgeTextColor = imagecolorallocate($srcImg, 15, 23, 42);

            if ($useTtf) {
                imagettftext($srcImg, $baseBadgeSize, 0, $badgeX + (int) round($badgeW * 0.18), $badgeY + (int) round($badgeH * 0.72), $badgeTextColor, $fontBold, $badgeText);
            }

            $titleX = $badgeX + $badgeW + (int) round($cardW * 0.03);
            $titleY = $badgeY + (int) round($badgeH * 0.78);
            if ($titleText !== '') {
                if ($useTtf) {
                    if ($hasShadow) {
                        imagettftext($srcImg, $baseTitleSize, 0, $titleX + 2, $titleY + 2, $shadowColor, $fontBold, $titleText);
                    }
                    imagettftext($srcImg, $baseTitleSize, 0, $titleX, $titleY, $textColor, $fontBold, $titleText);
                } else {
                    imagestring($srcImg, 5, $titleX, $titleY - 15, $titleText, $textColor);
                }
            }

            if ($descText !== '') {
                $descX = $badgeX;
                $descY = $badgeY + $badgeH + (int) round($baseDescSize * 1.6);
                $maxW = $cardW - (int) round($cardW * 0.08);
                $lines = $this->wrapText($baseDescSize, $fontFile, $descText, $maxW);
                $lineH = (int) round($baseDescSize * 1.4);

                foreach (array_slice($lines, 0, 2) as $lIndex => $line) {
                    $yPos = $descY + ($lIndex * $lineH);
                    if ($useTtf) {
                        imagettftext($srcImg, $baseDescSize, 0, $descX, $yPos, $textColor, $fontFile, $line);
                    } else {
                        imagestring($srcImg, 3, $descX, $yPos - 10, $line, $textColor);
                    }
                }
            }
        } else {
            $bannerH = (int) round($canvasH * 0.22 * $scaleFactor);
            $bannerY = $canvasH - $bannerH;

            imagefilledrectangle($srcImg, 0, $bannerY, $canvasW, $canvasH, $bgColor);

            $badgeW = (int) round($baseBadgeSize * 3.6);
            $badgeH = (int) round($baseBadgeSize * 2.0);
            $badgeX = (int) round($canvasW * 0.04);
            $badgeY = $bannerY + (int) round($bannerH * 0.16);

            $badgeColor = imagecolorallocate($srcImg, $badgeR, $badgeG, $badgeB);
            imagefilledrectangle($srcImg, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeColor);
            $badgeTextColor = imagecolorallocate($srcImg, 15, 23, 42);

            if ($useTtf) {
                imagettftext($srcImg, $baseBadgeSize, 0, $badgeX + (int) round($badgeW * 0.18), $badgeY + (int) round($badgeH * 0.72), $badgeTextColor, $fontBold, $badgeText);
            }

            $titleX = $badgeX + $badgeW + (int) round($canvasW * 0.03);
            $titleY = $badgeY + (int) round($badgeH * 0.78);
            if ($titleText !== '') {
                if ($useTtf) {
                    if ($hasShadow) {
                        imagettftext($srcImg, $baseTitleSize, 0, $titleX + 2, $titleY + 2, $shadowColor, $fontBold, $titleText);
                    }
                    imagettftext($srcImg, $baseTitleSize, 0, $titleX, $titleY, $textColor, $fontBold, $titleText);
                } else {
                    imagestring($srcImg, 5, $titleX, $titleY - 15, $titleText, $textColor);
                }
            }

            if ($descText !== '') {
                $descX = $badgeX;
                $descY = $badgeY + $badgeH + (int) round($baseDescSize * 1.6);
                $maxW = $canvasW - ($descX * 2);
                $lines = $this->wrapText($baseDescSize, $fontFile, $descText, $maxW);
                $lineH = (int) round($baseDescSize * 1.4);

                foreach (array_slice($lines, 0, 2) as $lIndex => $line) {
                    $yPos = $descY + ($lIndex * $lineH);
                    if ($useTtf) {
                        imagettftext($srcImg, $baseDescSize, 0, $descX, $yPos, $textColor, $fontFile, $line);
                    } else {
                        imagestring($srcImg, 3, $descX, $yPos - 10, $line, $textColor);
                    }
                }
            }
        }

        $saved = imagejpeg($srcImg, $destPath, 92);
        imagedestroy($srcImg);

        return $saved;
    }

    private function wrapText(int $fontSize, string $fontFile, string $text, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
            if (function_exists('imagettfbbox') && file_exists($fontFile)) {
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $testLine);
                $width = abs($bbox[4] - $bbox[0]);
            } else {
                $width = strlen($testLine) * imagefontwidth(4);
            }

            if ($width > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    public function getCollageSettings(string $slug): array
    {
        $project = $this->load($slug);
        if (! $project) {
            return [];
        }

        $saved = is_array($project['collage'] ?? null) ? $project['collage'] : [];

        return [
            'layout' => $saved['layout'] ?? 'auto-grid',
            'header_enabled' => isset($saved['header_enabled']) ? (bool) $saved['header_enabled'] : true,
            'title' => $saved['title'] ?? ($project['name'] ?? 'Recipe Collage'),
            'subtitle' => $saved['subtitle'] ?? 'Step-by-step culinary guide',
            'prep_time' => $saved['prep_time'] ?? '',
            'cook_time' => $saved['cook_time'] ?? '',
            'servings' => $saved['servings'] ?? '',
            'show_brand' => isset($saved['show_brand']) ? (bool) $saved['show_brand'] : true,
            'footer_enabled' => isset($saved['footer_enabled']) ? (bool) $saved['footer_enabled'] : false,
            'footer_text' => $saved['footer_text'] ?? '@RecipeFrameStudio',
            'bg_color' => $saved['bg_color'] ?? '#0f172a',
            'gap' => (int) ($saved['gap'] ?? 16),
            'padding' => (int) ($saved['padding'] ?? 24),
            'format' => $saved['format'] ?? 'jpg',
            'quality' => (int) ($saved['quality'] ?? 92),
            'scale' => (int) ($saved['scale'] ?? 1),
        ];
    }

    public function saveCollageSettings(string $slug, array $data): ?array
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $currentCollage = $this->getCollageSettings($slug);
        $updatedCollage = array_merge($currentCollage, $data);
        $updatedCollage['updated_at'] = date('c');

        $project['collage'] = $updatedCollage;
        $project['updated_at'] = date('c');
        $this->save($slug, $project);

        $outputDir = $this->getProjectPath($slug).DIRECTORY_SEPARATOR.'output';
        if (File::isDirectory($outputDir)) {
            File::cleanDirectory($outputDir);
        }

        return $project['collage'];
    }

    public function getCollageImagePath(string $slug, bool $regenerate = false): ?string
    {
        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $outputDir = $projectPath.DIRECTORY_SEPARATOR.'output';
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true, true);
        }

        $config = $this->getCollageSettings($slug);
        $format = $config['format'] === 'png' ? 'png' : 'jpg';
        $destPath = $outputDir.DIRECTORY_SEPARATOR.'collage.'.$format;

        if (! $regenerate && File::exists($destPath)) {
            return $destPath;
        }

        $selectedFrames = $this->getSelectedFrames($slug);
        $recipeSteps = $this->getRecipeSteps($slug);
        $items = $recipeSteps['items'] ?? [];
        $hasEnabledStepItems = collect($items)->contains(fn ($item) => ! empty($item['enabled']));

        $stepFramePaths = [];
        foreach ($selectedFrames as $index => $frame) {
            $fId = $frame['filename'] ?? ($frame['id'] ?? '');
            $stepItem = collect($items)->first(fn ($it) => ($it['frame_id'] ?? '') === $fId || ($it['filename'] ?? '') === $fId);

            if ($hasEnabledStepItems && $stepItem && empty($stepItem['enabled'])) {
                continue;
            }

            $stepPath = $this->getStepFramePath($slug, $fId, $regenerate);
            if ($stepPath && File::exists($stepPath)) {
                $stepFramePaths[] = $stepPath;
            }
        }

        if (empty($stepFramePaths)) {
            return null;
        }

        $success = $this->renderCollageImage($projectPath, $stepFramePaths, $destPath, $config, $project);

        return $success ? $destPath : null;
    }

    private function renderCollageImage(string $projectPath, array $stepFramePaths, string $destPath, array $config, array $project): bool
    {
        if (! extension_loaded('gd') || empty($stepFramePaths)) {
            return false;
        }

        @ini_set('memory_limit', '512M');

        $firstInfo = @getimagesize($stepFramePaths[0]);
        if (! $firstInfo) {
            return false;
        }

        $origCellW = $firstInfo[0];
        $origCellH = $firstInfo[1];

        $scale = max(1, min(2, (int) ($config['scale'] ?? 1)));
        $N = count($stepFramePaths);
        $baseCellW = (int) round(540 * $scale);
        if ($N > 16) {
            $baseCellW = min($baseCellW, 360);
        }
        $baseCellH = (int) round($baseCellW * ($origCellH / $origCellW));

        $layout = $config['layout'] ?? 'auto-grid';

        if ($layout === 'grid-2x2') {
            $cols = 2;
            $rows = (int) ceil($N / 2);
        } elseif ($layout === 'grid-3x2') {
            $cols = 3;
            $rows = (int) ceil($N / 3);
        } elseif ($layout === 'vertical-story') {
            $cols = 1;
            $rows = $N;
        } elseif ($layout === 'hero-strip') {
            $cols = 2;
            $rows = $N <= 1 ? 1 : (1 + (int) ceil(($N - 1) / 2));
        } else {
            // auto-grid
            if ($N <= 1) {
                $cols = 1;
            } elseif ($N <= 4) {
                $cols = 2;
            } elseif ($N <= 9) {
                $cols = 3;
            } else {
                $cols = 4;
            }
            $rows = (int) ceil($N / $cols);
        }

        $gap = (int) round(($config['gap'] ?? 16) * $scale);
        $padding = (int) round(($config['padding'] ?? 24) * $scale);

        $headerEnabled = ! empty($config['header_enabled']);
        $headerH = $headerEnabled ? (int) round(120 * $scale) : 0;

        $footerEnabled = ! empty($config['footer_enabled']);
        $footerH = $footerEnabled ? (int) round(50 * $scale) : 0;

        $totalW = ($cols * $baseCellW) + (($cols - 1) * $gap) + ($padding * 2);
        if ($layout === 'hero-strip') {
            $totalH = $headerH + ($rows * $baseCellH) + (($rows - 1) * $gap) + ($padding * 2) + $footerH;
        } else {
            $totalH = $headerH + ($rows * $baseCellH) + (($rows - 1) * $gap) + ($padding * 2) + $footerH;
        }

        $canvas = imagecreatetruecolor($totalW, $totalH);
        [$bgR, $bgG, $bgB] = $this->hexToRgb($config['bg_color'] ?? '#0f172a');
        $bgColor = imagecolorallocate($canvas, $bgR, $bgG, $bgB);
        imagefill($canvas, 0, 0, $bgColor);

        $fontBold = 'C:/Windows/Fonts/arialbd.ttf';
        $fontRegular = 'C:/Windows/Fonts/arial.ttf';
        $useTtf = function_exists('imagettftext') && file_exists($fontBold);

        // Render Header
        if ($headerEnabled) {
            $headerTop = $padding;
            $titleText = $config['title'] ?? ($project['name'] ?? 'Recipe Collage');
            $subtitleText = $config['subtitle'] ?? '';

            $titleColor = imagecolorallocate($canvas, 255, 255, 255);
            $subColor = imagecolorallocate($canvas, 148, 163, 184); // slate-400
            $metaPillBg = imagecolorallocate($canvas, 30, 41, 59); // slate-800
            $metaTextColor = imagecolorallocate($canvas, 251, 191, 36); // amber-400

            $titleSize = (int) round(26 * $scale);
            $subSize = (int) round(14 * $scale);

            if ($useTtf) {
                imagettftext($canvas, $titleSize, 0, $padding, $headerTop + (int) round($titleSize * 1.2), $titleColor, $fontBold, $titleText);
                if ($subtitleText !== '') {
                    imagettftext($canvas, $subSize, 0, $padding, $headerTop + (int) round($titleSize * 1.3) + (int) round($subSize * 1.5), $subColor, $fontRegular, $subtitleText);
                }
            } else {
                imagestring($canvas, 5, $padding, $headerTop, $titleText, $titleColor);
                if ($subtitleText !== '') {
                    imagestring($canvas, 3, $padding, $headerTop + 24, $subtitleText, $subColor);
                }
            }

            // Draw Meta Badges on right side of header
            $pills = [];
            if (! empty($config['prep_time'])) {
                $pills[] = 'Prep: '.$config['prep_time'];
            }
            if (! empty($config['cook_time'])) {
                $pills[] = 'Cook: '.$config['cook_time'];
            }
            if (! empty($config['servings'])) {
                $pills[] = 'Serves: '.$config['servings'];
            }

            if (! empty($pills)) {
                $pillX = $totalW - $padding;
                $pillY = $headerTop + (int) round($titleSize * 0.2);
                $pillH = (int) round(28 * $scale);

                foreach (array_reverse($pills) as $pill) {
                    $pillTextW = $useTtf
                        ? abs(imagettfbbox(11 * $scale, 0, $fontBold, $pill)[4] - imagettfbbox(11 * $scale, 0, $fontBold, $pill)[0])
                        : strlen($pill) * 8;
                    $pillW = $pillTextW + (int) round(20 * $scale);
                    $pillX -= $pillW;

                    imagefilledrectangle($canvas, $pillX, $pillY, $pillX + $pillW, $pillY + $pillH, $metaPillBg);
                    if ($useTtf) {
                        imagettftext($canvas, 11 * $scale, 0, $pillX + (int) round(10 * $scale), $pillY + (int) round($pillH * 0.68), $metaTextColor, $fontBold, $pill);
                    } else {
                        imagestring($canvas, 2, $pillX + 5, $pillY + 6, $pill, $metaTextColor);
                    }
                    $pillX -= (int) round(10 * $scale);
                }
            }
        }

        // Render Cells
        foreach ($stepFramePaths as $i => $framePath) {
            if ($layout === 'hero-strip' && $i === 0) {
                $cX = $padding;
                $cY = $padding + $headerH;
                $cW = $totalW - ($padding * 2);
                $cH = $baseCellH;
            } elseif ($layout === 'hero-strip') {
                $subIndex = $i - 1;
                $cCol = $subIndex % 2;
                $cRow = (int) floor($subIndex / 2);
                $cW = (int) floor(($totalW - ($padding * 2) - $gap) / 2);
                $cH = $baseCellH;
                $cX = $padding + ($cCol * ($cW + $gap));
                $cY = $padding + $headerH + $baseCellH + $gap + ($cRow * ($cH + $gap));
            } else {
                $cCol = $i % $cols;
                $cRow = (int) floor($i / $cols);
                $cW = $baseCellW;
                $cH = $baseCellH;
                $cX = $padding + ($cCol * ($baseCellW + $gap));
                $cY = $padding + $headerH + ($cRow * ($baseCellH + $gap));
            }

            $srcCell = @imagecreatefromjpeg($framePath);
            if ($srcCell) {
                $cellOrigW = imagesx($srcCell);
                $cellOrigH = imagesy($srcCell);
                imagecopyresampled($canvas, $srcCell, $cX, $cY, 0, 0, $cW, $cH, $cellOrigW, $cellOrigH);
                imagedestroy($srcCell);
            }
        }

        // Render Footer
        if ($footerEnabled) {
            $footerY = $totalH - $footerH + (int) round(10 * $scale);
            $footerText = $config['footer_text'] ?? '@RecipeFrameStudio';
            $footerColor = imagecolorallocate($canvas, 100, 116, 139); // slate-500
            $footerSize = (int) round(12 * $scale);

            if ($useTtf) {
                $fBbox = imagettfbbox($footerSize, 0, $fontRegular, $footerText);
                $fW = abs($fBbox[4] - $fBbox[0]);
                $fX = (int) round(($totalW - $fW) / 2);
                imagettftext($canvas, $footerSize, 0, $fX, $footerY + $footerSize, $footerColor, $fontRegular, $footerText);
            } else {
                $fX = (int) round(($totalW - (strlen($footerText) * 7)) / 2);
                imagestring($canvas, 3, $fX, $footerY, $footerText, $footerColor);
            }
        }

        $format = $config['format'] === 'png' ? 'png' : 'jpg';
        if ($format === 'png') {
            $saved = imagepng($canvas, $destPath, 8);
        } else {
            $saved = imagejpeg($canvas, $destPath, $config['quality'] ?? 92);
        }

        imagedestroy($canvas);

        return $saved;
    }

    public function createProjectZipArchive(string $slug): ?string
    {
        if (! class_exists('ZipArchive')) {
            return null;
        }

        $project = $this->load($slug);
        if (! $project) {
            return null;
        }

        $projectPath = $this->getProjectPath($slug);
        $outputDir = $projectPath.DIRECTORY_SEPARATOR.'output';
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true, true);
        }

        $zipPath = $outputDir.DIRECTORY_SEPARATOR.Str::slug($slug).'_recipe_bundle.zip';
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        // 1. Add Composite Collage
        $collagePath = $this->getCollageImagePath($slug, true);
        if ($collagePath && File::exists($collagePath)) {
            $ext = pathinfo($collagePath, PATHINFO_EXTENSION);
            $zip->addFile($collagePath, '00_Full_Recipe_Collage.'.$ext);
        }

        // 2. Add Individual Step Cards
        $selectedFrames = $this->getSelectedFrames($slug);
        $recipeSteps = $this->getRecipeSteps($slug);
        $items = $recipeSteps['items'] ?? [];

        $recipeSummaryLines = [
            '====================================================',
            'RECIPE: '.($project['name'] ?? 'Recipe Collage'),
            'Exported: '.date('Y-m-d H:i:s'),
            'Total Steps: '.count($selectedFrames),
            '====================================================',
            '',
        ];

        foreach ($selectedFrames as $index => $frame) {
            $fId = $frame['filename'] ?? ($frame['id'] ?? '');
            $stepItem = collect($items)->first(fn ($it) => ($it['frame_id'] ?? '') === $fId || ($it['filename'] ?? '') === $fId);
            $stepNum = $stepItem['step_number'] ?? ($index + 1);
            $title = $stepItem['title'] ?? 'Step '.$stepNum;
            $desc = $stepItem['description'] ?? '';
            $ingredients = $stepItem['ingredients'] ?? '';

            $safeTitle = Str::slug($title, '_');
            $cardFilename = sprintf('%02d_Step_%s.jpg', $stepNum, $safeTitle ?: 'Photo');

            $stepPath = $this->getStepFramePath($slug, $fId);
            if ($stepPath && File::exists($stepPath)) {
                $zip->addFile($stepPath, 'Steps'.DIRECTORY_SEPARATOR.$cardFilename);
            }

            $recipeSummaryLines[] = sprintf('STEP %d: %s', $stepNum, $title);
            if ($desc !== '') {
                $recipeSummaryLines[] = 'Instructions: '.$desc;
            }
            if ($ingredients !== '') {
                $recipeSummaryLines[] = 'Key Ingredients: '.$ingredients;
            }
            $recipeSummaryLines[] = '----------------------------------------------------';
            $recipeSummaryLines[] = '';
        }

        // 3. Add text summary
        $zip->addFromString('RECIPE_SUMMARY.txt', implode(PHP_EOL, $recipeSummaryLines));

        $zip->close();

        return File::exists($zipPath) ? $zipPath : null;
    }
}
