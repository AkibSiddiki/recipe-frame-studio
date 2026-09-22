<?php

use App\Services\AppSettingService;
use App\Services\FfmpegService;
use Illuminate\Support\Facades\Process;

test('it reads effective path from settings first', function () {
    $tempFfmpeg = tempnam(sys_get_temp_dir(), 'ffmpeg_test_');
    file_put_contents($tempFfmpeg, 'binary');

    $settings = app(AppSettingService::class);
    $settings->set('ffmpeg_path', $tempFfmpeg);

    $service = new FfmpegService;
    expect($service->getEffectivePath())->toBe($tempFfmpeg)
        ->and($service->detect())->toBe($tempFfmpeg);

    unlink($tempFfmpeg);
});

test('it checks availability using process runner', function () {
    Process::fake([
        '* -version' => Process::result(output: 'ffmpeg version 6.1.1-full_build-www.gyan.dev', exitCode: 0),
    ]);

    $tempFfmpeg = tempnam(sys_get_temp_dir(), 'ffmpeg_avail_');
    file_put_contents($tempFfmpeg, 'binary');

    $settings = app(AppSettingService::class);
    $settings->set('ffmpeg_path', $tempFfmpeg);

    $service = new FfmpegService;
    expect($service->isAvailable())->toBeTrue()
        ->and($service->getVersion())->toContain('ffmpeg version 6.1.1');

    unlink($tempFfmpeg);
});

test('it derives ffprobe path from ffmpeg directory', function () {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ffmpeg_probe_test_'.uniqid();
    mkdir($tempDir);

    $ffmpegPath = $tempDir.DIRECTORY_SEPARATOR.'ffmpeg.exe';
    $ffprobePath = $tempDir.DIRECTORY_SEPARATOR.'ffprobe.exe';
    file_put_contents($ffmpegPath, 'dummy');
    file_put_contents($ffprobePath, 'dummy');

    $settings = app(AppSettingService::class);
    $settings->set('ffmpeg_path', $ffmpegPath);

    $service = new FfmpegService;
    expect($service->getFfprobePath())->toBe($ffprobePath);

    unlink($ffmpegPath);
    unlink($ffprobePath);
    rmdir($tempDir);
});

test('it parses ffprobe json output into metadata correctly', function () {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'probe_json_'.uniqid();
    mkdir($tempDir);

    $ffmpegPath = $tempDir.DIRECTORY_SEPARATOR.'ffmpeg.exe';
    $ffprobePath = $tempDir.DIRECTORY_SEPARATOR.'ffprobe.exe';
    file_put_contents($ffmpegPath, 'dummy');
    file_put_contents($ffprobePath, 'dummy');

    $settings = app(AppSettingService::class);
    $settings->set('ffmpeg_path', $ffmpegPath);

    $fakeOutput = json_encode([
        'streams' => [
            [
                'codec_type' => 'video',
                'codec_name' => 'h264',
                'width' => 1080,
                'height' => 1920,
                'r_frame_rate' => '30/1',
                'duration' => '134.500000',
                'bit_rate' => '6500000',
            ],
        ],
        'format' => [
            'duration' => '134.500000',
            'size' => '45678912',
            'bit_rate' => '6500000',
        ],
    ]);

    Process::fake([
        '* -show_streams *' => Process::result(output: $fakeOutput, exitCode: 0),
    ]);

    $service = new FfmpegService;
    $meta = $service->getVideoMetadata('dummy.mp4');

    expect($meta)->toHaveKey('duration', 134.5)
        ->and($meta)->toHaveKey('width', 1080)
        ->and($meta)->toHaveKey('height', 1920)
        ->and($meta)->toHaveKey('fps', 30.0)
        ->and($meta)->toHaveKey('codec', 'h264')
        ->and($meta)->toHaveKey('aspect_ratio', '9:16')
        ->and($meta)->toHaveKey('is_vertical', true)
        ->and($meta)->toHaveKey('formatted_duration', '02:14');

    unlink($ffmpegPath);
    unlink($ffprobePath);
    rmdir($tempDir);
});
