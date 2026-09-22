<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class FfmpegService
{
    public function __construct() {}

    public function detect(): ?string
    {
        $path = $this->getEffectivePath();
        if ($path) {
            return $path;
        }

        $commonPaths = [
            'C:\ffmpeg\bin\ffmpeg.exe',
            'C:\Program Files\FFmpeg\bin\ffmpeg.exe',
            'C:\Program Files (x86)\FFmpeg\bin\ffmpeg.exe',
        ];

        foreach ($commonPaths as $commonPath) {
            if (file_exists($commonPath)) {
                return $commonPath;
            }
        }

        $process = Process::run('where ffmpeg');
        if ($process->successful()) {
            $output = trim($process->output());
            $paths = explode(PHP_EOL, $output);
            if (! empty($paths) && file_exists(trim($paths[0]))) {
                return trim($paths[0]);
            }
        }

        return null;
    }

    public function getFfprobePath(): ?string
    {
        $ffmpegPath = $this->detect();
        if (! $ffmpegPath) {
            return null;
        }

        $dir = dirname($ffmpegPath);
        $ffprobePath = $dir.DIRECTORY_SEPARATOR.'ffprobe.exe';

        if (file_exists($ffprobePath)) {
            return $ffprobePath;
        }

        $ffprobePath = $dir.DIRECTORY_SEPARATOR.'ffprobe';
        if (file_exists($ffprobePath)) {
            return $ffprobePath;
        }

        return null;
    }

    public function getEffectivePath(): ?string
    {
        $appSetting = app(AppSettingService::class);
        $dbPath = $appSetting->get('ffmpeg_path');
        if ($dbPath && file_exists($dbPath)) {
            return $dbPath;
        }

        $configPath = config('recipe-studio.ffmpeg_path');
        if ($configPath && file_exists($configPath)) {
            return $configPath;
        }

        return null;
    }

    public function isAvailable(): bool
    {
        $path = $this->detect();
        if (! $path) {
            return false;
        }

        $process = Process::run([$path, '-version']);

        return $process->successful();
    }

    public function getVersion(): ?string
    {
        $path = $this->detect();
        if (! $path) {
            return null;
        }

        $process = Process::run([$path, '-version']);
        if ($process->successful()) {
            $output = $process->output();
            $lines = explode(PHP_EOL, $output);
            if (count($lines) > 0) {
                return trim($lines[0]);
            }
        }

        return null;
    }

    public function detectPath(): ?string
    {
        return $this->detect();
    }

    public function getStatus(): array
    {
        $isAvailable = $this->isAvailable();

        return [
            'is_available' => $isAvailable,
            'version' => $this->getVersion(),
            'path' => $this->getEffectivePath() ?: $this->detect(),
        ];
    }

    public function getVideoMetadata(string $videoPath): array
    {
        $ffprobePath = $this->getFfprobePath();
        if (! $ffprobePath) {
            Log::error('ffprobe not found for metadata extraction.');

            return [];
        }

        $process = Process::run([
            $ffprobePath,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $videoPath,
        ]);

        if (! $process->successful()) {
            Log::error('Failed to extract video metadata', [
                'error' => $process->errorOutput(),
                'video' => $videoPath,
            ]);

            return [];
        }

        $data = json_decode($process->output(), true);
        if (! $data) {
            return [];
        }

        $videoStream = null;
        if (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }
        }

        if (! $videoStream) {
            return [];
        }

        $format = $data['format'] ?? [];

        $duration = (float) ($format['duration'] ?? $videoStream['duration'] ?? 0);
        $width = (int) ($videoStream['width'] ?? 0);
        $height = (int) ($videoStream['height'] ?? 0);
        $fpsRaw = $videoStream['r_frame_rate'] ?? '0/1';
        $fpsParts = explode('/', $fpsRaw);
        $fps = 0;
        if (count($fpsParts) === 2 && (int) $fpsParts[1] !== 0) {
            $fps = (float) $fpsParts[0] / (float) $fpsParts[1];
        } else {
            $fps = (float) $fpsParts[0];
        }

        $codec = $videoStream['codec_name'] ?? 'unknown';
        $bitRate = (int) ($format['bit_rate'] ?? $videoStream['bit_rate'] ?? 0);
        $fileSize = (int) ($format['size'] ?? 0);

        if ($fileSize === 0 && file_exists($videoPath)) {
            $fileSize = filesize($videoPath);
        }

        $aspectRatio = $this->calculateAspectRatio($width, $height);
        $isVertical = $height > $width;
        $formattedDuration = $this->formatDuration($duration);

        return [
            'duration' => $duration,
            'width' => $width,
            'height' => $height,
            'fps' => $fps,
            'codec' => $codec,
            'bit_rate' => $bitRate,
            'aspect_ratio' => $aspectRatio,
            'file_size' => $fileSize,
            'formatted_duration' => $formattedDuration,
            'is_vertical' => $isVertical,
        ];
    }

    public function generateThumbnail(string $videoPath, float $timestamp, string $outputPath, int $width = 320): bool
    {
        $ffmpegPath = $this->detect();
        if (! $ffmpegPath) {
            Log::error('ffmpeg not found for thumbnail generation.');

            return false;
        }

        $process = Process::run([
            $ffmpegPath,
            '-y',
            '-ss', (string) $timestamp,
            '-i', $videoPath,
            '-vframes', '1',
            '-vf', "scale={$width}:-1",
            '-q:v', '2',
            $outputPath,
        ]);

        if (! $process->successful()) {
            Log::error('Failed to generate thumbnail', [
                'error' => $process->errorOutput(),
                'video' => $videoPath,
                'output' => $outputPath,
            ]);

            return false;
        }

        return file_exists($outputPath);
    }

    public function extractFrames(string $videoPath, string $outputPattern, float $interval = 1.0, int $quality = 2): bool
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $ffmpegPath = $this->detect();
        if (! $ffmpegPath) {
            Log::error('ffmpeg not found for frame extraction.');

            return false;
        }

        $fpsExpr = 'fps=1/'.max(0.1, $interval);

        $process = Process::timeout(600)->run([
            $ffmpegPath,
            '-y',
            '-threads', '0',
            '-i', $videoPath,
            '-vf', $fpsExpr,
            '-q:v', (string) $quality,
            $outputPattern,
        ]);

        if (! $process->successful()) {
            Log::error('Failed to extract frames', [
                'error' => $process->errorOutput(),
                'video' => $videoPath,
                'pattern' => $outputPattern,
            ]);

            return false;
        }

        return true;
    }

    public function extractFrameAt(string $videoPath, float $timestamp, string $outputPath, int $quality = 2): bool
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $ffmpegPath = $this->detect();
        if (! $ffmpegPath) {
            Log::error('ffmpeg not found for extracting frame at timestamp.');

            return false;
        }

        $process = Process::timeout(60)->run([
            $ffmpegPath,
            '-y',
            '-ss', (string) max(0, $timestamp),
            '-i', $videoPath,
            '-vframes', '1',
            '-q:v', (string) $quality,
            $outputPath,
        ]);

        if (! $process->successful()) {
            Log::error('Failed to extract frame at timestamp', [
                'error' => $process->errorOutput(),
                'video' => $videoPath,
                'timestamp' => $timestamp,
                'output' => $outputPath,
            ]);

            return false;
        }

        return file_exists($outputPath);
    }

    private function calculateAspectRatio(int $width, int $height): string
    {
        if ($width === 0 || $height === 0) {
            return '0:0';
        }
        $gcd = $this->gcd($width, $height);
        $w = $width / $gcd;
        $h = $height / $gcd;

        $ratio = $w / $h;
        if (abs($ratio - (16 / 9)) < 0.05) {
            return '16:9';
        }
        if (abs($ratio - (9 / 16)) < 0.05) {
            return '9:16';
        }
        if (abs($ratio - (4 / 3)) < 0.05) {
            return '4:3';
        }
        if (abs($ratio - (1 / 1)) < 0.05) {
            return '1:1';
        }

        return "{$w}:{$h}";
    }

    private function gcd(int $a, int $b): int
    {
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }

    public function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
