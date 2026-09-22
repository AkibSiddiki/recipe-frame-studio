<?php

use App\Services\ProjectService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('testing_crop_'.uniqid());
    config(['recipe-studio.projects_directory' => $this->tempDir]);
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

function createCropTestingProject(string $slug = 'crop-test-project'): array
{
    $projectService = app(ProjectService::class);
    $projectPath = $projectService->getProjectPath($slug);

    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'source');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'frames');
    File::ensureDirectoryExists($projectPath.DIRECTORY_SEPARATOR.'cropped');

    // Create a real JPEG image with GD for frame_0001.jpg
    $framePath = $projectPath.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.'frame_0001.jpg';
    $img = imagecreatetruecolor(400, 300);
    $color = imagecolorallocate($img, 200, 100, 50);
    imagefill($img, 0, 0, $color);
    imagejpeg($img, $framePath, 90);
    imagedestroy($img);

    $projectData = [
        'name' => 'Crop Test Project',
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
            [
                'id' => 'frame_0002',
                'filename' => 'frame_0002.jpg',
                'timestamp' => 3.0,
                'formatted_time' => '00:03',
                'selected' => false,
                'width' => 400,
                'height' => 300,
            ],
        ],
        'crop' => [
            'ratio' => '4:5',
            'preset' => 'facebook-portrait',
            'width' => 1080,
            'height' => 1350,
            'default_alignment' => 'center',
        ],
    ];

    $projectService->save($slug, $projectData);

    return $projectData;
}

test('it renders the crop studio page when frames exist', function () {
    createCropTestingProject('pizza-recipe');

    $response = $this->get(route('project.crop', 'pizza-recipe'));

    $response->assertStatus(200);
    $response->assertSee('Step 3: Crop', false);
    $response->assertSee('4:5 Portrait', false);
    $response->assertSee('1:1 Square', false);
    $response->assertSee('16:9 Landscape', false);
    $response->assertSee('Apply to All Frames', false);
    $response->assertSee('frame_0001.jpg', false);
});

test('it redirects to frames if no frames exist for project', function () {
    $projectService = app(ProjectService::class);
    $slug = 'empty-project';
    $projectService->save($slug, [
        'name' => 'Empty Project',
        'slug' => $slug,
        'frames' => [],
        'video' => [
            'filename' => 'empty.mp4',
            'duration' => 10.0,
        ],
    ]);

    $response = $this->get(route('project.crop', $slug));

    $response->assertRedirect(route('project.frames', $slug));
    $response->assertSessionHas('status');
});

test('it returns 404 for non-existent project on crop page', function () {
    $response = $this->get(route('project.crop', 'non-existent-project'));

    $response->assertStatus(404);
});

test('it saves crop settings via post request', function () {
    createCropTestingProject('soup-recipe');

    $payload = [
        'ratio' => '1:1',
        'preset' => 'square',
        'width' => 1080,
        'height' => 1080,
        'default_alignment' => 'top',
        'frames' => [
            'frame_0001' => [
                'x' => 10,
                'y' => 20,
                'width' => 250,
                'height' => 250,
                'ratio' => '1:1',
            ],
        ],
    ];

    $response = $this->postJson(route('project.crop.save', 'soup-recipe'), $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $projectService = app(ProjectService::class);
    $updated = $projectService->load('soup-recipe');

    expect($updated['crop']['ratio'])->toBe('1:1');
    expect($updated['crop']['preset'])->toBe('square');
    expect($updated['frames'][0]['crop']['width'])->toBe(250);
});

test('it applies crop across all frames when apply_to_all is enabled', function () {
    createCropTestingProject('salad-recipe');

    $payload = [
        'ratio' => '4:5',
        'preset' => 'facebook-portrait',
        'apply_to_all' => true,
        'global_crop' => [
            'x' => 15,
            'y' => 10,
            'width' => 200,
            'height' => 250,
        ],
    ];

    $response = $this->postJson(route('project.crop.save', 'salad-recipe'), $payload);

    $response->assertStatus(200);

    $projectService = app(ProjectService::class);
    $updated = $projectService->load('salad-recipe');

    // Both frames should now have the global crop
    expect($updated['frames'][0]['crop']['width'])->toBe(200);
    expect($updated['frames'][1]['crop']['width'])->toBe(200);
});

test('it crops and serves the frame image safely', function () {
    createCropTestingProject('cake-recipe');

    $response = $this->get(route('project.frame.cropped', [
        'slug' => 'cake-recipe',
        'filename' => 'frame_0001.jpg',
    ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/jpeg');
});

test('it returns 404 for invalid cropped frame requests', function () {
    createCropTestingProject('steak-recipe');

    $response = $this->get(route('project.frame.cropped', [
        'slug' => 'steak-recipe',
        'filename' => 'non_existent_frame.jpg',
    ]));

    $response->assertStatus(404);
});
