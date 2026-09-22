<?php

use App\Services\AppSettingService;
use App\Services\FfmpegService;
use App\Services\ProjectService;

test('settings page renders successfully', function () {
    $response = $this->get('/settings');

    $response->assertStatus(200)
        ->assertSee('FFmpeg Configuration')
        ->assertSee('Default Output Settings');
});

test('it updates application settings successfully', function () {
    $response = $this->post('/settings', [
        'ffmpeg_path' => 'C:\\ffmpeg\\bin\\ffmpeg.exe',
        'default_format' => 'webp',
        'default_quality' => 85,
        'default_crop_ratio' => '4:5',
    ]);

    $response->assertRedirect('/settings')
        ->assertSessionHas('status', 'Settings updated successfully.');

    $service = app(AppSettingService::class);
    expect($service->get('ffmpeg_path'))->toBe('C:\\ffmpeg\\bin\\ffmpeg.exe')
        ->and($service->get('default_format'))->toBe('webp')
        ->and($service->get('default_quality'))->toBe(85)
        ->and($service->get('default_crop_ratio'))->toBe('4:5');
});

test('it validates setting inputs', function () {
    $response = $this->post('/settings', [
        'default_format' => 'invalid_format',
        'default_quality' => 150, // exceeds 100
        'default_crop_ratio' => 'invalid_ratio',
    ]);

    $response->assertSessionHasErrors(['default_format', 'default_quality', 'default_crop_ratio']);
});

test('it returns json from ffmpeg detect endpoint', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('detectPath')->andReturn('C:\\ffmpeg\\bin\\ffmpeg.exe');
    $this->app->instance(FfmpegService::class, $mockFfmpeg);

    $response = $this->postJson('/settings/ffmpeg-detect');

    $response->assertStatus(200)
        ->assertJson(['path' => 'C:\\ffmpeg\\bin\\ffmpeg.exe']);
});

test('it returns status from ffmpeg status endpoint', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getStatus')->andReturn([
        'is_available' => true,
        'version' => 'ffmpeg version 6.0',
        'path' => 'C:\\ffmpeg\\bin\\ffmpeg.exe',
    ]);
    $this->app->instance(FfmpegService::class, $mockFfmpeg);

    $response = $this->getJson('/settings/ffmpeg-status');

    $response->assertStatus(200)
        ->assertJson([
            'is_available' => true,
            'version' => 'ffmpeg version 6.0',
        ]);
});

test('settings page renders danger zone', function () {
    $response = $this->get('/settings');

    $response->assertStatus(200)
        ->assertSee('Danger Zone')
        ->assertSee('Clear All App Data');
});

test('clear data purges projects and redirects with status', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('deleteAll')->once()->andReturn(3);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->post('/settings/clear-data');

    $response->assertRedirect('/settings')
        ->assertSessionHas('status', 'Application data cleared: 3 project(s) and temporary files were deleted.');
});
