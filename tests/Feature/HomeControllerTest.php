<?php

use App\Services\ProjectService;

test('home page renders with empty state when no projects exist', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('getRecent')->andReturn([]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Recipe Frame Studio')
        ->assertSee('No recent projects found');
});

test('home page renders recent project cards when projects exist', function () {
    $mockProjectService = Mockery::mock(ProjectService::class);
    $mockProjectService->shouldReceive('getRecent')->andReturn([
        [
            'name' => 'Garlic Bhorta',
            'slug' => 'garlic-bhorta',
            'updated_at' => now()->toISOString(),
            'video' => [
                'formatted_duration' => '02:14',
                'aspect_ratio' => '9:16',
            ],
        ],
    ]);
    $this->app->instance(ProjectService::class, $mockProjectService);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Garlic Bhorta')
        ->assertSee('02:14')
        ->assertSee('9:16');
});
