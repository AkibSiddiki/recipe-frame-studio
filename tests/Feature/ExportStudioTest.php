<?php

use App\Services\ProjectService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rfs_test_'.uniqid();
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    gc_collect_cycles();
    if (File::exists($this->tempDir)) {
        @File::deleteDirectory($this->tempDir);
    }
});

function createExportTestingProject(string $slug = 'export-test-project', bool $withFrames = true): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'cropped');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'watermarked');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'steps');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'output');

    $frames = [];
    if ($withFrames) {
        $framePath1 = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.'frame_0001.jpg';
        $img1 = imagecreatetruecolor(400, 300);
        $color1 = imagecolorallocate($img1, 180, 80, 40);
        imagefill($img1, 0, 0, $color1);
        imagejpeg($img1, $framePath1, 90);
        imagedestroy($img1);

        $framePath2 = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.'frame_0002.jpg';
        $img2 = imagecreatetruecolor(400, 300);
        $color2 = imagecolorallocate($img2, 40, 140, 180);
        imagefill($img2, 0, 0, $color2);
        imagejpeg($img2, $framePath2, 90);
        imagedestroy($img2);

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
                'timestamp' => 4.0,
                'formatted_time' => '00:04',
                'selected' => true,
                'width' => 400,
                'height' => 300,
            ],
        ];
    }

    $projectData = [
        'name' => 'Garlic Butter Chicken',
        'slug' => $slug,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'video' => [
            'filename' => 'chicken.mp4',
            'duration' => 45.0,
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
            'text' => '@ChefKitchen',
            'position' => 'bottom-right',
            'opacity' => 85,
            'size' => 18,
            'margin' => 25,
            'color' => '#ffffff',
            'has_shadow' => true,
            'has_pill' => true,
        ],
        'steps' => [
            'style' => [
                'layout' => 'bottom-banner',
                'bg_opacity' => 85,
                'text_color' => '#ffffff',
                'badge_color' => '#f59e0b',
                'show_badge' => true,
            ],
            'items' => [
                [
                    'frame_id' => 'frame_0001.jpg',
                    'step_number' => 1,
                    'title' => 'Sear the Chicken',
                    'description' => 'Sear chicken breasts until golden brown.',
                    'ingredients' => '500g chicken breasts, 2 tbsp butter',
                    'enabled' => true,
                ],
                [
                    'frame_id' => 'frame_0002.jpg',
                    'step_number' => 2,
                    'title' => 'Make Garlic Sauce',
                    'description' => 'Melt garlic and butter together until fragrant.',
                    'ingredients' => '4 cloves garlic, parsley',
                    'enabled' => true,
                ],
            ],
        ],
        'collage' => [
            'layout' => 'auto-grid',
            'header_enabled' => true,
            'title' => 'Garlic Butter Chicken',
            'subtitle' => 'Crispy outside, tender inside',
            'prep_time' => '10m',
            'cook_time' => '20m',
            'servings' => '4 Servings',
            'bg_color' => '#0f172a',
            'gap' => 16,
            'padding' => 24,
            'format' => 'jpg',
            'quality' => 92,
            'scale' => 1,
        ],
    ];

    $projectService->save($slug, $projectData);

    return $projectData;
}

test('export page returns 404 for non-existent project', function () {
    $response = $this->get(route('project.export', 'non-existent-project'));

    $response->assertNotFound();
});

test('export page renders successfully with selected frames and download buttons', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export', $project['slug']));

    $response->assertOk();
    $response->assertSee('Step 6: Export & Collage Studio', false);
    $response->assertSee('Download Collage');
    $response->assertSee('Download ZIP Bundle');
    $response->assertSee('Auto Grid');
});

test('export page renders empty state when no frames are selected', function () {
    $project = createExportTestingProject('empty-export-project', false);

    $response = $this->get(route('project.export', $project['slug']));

    $response->assertOk();
    $response->assertSee('No Step Cards Available');
});

test('saveCollage endpoint persists custom collage options to project.json', function () {
    $project = createExportTestingProject();

    $payload = [
        'layout' => 'grid-2x2',
        'header_enabled' => true,
        'title' => 'Pan-Seared Salmon',
        'subtitle' => 'Lemon herb crust',
        'prep_time' => '5m',
        'cook_time' => '12m',
        'servings' => '2 Servings',
        'bg_color' => '#000000',
        'gap' => 24,
        'padding' => 32,
        'format' => 'png',
        'quality' => 95,
        'scale' => 2,
    ];

    $response = $this->postJson(route('project.export.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Collage settings saved successfully.',
    ]);

    $projectService = app(ProjectService::class);
    $loadedProject = $projectService->load($project['slug']);

    expect($loadedProject['collage']['layout'])->toBe('grid-2x2');
    expect($loadedProject['collage']['title'])->toBe('Pan-Seared Salmon');
    expect($loadedProject['collage']['bg_color'])->toBe('#000000');
    expect($loadedProject['collage']['gap'])->toBe(24);
    expect($loadedProject['collage']['format'])->toBe('png');
});

test('saveCollage endpoint validates input parameters', function () {
    $project = createExportTestingProject();

    $payload = [
        'layout' => 'invalid-layout',
        'gap' => 999, // exceeds max 64
        'format' => 'gif', // invalid format
    ];

    $response = $this->postJson(route('project.export.save', $project['slug']), $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['layout', 'gap', 'format']);
});

test('getCollageImagePath renders GD composite image in auto-grid layout', function () {
    $project = createExportTestingProject();
    $projectService = app(ProjectService::class);

    $collagePath = $projectService->getCollageImagePath($project['slug']);

    expect($collagePath)->not->toBeNull();
    expect(file_exists($collagePath))->toBeTrue();

    $imageInfo = getimagesize($collagePath);
    expect($imageInfo[2])->toBe(IMAGETYPE_JPEG);
    expect($imageInfo[0])->toBeGreaterThan(0);
    expect($imageInfo[1])->toBeGreaterThan(0);
});

test('getCollageImagePath renders GD composite in vertical-story layout', function () {
    $project = createExportTestingProject();
    $projectService = app(ProjectService::class);

    $projectService->saveCollageSettings($project['slug'], [
        'layout' => 'vertical-story',
        'header_enabled' => false,
    ]);

    $collagePath = $projectService->getCollageImagePath($project['slug'], true);

    expect($collagePath)->not->toBeNull();
    expect(file_exists($collagePath))->toBeTrue();
});

test('collageImage endpoint serves binary file response', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export.collage-image', $project['slug']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
});

test('downloadCollage endpoint streams file download attachment', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export.download.collage', $project['slug']));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('garlic_butter_chicken_recipe_collage.jpg');
});

test('downloadZip endpoint creates and streams valid ZIP file', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export.download.zip', $project['slug']));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('.zip');
});

test('downloadStepCard endpoint streams single step card download', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export.download.step', [
        'slug' => $project['slug'],
        'filename' => 'frame_0001.jpg',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
    expect($response->headers->get('Content-Disposition'))->toContain('frame_0001.jpg');
});

test('prepareZip endpoint generates zip bundle and returns JSON metadata', function () {
    $project = createExportTestingProject();

    $response = $this->postJson(route('project.export.prepare.zip', $project['slug']), [
        'collage_settings' => [
            'layout' => 'auto-grid',
            'gap' => 16,
            'padding' => 24,
            'format' => 'jpg',
        ],
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
    expect($response->json('download_url'))->toContain('download/zip');
    expect($response->json('filename'))->toContain('.zip');
    expect($response->json('file_size'))->not->toBeEmpty();
});

test('prepareCollage endpoint renders collage and returns JSON metadata', function () {
    $project = createExportTestingProject();

    $response = $this->postJson(route('project.export.prepare.collage', $project['slug']), [
        'collage_settings' => [
            'layout' => 'grid-2x2',
            'gap' => 12,
            'padding' => 20,
            'format' => 'jpg',
        ],
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
    expect($response->json('download_url'))->toContain('download/collage');
    expect($response->json('filename'))->toContain('.jpg');
    expect($response->json('file_size'))->not->toBeEmpty();
});

test('exportStatus endpoint returns progress state', function () {
    $project = createExportTestingProject();

    $response = $this->getJson(route('project.export.status', $project['slug']));

    $response->assertOk();
    expect($response->json())->toHaveKey('percent');
});

test('export page renders step-by-step export modal and pipeline elements', function () {
    $project = createExportTestingProject();

    $response = $this->get(route('project.export', $project['slug']));

    $response->assertOk();
    $response->assertSee('id="export-progress-modal"', false);
    $response->assertSee('Export Execution Pipeline');
    $response->assertSee('What\'s Happening in Background', false);
    $response->assertSee('id="export-download-direct-btn"', false);
    $response->assertSee('id="export-open-folder-btn"', false);
    $response->assertSee('id="export-download-status-text"', false);
});

test('downloadZip reuses pre-generated zip bundle without duplicate rebuild', function () {
    $project = createExportTestingProject();
    $projectService = app(ProjectService::class);

    // Prepare zip first
    $preparedZip = $projectService->createProjectZipArchive($project['slug'], true);
    expect($preparedZip)->not->toBeNull();
    expect(file_exists($preparedZip))->toBeTrue();

    $initialMtime = filemtime($preparedZip);

    // Call downloadZip endpoint
    $response = $this->get(route('project.export.download.zip', $project['slug']));
    $response->assertOk();

    // Verify file was reused and not overwritten
    expect(filemtime($preparedZip))->toBe($initialMtime);
});

test('downloadCollage reuses pre-rendered collage without duplicate rebuild', function () {
    $project = createExportTestingProject();
    $projectService = app(ProjectService::class);

    // Prepare collage first
    $preparedCollage = $projectService->getCollageImagePath($project['slug'], true);
    expect($preparedCollage)->not->toBeNull();
    expect(file_exists($preparedCollage))->toBeTrue();

    $initialMtime = filemtime($preparedCollage);

    // Call downloadCollage endpoint
    $response = $this->get(route('project.export.download.collage', $project['slug']));
    $response->assertOk();

    // Verify file was reused
    expect(filemtime($preparedCollage))->toBe($initialMtime);
});

test('nativeSave endpoint returns fallback download url when native dialog is unavailable', function () {
    $project = createExportTestingProject();

    $response = $this->postJson(route('project.export.native-save', $project['slug']), [
        'type' => 'zip',
        'filename' => 'test_bundle.zip',
    ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('success');
    expect($response->json('download_url'))->toContain('download/zip');
});

test('openExportFolder endpoint returns json response', function () {
    $project = createExportTestingProject();

    $response = $this->postJson(route('project.export.open-folder', $project['slug']), [
        'path' => null,
    ]);

    $response->assertOk();
});
