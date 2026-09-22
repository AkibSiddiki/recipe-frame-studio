<?php

use App\Services\FfmpegService;
use App\Services\VideoService;

test('it correctly detects vertical video', function () {
    $service = app(VideoService::class);

    expect($service->isVertical(['width' => 1080, 'height' => 1920]))->toBeTrue()
        ->and($service->isVertical(['width' => 1920, 'height' => 1080]))->toBeFalse()
        ->and($service->isVertical(['width' => 1080, 'height' => 1080]))->toBeFalse();
});

test('it calculates simplified aspect ratios accurately', function () {
    $service = app(VideoService::class);

    expect($service->getAspectRatio(1080, 1920))->toBe('9:16')
        ->and($service->getAspectRatio(720, 1280))->toBe('9:16')
        ->and($service->getAspectRatio(1920, 1080))->toBe('16:9')
        ->and($service->getAspectRatio(1080, 1080))->toBe('1:1')
        ->and($service->getAspectRatio(1440, 1080))->toBe('4:3')
        ->and($service->getAspectRatio(0, 0))->toBe('0:0');
});

test('it formats durations properly', function () {
    $service = app(VideoService::class);

    expect($service->formatDuration(65))->toBe('01:05')
        ->and($service->formatDuration(134.5))->toBe('02:14')
        ->and($service->formatDuration(3665))->toBe('01:01:05')
        ->and($service->formatDuration(0))->toBe('00:00');
});

test('it formats file sizes into human readable units', function () {
    $service = app(VideoService::class);

    expect($service->formatFileSize(1024))->toBe('1 KB')
        ->and($service->formatFileSize(10485760))->toBe('10 MB')
        ->and($service->formatFileSize(52428800))->toBe('50 MB')
        ->and($service->formatFileSize(0))->toBe('0 B');
});

test('it analyzes video metadata and enriches it', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')
        ->with('test-video.mp4')
        ->once()
        ->andReturn([
            'duration' => 125.0,
            'width' => 1080,
            'height' => 1920,
            'fps' => 30.0,
            'codec' => 'h264',
            'bit_rate' => 5000000,
            'file_size' => 20971520,
        ]);

    $service = new VideoService($mockFfmpeg);
    $result = $service->analyze('test-video.mp4');

    expect($result)->toHaveKey('formatted_duration', '02:05')
        ->and($result)->toHaveKey('formatted_file_size', '20 MB')
        ->and($result)->toHaveKey('aspect_ratio', '9:16')
        ->and($result)->toHaveKey('is_vertical', true);
});
