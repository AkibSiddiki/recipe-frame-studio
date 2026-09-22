<?php

use App\Services\AppSettingService;

test('it can store and retrieve string settings', function () {
    $service = app(AppSettingService::class);

    $service->set('test_key', 'test_value');

    expect($service->get('test_key'))->toBe('test_value')
        ->and($service->has('test_key'))->toBeTrue();
});

test('it returns default value when setting does not exist', function () {
    $service = app(AppSettingService::class);

    expect($service->get('non_existent_key', 'fallback'))->toBe('fallback');
});

test('it can store and retrieve array settings', function () {
    $service = app(AppSettingService::class);

    $data = ['quality' => 90, 'ratio' => '4:5', 'active' => true];
    $service->set('presets', $data);

    expect($service->get('presets'))->toBe($data);
});

test('it can remove settings', function () {
    $service = app(AppSettingService::class);

    $service->set('to_delete', 'value');
    expect($service->has('to_delete'))->toBeTrue();

    $service->remove('to_delete');
    expect($service->has('to_delete'))->toBeFalse()
        ->and($service->get('to_delete'))->toBeNull();
});

test('it can retrieve all settings', function () {
    $service = app(AppSettingService::class);

    $service->set('key_1', 'val_1');
    $service->set('key_2', ['a' => 1]);

    $all = $service->all();

    expect($all)->toBeArray()
        ->and($all)->toHaveKey('key_1', 'val_1')
        ->and($all)->toHaveKey('key_2', ['a' => 1]);
});
