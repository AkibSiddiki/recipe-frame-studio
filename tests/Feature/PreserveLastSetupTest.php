<?php

use App\Services\AppSettingService;
use App\Services\FfmpegService;
use App\Services\ProjectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('testing_preserve_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);

    $this->settingService = app(AppSettingService::class);
    $this->settingService->remove('last_crop_setup');
    $this->settingService->remove('last_watermark_setup');
    $this->settingService->remove('last_recipe_steps_setup');
    $this->settingService->remove('last_collage_setup');
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

function createTestingProject(string $slug = 'test-preserve-project'): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'cropped');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'watermarked');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'steps');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'output');

    $projectData = [
        'name' => 'Preserve Test Project',
        'slug' => $slug,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'video' => [
            'filename' => 'test.mp4',
            'duration' => 30.0,
            'width' => 1920,
            'height' => 1080,
        ],
        'frames' => [
            [
                'id' => 'frame_0001',
                'filename' => 'frame_0001.jpg',
                'timestamp' => 1.5,
                'formatted_time' => '00:01',
                'selected' => true,
                'width' => 400,
                'height' => 300,
            ],
        ],
        'crop' => [
            'ratio' => '4:5',
            'preset' => 'facebook-portrait',
            'default_alignment' => 'center',
            'width' => 1080,
            'height' => 1350,
        ],
        'watermark' => [
            'enabled' => true,
            'type' => 'text',
            'text' => '@TestHandle',
            'position' => 'bottom-right',
            'opacity' => 80,
            'size' => 15,
            'margin' => 30,
            'color' => '#ffffff',
            'has_shadow' => true,
            'has_pill' => false,
        ],
        'steps' => [],
        'collage' => [],
    ];

    $projectService->save($slug, $projectData);

    return $projectData;
}

test('saving crop settings updates last_crop_setup', function () {
    createTestingProject('crop-test');
    $service = app(ProjectService::class);

    $service->saveCropSettings('crop-test', [
        'ratio' => '1:1',
        'preset' => 'square',
        'default_alignment' => 'top',
        'width' => 1080,
        'height' => 1080,
    ]);

    $lastCrop = $this->settingService->getLastCropSetup();
    expect($lastCrop)->not->toBeNull()
        ->and($lastCrop['ratio'])->toBe('1:1')
        ->and($lastCrop['preset'])->toBe('square')
        ->and($lastCrop['default_alignment'])->toBe('top');
});

test('saving watermark settings updates last_watermark_setup', function () {
    createTestingProject('wm-test');
    $service = app(ProjectService::class);

    $service->saveWatermarkSettings('wm-test', [
        'enabled' => true,
        'type' => 'image',
        'image_path' => 'images/custom-logo.png',
        'position' => 'bottom-left',
        'opacity' => 65,
        'size' => 20,
        'margin' => 250,
        'color' => '#f59e0b',
        'has_shadow' => false,
        'has_pill' => true,
    ]);

    $lastWm = $this->settingService->getLastWatermarkSetup();
    expect($lastWm)->not->toBeNull()
        ->and($lastWm['position'])->toBe('bottom-left')
        ->and($lastWm['opacity'])->toBe(65)
        ->and($lastWm['margin'])->toBe(250)
        ->and($lastWm['has_pill'])->toBeTrue();
});

test('uploading watermark logo updates last_watermark_setup and copies to shared storage', function () {
    createTestingProject('wm-logo-test');
    $service = app(ProjectService::class);

    $file = UploadedFile::fake()->image('my_logo.png', 200, 200);
    $relPath = $service->uploadWatermarkLogo('wm-logo-test', $file);

    expect($relPath)->toBeString()->and($relPath)->toStartWith('watermark/logo_');

    $lastWm = $this->settingService->getLastWatermarkSetup();
    expect($lastWm)->not->toBeNull()
        ->and($lastWm['type'])->toBe('image')
        ->and($lastWm['image_path'])->toBe($relPath);

    $sharedFile = storage_path('app'.DIRECTORY_SEPARATOR.'watermarks'.DIRECTORY_SEPARATOR.basename($relPath));
    expect(File::exists($sharedFile))->toBeTrue();
});

test('saving recipe steps updates last_recipe_steps_setup', function () {
    createTestingProject('steps-test');
    $service = app(ProjectService::class);

    $style = [
        'layout' => 'top-banner',
        'bg_opacity' => 0,
        'title_padding' => 106,
        'text_align' => 'center',
        'show_badge' => false,
        'has_shadow' => true,
    ];

    $service->saveRecipeSteps('steps-test', [
        'style' => $style,
        'items' => [],
    ]);

    $lastStyle = $this->settingService->getLastRecipeStepsSetup();
    expect($lastStyle)->not->toBeNull()
        ->and($lastStyle['layout'])->toBe('top-banner')
        ->and($lastStyle['bg_opacity'])->toBe(0)
        ->and($lastStyle['title_padding'])->toBe(106)
        ->and($lastStyle['text_align'])->toBe('center')
        ->and($lastStyle['show_badge'])->toBeFalse();
});

test('saving collage settings updates last_collage_setup', function () {
    createTestingProject('collage-test');
    $service = app(ProjectService::class);

    $service->saveCollageSettings('collage-test', [
        'layout' => 'hero-strip',
        'gap' => 20,
        'padding' => 32,
        'format' => 'png',
        'quality' => 95,
        'footer_enabled' => true,
        'footer_text' => '@MyCustomBrand',
    ]);

    $lastCollage = $this->settingService->getLastCollageSetup();
    expect($lastCollage)->not->toBeNull()
        ->and($lastCollage['layout'])->toBe('hero-strip')
        ->and($lastCollage['gap'])->toBe(20)
        ->and($lastCollage['padding'])->toBe(32)
        ->and($lastCollage['format'])->toBe('png')
        ->and($lastCollage['footer_enabled'])->toBeTrue()
        ->and($lastCollage['footer_text'])->toBe('@MyCustomBrand');
});

test('apply-last-setup endpoint applies last setup to project', function () {
    createTestingProject('apply-test');

    $this->settingService->setLastCropSetup([
        'ratio' => '16:9',
        'preset' => 'landscape',
        'default_alignment' => 'bottom',
        'width' => 1920,
        'height' => 1080,
    ]);

    $response = $this->postJson(route('project.apply-last-setup', ['slug' => 'apply-test', 'type' => 'crop']));
    $response->assertOk()
        ->assertJsonPath('success', true);

    $service = app(ProjectService::class);
    $loaded = $service->load('apply-test');
    expect($loaded['crop']['ratio'])->toBe('16:9')
        ->and($loaded['crop']['preset'])->toBe('landscape')
        ->and($loaded['crop']['default_alignment'])->toBe('bottom');
});

test('creating a new project automatically inherits last saved setups', function () {
    $this->settingService->setLastCropSetup([
        'ratio' => '1:1',
        'preset' => 'square',
        'default_alignment' => 'top',
        'width' => 1080,
        'height' => 1080,
    ]);

    $this->settingService->setLastWatermarkSetup([
        'enabled' => true,
        'type' => 'text',
        'text' => '@PreservedWatermark',
        'position' => 'top-left',
        'opacity' => 75,
        'size' => 22,
        'margin' => 45,
        'color' => '#10b981',
        'has_shadow' => true,
        'has_pill' => false,
    ]);

    $this->settingService->setLastRecipeStepsSetup([
        'layout' => 'top-banner',
        'size' => 'large',
        'font_size' => 'large',
        'bg_color' => '#1e293b',
        'bg_opacity' => 0,
        'text_color' => '#ffffff',
        'badge_color' => '#f59e0b',
        'badge_text_color' => '#ffffff',
        'show_badge' => false,
        'has_shadow' => true,
        'title_padding' => 120,
        'text_align' => 'center',
    ]);

    $this->settingService->setLastCollageSetup([
        'layout' => 'vertical-story',
        'header_enabled' => true,
        'show_brand' => true,
        'footer_enabled' => true,
        'footer_text' => '@PreservedFooter',
        'bg_color' => '#000000',
        'gap' => 24,
        'padding' => 30,
        'format' => 'png',
        'quality' => 98,
        'scale' => 2,
    ]);

    // Create a mock video file for project creation
    $tempVideo = storage_path('dummy_video_'.uniqid().'.mp4');
    file_put_contents($tempVideo, 'dummy video data');

    $mockFfmpeg = Mockery::mock(FfmpegService::class);
    $mockFfmpeg->shouldReceive('generateThumbnail')->andReturn(true);

    $service = new ProjectService($mockFfmpeg, $this->settingService);
    $newProject = $service->create($tempVideo, 'Auto Inherited Project');

    if (file_exists($tempVideo)) {
        unlink($tempVideo);
    }

    expect($newProject['crop']['ratio'])->toBe('1:1')
        ->and($newProject['crop']['preset'])->toBe('square')
        ->and($newProject['crop']['default_alignment'])->toBe('top')
        ->and($newProject['watermark']['text'])->toBe('@PreservedWatermark')
        ->and($newProject['watermark']['position'])->toBe('top-left')
        ->and($newProject['watermark']['opacity'])->toBe(75)
        ->and($newProject['steps']['style']['layout'])->toBe('top-banner')
        ->and($newProject['steps']['style']['bg_opacity'])->toBe(0)
        ->and($newProject['steps']['style']['title_padding'])->toBe(120)
        ->and($newProject['collage']['layout'])->toBe('vertical-story')
        ->and($newProject['collage']['footer_text'])->toBe('@PreservedFooter');
});
