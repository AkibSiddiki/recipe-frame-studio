<?php

use App\Services\ProjectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('testing_watermark_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

function createWatermarkTestingProject(string $slug = 'wm-test-project', bool $withFrames = true): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'cropped');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'watermarked');

    $frames = [];
    if ($withFrames) {
        $framePath = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.'frame_0001.jpg';
        $img = imagecreatetruecolor(400, 300);
        $color = imagecolorallocate($img, 180, 80, 40);
        imagefill($img, 0, 0, $color);
        imagejpeg($img, $framePath, 90);
        imagedestroy($img);

        $frames = [
            [
                'id' => 'frame_0001',
                'filename' => 'frame_0001.jpg',
                'timestamp' => 1.5,
                'formatted_time' => '00:01',
                'selected' => true,
                'width' => 400,
                'height' => 300,
            ],
            [
                'id' => 'frame_0002',
                'filename' => 'frame_0002.jpg',
                'timestamp' => 3.0,
                'formatted_time' => '00:03',
                'selected' => false,
                'width' => 400,
                'height' => 300,
            ],
        ];
    }

    $projectData = [
        'name' => 'Watermark Test Project',
        'slug' => $slug,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'video' => [
            'filename' => 'test.mp4',
            'duration' => 30.0,
            'width' => 1920,
            'height' => 1080,
        ],
        'frames' => $frames,
        'crop' => [
            'ratio' => '4:5',
            'preset' => 'facebook-portrait',
            'default_alignment' => 'center',
        ],
        'watermark' => [
            'enabled' => true,
            'type' => 'text',
            'text' => '@MyRecipeChef',
            'position' => 'bottom-right',
            'opacity' => 85,
            'size' => 18,
            'margin' => 25,
            'color' => '#ffffff',
            'has_shadow' => true,
            'has_pill' => true,
        ],
    ];

    $projectService->save($slug, $projectData);

    return $projectData;
}

test('watermark page returns 404 for non-existent project', function () {
    $response = $this->get(route('project.watermark', 'non-existent-project'));

    $response->assertNotFound();
});

test('watermark page redirects to frames page if project has no frames', function () {
    createWatermarkTestingProject('empty-wm-project', withFrames: false);

    $response = $this->get(route('project.watermark', 'empty-wm-project'));

    $response->assertRedirect(route('project.frames', 'empty-wm-project'));
    $response->assertSessionHas('status');
});

test('watermark page loads successfully with frames and branding controls', function () {
    createWatermarkTestingProject('loaded-wm-project');

    $response = $this->get(route('project.watermark', 'loaded-wm-project'));

    $response->assertOk();
    $response->assertSee('Watermark');
    $response->assertSee('@MyRecipeChef');
    $response->assertSee('Save Watermark');
    $response->assertSee('Step 4: Watermark & Branding', false);
});

test('watermark settings can be updated via JSON API', function () {
    createWatermarkTestingProject('update-wm-project');

    $response = $this->postJson(route('project.watermark.save', 'update-wm-project'), [
        'type' => 'text',
        'enabled' => true,
        'text' => '@ChefGordon',
        'position' => 'top-left',
        'opacity' => 90,
        'size' => 20,
        'margin' => 40,
        'color' => '#fbbf24',
        'has_shadow' => false,
        'has_pill' => true,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'watermark' => [
            'type' => 'text',
            'enabled' => true,
            'text' => '@ChefGordon',
            'position' => 'top-left',
            'opacity' => 90,
            'size' => 20,
            'margin' => 40,
            'color' => '#fbbf24',
            'has_pill' => true,
        ],
    ]);

    $projectService = app(ProjectService::class);
    $project = $projectService->load('update-wm-project');
    expect($project['watermark']['text'])->toBe('@ChefGordon');
    expect($project['watermark']['position'])->toBe('top-left');
    expect($project['watermark']['opacity'])->toBe(90);
});

test('watermark settings can be updated via standard form post', function () {
    createWatermarkTestingProject('form-wm-project');

    $response = $this->post(route('project.watermark.save', 'form-wm-project'), [
        'type' => 'text',
        'text' => '@TasteOfHome',
        'position' => 'bottom-center',
        'opacity' => 75,
        'size' => 15,
        'margin' => 20,
    ]);

    $response->assertRedirect(route('project.watermark', 'form-wm-project'));
    $response->assertSessionHas('status');

    $projectService = app(ProjectService::class);
    $project = $projectService->load('form-wm-project');
    expect($project['watermark']['text'])->toBe('@TasteOfHome');
    expect($project['watermark']['position'])->toBe('bottom-center');
});

test('saving watermark settings validates input ranges', function () {
    createWatermarkTestingProject('invalid-wm-project');

    $response = $this->postJson(route('project.watermark.save', 'invalid-wm-project'), [
        'opacity' => 150, // exceeds max 100
        'size' => 2,     // below min 5
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['opacity', 'size']);
});

test('uploading a logo stores image and updates project watermark configuration', function () {
    createWatermarkTestingProject('logo-wm-project');

    // Create a real fake PNG image
    $file = UploadedFile::fake()->image('channel_logo.png', 200, 100);

    $response = $this->postJson(route('project.watermark.logo', 'logo-wm-project'), [
        'logo' => $file,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Logo uploaded successfully.',
    ]);
    expect($response->json('path'))->toContain('watermark/logo_');
    expect($response->json('url'))->toContain('watermark/logo');

    $projectService = app(ProjectService::class);
    $project = $projectService->load('logo-wm-project');
    expect($project['watermark']['type'])->toBe('image');
    expect($project['watermark']['image_path'])->toContain('watermark/logo_');

    // Verify file actually exists on disk
    $projectPath = $projectService->getProjectPath('logo-wm-project');
    $logoFile = $projectPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $project['watermark']['image_path']);
    expect(File::exists($logoFile))->toBeTrue();
});

test('watermark logo image endpoint returns uploaded image binary', function () {
    createWatermarkTestingProject('serve-logo-project');

    $file = UploadedFile::fake()->image('my_logo.png', 150, 80);
    $this->postJson(route('project.watermark.logo', 'serve-logo-project'), [
        'logo' => $file,
    ]);

    $response = $this->get(route('project.watermark.logo.image', 'serve-logo-project'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

test('watermarked frame image endpoint generates and serves watermarked image with GD', function () {
    createWatermarkTestingProject('render-wm-project');

    $response = $this->get(route('project.frame.watermarked', [
        'slug' => 'render-wm-project',
        'filename' => 'frame_0001.jpg',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');

    // Check that watermarked frame file was created on disk
    $projectService = app(ProjectService::class);
    $watermarkedPath = $projectService->getWatermarkedFramePath('render-wm-project', 'frame_0001.jpg');
    expect($watermarkedPath)->not()->toBeNull();
    expect(File::exists($watermarkedPath))->toBeTrue();

    // Verify it is a valid JPEG
    $size = getimagesize($watermarkedPath);
    expect($size)->not()->toBeFalse();
    expect($size[2])->toBe(IMAGETYPE_JPEG);
});

test('watermark respects disabled state and returns cropped frame without watermark', function () {
    createWatermarkTestingProject('disabled-wm-project');

    $projectService = app(ProjectService::class);
    $projectService->saveWatermarkSettings('disabled-wm-project', [
        'type' => 'none',
        'enabled' => false,
    ]);

    $path = $projectService->getWatermarkedFramePath('disabled-wm-project', 'frame_0001.jpg');
    expect($path)->toContain('cropped');
});

test('watermark margin can be any custom value including zero or large numbers', function () {
    createWatermarkTestingProject('margin-wm-project');

    $response = $this->postJson(route('project.watermark.save', 'margin-wm-project'), [
        'type' => 'image',
        'margin' => 0,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'watermark' => [
            'margin' => 0,
        ],
    ]);

    $responseLarge = $this->postJson(route('project.watermark.save', 'margin-wm-project'), [
        'type' => 'image',
        'margin' => 250,
    ]);

    $responseLarge->assertOk();
    $responseLarge->assertJson([
        'success' => true,
        'watermark' => [
            'margin' => 250,
        ],
    ]);
});

test('default watermark logo is served when image_path uses default asset', function () {
    createWatermarkTestingProject('default-logo-project');

    $response = $this->get(route('project.watermark.logo.image', 'default-logo-project'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});
