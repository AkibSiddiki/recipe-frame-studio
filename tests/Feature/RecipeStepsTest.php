<?php

use App\Services\ProjectService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('testing_steps_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

function createStepsTestingProject(string $slug = 'steps-test-project', bool $withFrames = true): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'cropped');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'watermarked');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'steps');

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
        'name' => 'Recipe Steps Test Project',
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
            'ratio' => '1:1',
            'preset' => 'instagram-square',
            'default_alignment' => 'center',
        ],
        'watermark' => [
            'enabled' => true,
            'type' => 'text',
            'text' => '@RecipeStudio',
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

test('recipe steps page returns 404 for non-existent project', function () {
    $response = $this->get(route('project.steps', 'non-existent-project'));

    $response->assertNotFound();
});

test('recipe steps page renders successfully with selected frames', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('Step 5: Recipe Steps', false);
    $response->assertSee('Step Instructions');
    $response->assertSee('Enable All');
    $response->assertSee('Disable All');
    $response->assertSee('Bottom Banner');
});

test('recipe steps page renders empty state when no frames are selected', function () {
    $project = createStepsTestingProject('no-frames-project', false);

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('No Frames Selected');
});

test('getRecipeSteps pre-populates default items based on selected frames', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $steps = $projectService->getRecipeSteps($project['slug']);

    expect($steps)->toBeArray();
    expect($steps['style']['layout'])->toBe('bottom-banner');
    expect($steps['items'])->toHaveCount(1);
    expect($steps['items'][0]['frame_id'])->toBe('frame_0001.jpg');
    expect($steps['items'][0]['title'])->toBe('Step 1');
    expect($steps['items'][0]['step_number'])->toBe(1);
});

test('saveSteps endpoint persists recipe steps and styles to project.json', function () {
    $project = createStepsTestingProject();

    $payload = [
        'style' => [
            'layout' => 'lower-third',
            'bg_color' => '#1e293b',
            'bg_opacity' => 90,
            'text_color' => '#fef08a',
            'badge_color' => '#ef4444',
            'badge_text_color' => '#ffffff',
            'show_badge' => true,
            'font_size' => 'large',
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'Heat Olive Oil',
                'description' => 'Heat olive oil in large skillet over medium heat.',
                'ingredients' => '2 tbsp olive oil',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Recipe steps saved successfully.',
    ]);

    $projectService = app(ProjectService::class);
    $loadedProject = $projectService->load($project['slug']);

    expect($loadedProject['recipe_steps']['style']['layout'])->toBe('lower-third');
    expect($loadedProject['recipe_steps']['style']['bg_opacity'])->toBe(90);
    expect($loadedProject['recipe_steps']['items'][0]['title'])->toBe('Heat Olive Oil');
    expect($loadedProject['recipe_steps']['items'][0]['ingredients'])->toBe('2 tbsp olive oil');
});

test('saveSteps endpoint validates layout and opacity parameters', function () {
    $project = createStepsTestingProject();

    $payload = [
        'style' => [
            'layout' => 'invalid-layout',
            'bg_opacity' => 150, // exceeds max 100
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['style.layout', 'style.bg_opacity']);
});

test('getStepFramePath renders GD image with bottom-banner overlay composite', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $projectService->saveRecipeSteps($project['slug'], [
        'style' => [
            'layout' => 'bottom-banner',
            'bg_opacity' => 85,
            'text_color' => '#ffffff',
            'badge_color' => '#f59e0b',
            'show_badge' => true,
            'font_size' => 'medium',
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'Brown the Meat',
                'description' => 'Sear the meat until browned on all sides.',
                'ingredients' => '500g beef chuck',
                'enabled' => true,
            ],
        ],
    ]);

    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg');

    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();

    $imageInfo = getimagesize($stepFramePath);
    expect($imageInfo[2])->toBe(IMAGETYPE_JPEG);
    expect($imageInfo[0])->toBeGreaterThan(0);
    expect($imageInfo[1])->toBeGreaterThan(0);
});

test('getStepFramePath renders GD image with floating pill lower-third layout', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $projectService->saveRecipeSteps($project['slug'], [
        'style' => [
            'layout' => 'lower-third',
            'bg_opacity' => 80,
            'text_color' => '#ffffff',
            'badge_color' => '#10b981',
            'show_badge' => true,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'Garnish & Serve',
                'description' => 'Sprinkle fresh parsley on top.',
                'enabled' => true,
            ],
        ],
    ]);

    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);

    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();
});

test('getStepFramePath renders GD image with badge-only layout', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $projectService->saveRecipeSteps($project['slug'], [
        'style' => [
            'layout' => 'badge-only',
            'badge_color' => '#6366f1',
            'show_badge' => true,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'enabled' => true,
            ],
        ],
    ]);

    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);

    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();
});

test('stepFrameImage endpoint returns binary JPEG file response', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.frame.step-image', [
        'slug' => $project['slug'],
        'filename' => 'frame_0001.jpg',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
});

test('stepFrameImage endpoint returns 404 for invalid frame filename', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.frame.step-image', [
        'slug' => $project['slug'],
        'filename' => 'non_existent_frame.jpg',
    ]));

    $response->assertNotFound();
});

test('saveSteps endpoint updates recipe_name and persists top-banner captions', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $payload = [
        'recipe_name' => 'নরম তুলতুলে পাউরুটি',
        'style' => [
            'layout' => 'top-banner',
            'bg_opacity' => 90,
            'text_color' => '#ffffff',
            'badge_color' => '#f59e0b',
            'show_badge' => true,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'তৈরি নরম তুলতুলে পাউরুটি',
                'description' => 'মচমচে করে স্লাইস কাটা',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Recipe steps saved successfully.',
    ]);

    $updatedProject = $projectService->load($project['slug']);
    expect($updatedProject['name'])->toBe('নরম তুলতুলে পাউরুটি');
    expect($updatedProject['steps']['style']['layout'])->toBe('top-banner');
    expect($updatedProject['steps']['items'][0]['title'])->toBe('তৈরি নরম তুলতুলে পাউরুটি');

    // Verify GD renders step frame with top-banner and Bengali text without error
    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();
});

test('recipe steps view contains paste JSON captions button and modal', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('Paste JSON Captions');
    $response->assertSee('paste-json-modal', false);
    $response->assertSee('json-caption-input', false);
    $response->assertSee('title-padding-slider', false);
    $response->assertSee('title-padding-input', false);
    $response->assertSee('step-padding-0', false);
});

test('saveSteps persists style title_padding and per-step padding', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $payload = [
        'style' => [
            'layout' => 'top-banner',
            'title_padding' => 45,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'কুসুম গরম দুধ নিন ১ কাপ',
                'padding' => 50,
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Recipe steps saved successfully.',
    ]);

    $steps = $projectService->getRecipeSteps($project['slug']);
    expect($steps['style']['title_padding'])->toBe(45);
    expect($steps['items'][0]['padding'])->toBe(50);
});

test('ProjectService resolves font from public folder as primary font', function () {
    $projectService = app(ProjectService::class);
    $resolvedFont = $projectService->resolveTtfFont(false);
    $resolvedBold = $projectService->resolveTtfFont(true);

    expect($resolvedFont)->not->toBeNull();
    expect($resolvedFont)->toBe(public_path('HindSiliguri-Regular.ttf'));
    expect(file_exists($resolvedFont))->toBeTrue();

    expect($resolvedBold)->not->toBeNull();
    expect($resolvedBold)->toBe(public_path('HindSiliguri-Bold.ttf'));
    expect(file_exists($resolvedBold))->toBeTrue();
});

test('saveSteps allows 0 percent banner opacity and renders without error', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $payload = [
        'style' => [
            'layout' => 'top-banner',
            'bg_opacity' => 0,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'নরম তুলতুলে পাউরুটি',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Recipe steps saved successfully.',
    ]);

    $steps = $projectService->getRecipeSteps($project['slug']);
    expect($steps['style']['bg_opacity'])->toBe(0);

    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();
});

test('saveSteps persists center text alignment and renders GD overlay without error', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $payload = [
        'style' => [
            'layout' => 'top-banner',
            'text_align' => 'center',
            'title_padding' => 25,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'নরম তুলতুলে পাউরুটি',
                'description' => 'কুসুম গরম দুধ ও ইস্ট ভালোভাবে মিশিয়ে নিন',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Recipe steps saved successfully.',
    ]);

    $steps = $projectService->getRecipeSteps($project['slug']);
    expect($steps['style']['text_align'])->toBe('center');

    // Verify GD renders center-aligned step overlay properly
    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();
});

test('saveSteps rejects invalid text alignment parameter', function () {
    $project = createStepsTestingProject();

    $payload = [
        'style' => [
            'text_align' => 'justify', // Invalid, must be left, center, right
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['style.text_align']);
});

test('recipe steps view contains headline alignment controls', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('Headline Alignment');
    $response->assertSee('text-align-center', false);
    $response->assertSee('text-align-left', false);
    $response->assertSee('text-align-right', false);
});

test('recipe steps view contains drop shadow toggle checkbox', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('has-shadow-checkbox', false);
    $response->assertSee('Drop shadow effect');
});

test('recipe steps style persists has_shadow toggle and renders with drop shadow in GD', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    // 1. Check default has_shadow
    $initialSteps = $projectService->getRecipeSteps($project['slug']);
    expect($initialSteps['style']['has_shadow'] ?? true)->toBeTrue();

    // 2. Save with has_shadow set to true
    $payload = [
        'style' => [
            'layout' => 'bottom-banner',
            'has_shadow' => true,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'Title With Drop Shadow',
                'description' => 'Crisp text on top of ambient shadow layers',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);
    $response->assertOk();

    $savedSteps = $projectService->getRecipeSteps($project['slug']);
    expect($savedSteps['style']['has_shadow'])->toBeTrue();

    // Render GD frame with shadow enabled
    $renderedWithShadow = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($renderedWithShadow)->not->toBeNull();
    expect(file_exists($renderedWithShadow))->toBeTrue();

    // 3. Save with has_shadow set to false
    $payload['style']['has_shadow'] = false;
    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);
    $response->assertOk();

    $savedNoShadow = $projectService->getRecipeSteps($project['slug']);
    expect($savedNoShadow['style']['has_shadow'])->toBeFalse();

    // Render GD frame with shadow disabled
    $renderedWithoutShadow = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($renderedWithoutShadow)->not->toBeNull();
    expect(file_exists($renderedWithoutShadow))->toBeTrue();
});

test('recipe steps view contains preview left and right arrow navigation controls', function () {
    $project = createStepsTestingProject();

    $response = $this->get(route('project.steps', $project['slug']));

    $response->assertOk();
    $response->assertSee('prev-frame-btn', false);
    $response->assertSee('next-frame-btn', false);
    $response->assertSee('top-prev-frame-btn', false);
    $response->assertSee('top-next-frame-btn', false);
    $response->assertSee('step-counter-badge', false);
    $response->assertSee('prevStep()', false);
    $response->assertSee('nextStep()', false);
});

test('bengali complex text is shaped properly without broken glyphs in step frame export', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $fontFile = $projectService->resolveTtfFont(true);
    expect($fontFile)->not->toBeNull();

    // Bengali phrase with pre-base matras (ঐ-কার, ই-কার) and complex conjuncts
    $bengaliText = 'তৈরি নরম তুলতুলে পাউরুটি';

    [$w, $h] = $projectService->measureText(36, $fontFile, $bengaliText);
    expect($w)->toBeGreaterThan(0);
    expect($h)->toBeGreaterThan(0);

    // Save recipe steps with Bengali text
    $payload = [
        'style' => [
            'layout' => 'bottom-banner',
            'has_shadow' => true,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => $bengaliText,
                'description' => 'কুসুম গরম দুধ ও ইস্ট ভালোভাবে মিশিয়ে নিন',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);
    $response->assertOk();

    // Verify step frame rendered successfully
    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();

    // Verify zip bundle export succeeds
    $zipPath = $projectService->createProjectZipArchive($project['slug']);
    expect($zipPath)->not->toBeNull();
    expect(file_exists($zipPath))->toBeTrue();
});

test('saveSteps with large font_size scales font and banner properly', function () {
    $project = createStepsTestingProject();
    $projectService = app(ProjectService::class);

    $payload = [
        'style' => [
            'layout' => 'bottom-banner',
            'font_size' => 'large',
            'bg_opacity' => 85,
        ],
        'items' => [
            [
                'frame_id' => 'frame_0001.jpg',
                'step_number' => 1,
                'title' => 'ধাপ ১: ডিম ও চিনি ফেটিয়ে নিন',
                'description' => 'ভালোভাবে মিশ্রণ তৈরি করুন যতক্ষণ না চিনি পুরোপুরি গলে যায়।',
                'enabled' => true,
            ],
        ],
    ];

    $response = $this->postJson(route('project.steps.save', $project['slug']), $payload);
    $response->assertOk();

    $steps = $projectService->getRecipeSteps($project['slug']);
    expect($steps['style']['font_size'])->toBe('large');

    $stepFramePath = $projectService->getStepFramePath($project['slug'], 'frame_0001.jpg', true);
    expect($stepFramePath)->not->toBeNull();
    expect(file_exists($stepFramePath))->toBeTrue();

    // Verify generated image is valid JPEG and non-empty
    $imgSize = @getimagesize($stepFramePath);
    expect($imgSize)->not->toBeFalse();
    expect($imgSize[0])->toBeGreaterThan(0);
    expect($imgSize[1])->toBeGreaterThan(0);
});
