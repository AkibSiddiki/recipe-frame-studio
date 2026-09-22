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
                'text' => '@RecipeFrameStudio',
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

        $project['watermark'] = [
            'enabled' => $enabled,
            'type' => $type,
            'text' => $config['text'] ?? ($currentWatermark['text'] ?? '@RecipeFrameStudio'),
            'image_path' => $config['image_path'] ?? ($currentWatermark['image_path'] ?? null),
            'position' => $config['position'] ?? ($currentWatermark['position'] ?? 'bottom-right'),
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

        // Clear existing cached watermarked frames
        if (File::isDirectory($watermarkedDir)) {
            File::cleanDirectory($watermarkedDir);
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
}
