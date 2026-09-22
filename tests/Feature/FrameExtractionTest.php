<?php

use App\Services\FfmpegService;
use App\Services\ProjectService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('testing_frames_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

function createDummyProject(string $slug = 'cooking-pasta'): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'thumbnails');

    $dummyVideo = $projectPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'pasta.mp4';
    File::put($dummyVideo, 'dummy video binary content');

    // Create a dummy frame
    $dummyFrame = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.'frame_0001.jpg';
    File::put($dummyFrame, 'dummy image content');

    $projectData = [
        'name' => 'Cooking Pasta',
        'slug' => $slug,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'video' => [
            'filename' => 'pasta.mp4',
            'original_path' => $dummyVideo,
            'duration' => 60.0,
            'formatted_duration' => '01:00',
            'width' => 1080,
            'height' => 1920,
            'fps' => 30.0,
            'is_vertical' => true,
        ],
        'frames' => [
            [
                'id' => 'frame_0001',
                'filename' => 'frame_0001.jpg',
                'timestamp' => 2.5,
                'formatted_time' => '00:02',
                'selected' => true,
                'width' => 1080,
                'height' => 1920,
            ],
        ],
    ];

    $projectService->save($slug, $projectData);

    return $projectData;
}

test('frames view renders successfully for existing project', function () {
    createDummyProject('pasta-recipe');

    $response = $this->get('/project/pasta-recipe/frames');

    $response->assertStatus(200)
        ->assertSee('Cooking Pasta')
        ->assertSee('Step 2: Frames')
        ->assertSee('00:02')
        ->assertSee('frame_0001.jpg');
});

test('extract frames endpoint triggers ffmpeg and updates project json', function () {
    $projectData = createDummyProject('recipe-test');

    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('extractFrames')
        ->once()
        ->andReturnUsing(function ($videoPath, $outputPattern, $interval) {
            $projectService = app(ProjectService::class);
            $framesDir = $projectService->getProjectPath('recipe-test').DIRECTORY_SEPARATOR.'frames';
            File::put($framesDir.DIRECTORY_SEPARATOR.'frame_0001.jpg', 'frame1');
            File::put($framesDir.DIRECTORY_SEPARATOR.'frame_0002.jpg', 'frame2');

            return true;
        });
    $mockFfmpeg->shouldReceive('formatDuration')
        ->andReturn('00:02', '00:04');

    $this->app->instance(FfmpegService::class, $mockFfmpeg);

    $response = $this->post('/project/recipe-test/extract-frames', [
        'target_count' => 24,
    ]);

    $response->assertRedirect('/project/recipe-test/frames');

    $project = app(ProjectService::class)->load('recipe-test');
    expect($project['frames'])->toHaveCount(2)
        ->and($project['frames'][0]['filename'])->toBe('frame_0001.jpg');
});

test('frame image endpoint returns 200 binary response for valid frame', function () {
    createDummyProject('image-test');

    $response = $this->get('/project/image-test/frame-image/frame_0001.jpg');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('frame image endpoint returns 404 for non-existent frame', function () {
    createDummyProject('image-test-missing');

    $response = $this->get('/project/image-test-missing/frame-image/non_existent.jpg');

    $response->assertStatus(404);
});

test('toggle frame endpoint flips frame selection state', function () {
    createDummyProject('toggle-test');

    $response = $this->postJson('/project/toggle-test/frames/toggle', [
        'frame_id' => 'frame_0001',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'frame' => [
                'id' => 'frame_0001',
                'selected' => false,
            ],
        ]);

    $project = app(ProjectService::class)->load('toggle-test');
    expect($project['frames'][0]['selected'])->toBeFalse();
});

test('capture at timestamp extracts frame and adds to project', function () {
    createDummyProject('capture-test');

    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('extractFrameAt')
        ->once()
        ->andReturnUsing(function ($videoPath, $timestamp, $outputPath) {
            File::put($outputPath, 'custom frame image');

            return true;
        });
    $mockFfmpeg->shouldReceive('formatDuration')
        ->with(15.5)
        ->andReturn('00:15');

    $this->app->instance(FfmpegService::class, $mockFfmpeg);

    $response = $this->postJson('/project/capture-test/frames/capture-at', [
        'timestamp' => 15.5,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'frame' => [
                'timestamp' => 15.5,
                'formatted_time' => '00:15',
                'selected' => true,
            ],
        ]);

    $project = app(ProjectService::class)->load('capture-test');
    expect($project['frames'])->toHaveCount(2);
});

test('delete frame endpoint removes frame from disk and project', function () {
    createDummyProject('delete-test');

    $response = $this->deleteJson('/project/delete-test/frames/frame_0001.jpg');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $project = app(ProjectService::class)->load('delete-test');
    expect($project['frames'])->toHaveCount(0);
});
