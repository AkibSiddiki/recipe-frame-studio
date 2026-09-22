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
