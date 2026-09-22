<?php

use App\Services\ProjectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('create project view renders successfully', function () {
    $response = $this->get('/project/create');

    $response->assertStatus(200)
        ->assertSee('New Project')
        ->assertSee('Click to select a video file');
});

test('store requires a valid video path', function () {
    $response = $this->post('/project', [
        'video_path' => '',
    ]);

    $response->assertSessionHasErrors('video_path');
});

test('store rejects non-existent video files', function () {
    $response = $this->post('/project', [
        'video_path' => 'C:\\non_existent\\path\\video.mp4',
    ]);

    $response->assertSessionHasErrors(['video_path' => 'The selected video file does not exist.']);
});

test('store rejects unsupported video extensions', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_').'.txt';
    file_put_contents($tempFile, 'dummy content');

    $response = $this->post('/project', [
        'video_path' => $tempFile,
    ]);

    $response->assertSessionHasErrors(['video_path']);

    unlink($tempFile);
});

test('store creates project and redirects to show', function () {
    $tempVideo = tempnam(sys_get_temp_dir(), 'test_video_').'.mp4';
    file_put_contents($tempVideo, 'video data');

    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('create')
        ->once()
        ->andReturn([
            'slug' => 'sample-recipe',
            'name' => 'Sample Recipe',
        ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->post('/project', [
        'video_path' => $tempVideo,
        'name' => 'Sample Recipe',
    ]);

    $response->assertRedirect('/project/sample-recipe');

    unlink($tempVideo);
});

test('store accepts uploaded video file and redirects to show', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('cooking-test.mp4', 500, 'video/mp4');

    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('create')
        ->once()
        ->andReturn([
            'slug' => 'cooking-test',
            'name' => 'Cooking Test',
        ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->post('/project', [
        'video_file' => $file,
    ]);

    $response->assertRedirect('/project/cooking-test');
});

test('show returns 404 for non-existent project', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('load')->with('missing-slug')->andReturn(null);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->get('/project/missing-slug');

    $response->assertStatus(404);
});

test('show renders project workspace with video metadata', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('load')->with('garlic-bhorta')->andReturn([
        'name' => 'Garlic Bhorta',
        'slug' => 'garlic-bhorta',
        'video' => [
            'filename' => 'garlic-bhorta.mp4',
            'duration' => 134.5,
            'formatted_duration' => '02:14',
            'width' => 1080,
            'height' => 1920,
            'fps' => 30.0,
            'aspect_ratio' => '9:16',
            'formatted_file_size' => '45.6 MB',
            'codec' => 'h264',
            'is_vertical' => true,
        ],
    ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->get('/project/garlic-bhorta');

    $response->assertStatus(200)
        ->assertSee('Garlic Bhorta')
        ->assertSee('garlic-bhorta.mp4')
        ->assertSee('02:14')
        ->assertSee('1080 × 1920')
        ->assertSee('9:16 Vertical');
});

test('show displays warning for non-vertical video', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('load')->with('landscape-recipe')->andReturn([
        'name' => 'Landscape Recipe',
        'slug' => 'landscape-recipe',
        'video' => [
            'filename' => 'landscape.mp4',
            'width' => 1920,
            'height' => 1080,
            'fps' => 30.0,
            'aspect_ratio' => '16:9',
            'is_vertical' => false,
        ],
    ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->get('/project/landscape-recipe');

    $response->assertStatus(200)
        ->assertSee('Non-vertical Video Detected');
});

test('video info api returns project metadata', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('load')->with('garlic-bhorta')->andReturn([
        'slug' => 'garlic-bhorta',
        'video' => [
            'width' => 1080,
            'height' => 1920,
        ],
    ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->getJson('/project/garlic-bhorta/video-info');

    $response->assertStatus(200)
        ->assertJson(['width' => 1080, 'height' => 1920]);
});

test('native dialog api endpoints return json responses', function () {
    $response1 = $this->getJson('/api/dialog/open-video');
    $response1->assertStatus(200)->assertJsonStructure(['path']);

    $response2 = $this->getJson('/api/dialog/open-ffmpeg');
    $response2->assertStatus(200)->assertJsonStructure(['path']);
});
