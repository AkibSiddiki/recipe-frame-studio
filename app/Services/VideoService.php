<?php

namespace App\Services;

class VideoService
{
    public function __construct(private FfmpegService $ffmpeg) {}

    public function analyze(string $videoPath): array
    {
        $metadata = $this->ffmpeg->getVideoMetadata($videoPath);
        if (empty($metadata)) {
            return [];
        }

        $metadata['formatted_file_size'] = $this->formatFileSize($metadata['file_size'] ?? 0);

        if (isset($metadata['width']) && isset($metadata['height'])) {
            $metadata['aspect_ratio'] = $this->getAspectRatio($metadata['width'], $metadata['height']);
            $metadata['is_vertical'] = $this->isVertical($metadata);
        }

        if (isset($metadata['duration'])) {
            $metadata['formatted_duration'] = $this->formatDuration($metadata['duration']);
        }

        return $metadata;
    }

    public function isVertical(array $metadata): bool
    {
        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;

        return $height > $width;
    }

    public function getAspectRatio(int $width, int $height): string
    {
        if ($width === 0 || $height === 0) {
            return '0:0';
        }

        $gcd = function (int $a, int $b) use (&$gcd): int {
            return $b ? $gcd($b, $a % $b) : $a;
        };

        $divisor = $gcd($width, $height);
        $w = $width / $divisor;
        $h = $height / $divisor;

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
        if (abs($ratio - (1)) < 0.05) {
            return '1:1';
        }

        return "{$w}:{$h}";
    }

    public function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', (int) $hours, (int) $minutes, (int) $secs);
        }

        return sprintf('%02d:%02d', (int) $minutes, (int) $secs);
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1).' '.$units[$pow];
    }
}
