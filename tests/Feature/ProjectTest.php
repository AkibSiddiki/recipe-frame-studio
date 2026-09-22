<?php

use App\Services\FfmpegService;
use App\Services\ProjectService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempTestDir = storage_path('testing_projects_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempTestDir]);
    File::ensureDirectoryExists($this->tempTestDir);

    // Create a dummy video file
    $this->dummyVideo = $this->tempTestDir.DIRECTORY_SEPARATOR.'sample-cooking.mp4';
    File::put($this->dummyVideo, 'dummy video binary content');
});

afterEach(function () {
    if (File::exists($this->tempTestDir)) {
        File::deleteDirectory($this->tempTestDir);
    }
});

test('it creates project with predictable directory structure and project.json', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')->andReturn([
        'duration' => 90.0,
        'width' => 1080,
        'height' => 1920,
        'fps' => 30.0,
        'codec' => 'h264',
        'bit_rate' => 4000000,
        'file_size' => 12345,
    ]);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $projectService = new ProjectService($mockFfmpeg);
    $project = $projectService->create($this->dummyVideo, 'Garlic Bhorta');

    expect($project)->toHaveKey('name', 'Garlic Bhorta')
        ->and($project)->toHaveKey('slug', 'garlic-bhorta')
        ->and($project['video'])->toHaveKey('filename', 'sample-cooking.mp4')
        ->and($project['crop'])->toHaveKey('ratio', '4:5');

    $projectPath = $projectService->getProjectPath('garlic-bhorta');
    expect(File::isDirectory($projectPath.DIRECTORY_SEPARATOR.'source'))->toBeTrue()
        ->and(File::isDirectory($projectPath.DIRECTORY_SEPARATOR.'frames'))->toBeTrue()
        ->and(File::isDirectory($projectPath.DIRECTORY_SEPARATOR.'thumbnails'))->toBeTrue()
        ->and(File::isDirectory($projectPath.DIRECTORY_SEPARATOR.'output'))->toBeTrue()
        ->and(File::isDirectory($projectPath.DIRECTORY_SEPARATOR.'temp'))->toBeTrue()
        ->and(File::exists($projectPath.DIRECTORY_SEPARATOR.'project.json'))->toBeTrue()
        ->and(File::exists($projectPath.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'sample-cooking.mp4'))->toBeTrue();
});

test('it handles duplicate names by appending incrementing numbers', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')->andReturn([
        'duration' => 10.0,
        'width' => 1080,
        'height' => 1920,
        'fps' => 30.0,
        'codec' => 'h264',
        'bit_rate' => 1000,
        'file_size' => 100,
    ]);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $projectService = new ProjectService($mockFfmpeg);

    $p1 = $projectService->create($this->dummyVideo, 'Chicken Recipe');
    $p2 = $projectService->create($this->dummyVideo, 'Chicken Recipe');
    $p3 = $projectService->create($this->dummyVideo, 'Chicken Recipe');

    expect($p1['slug'])->toBe('chicken-recipe')
        ->and($p2['slug'])->toBe('chicken-recipe-2')
        ->and($p3['slug'])->toBe('chicken-recipe-3');
});

test('it loads and saves project data', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')->andReturn(['duration' => 10, 'width' => 1080, 'height' => 1920]);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $projectService = new ProjectService($mockFfmpeg);
    $project = $projectService->create($this->dummyVideo, 'Beef Curry');

    $loaded = $projectService->load('beef-curry');
    expect($loaded)->not->toBeNull()
        ->and($loaded['name'])->toBe('Beef Curry');

    $loaded['notes'] = 'Added some spices';
    $projectService->save('beef-curry', $loaded);

    $reloaded = $projectService->load('beef-curry');
    expect($reloaded['notes'])->toBe('Added some spices');
});

test('it lists recent projects sorted by updated_at', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')->andReturn(['duration' => 10, 'width' => 1080, 'height' => 1920]);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $projectService = new ProjectService($mockFfmpeg);

    $projectService->create($this->dummyVideo, 'First Project');
    sleep(1);
    $projectService->create($this->dummyVideo, 'Second Project');

    $recent = $projectService->getRecent(5);

    expect(count($recent))->toBe(2)
        ->and($recent[0]['name'])->toBe('Second Project')
        ->and($recent[1]['name'])->toBe('First Project');
});

test('it can delete a project directory completely', function () {
    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('getVideoMetadata')->andReturn(['duration' => 10, 'width' => 1080, 'height' => 1920]);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $projectService = new ProjectService($mockFfmpeg);
    $projectService->create($this->dummyVideo, 'To Delete');

    expect($projectService->exists('to-delete'))->toBeTrue();

    $deleted = $projectService->delete('to-delete');
    expect($deleted)->toBeTrue()
        ->and($projectService->exists('to-delete'))->toBeFalse();
});
