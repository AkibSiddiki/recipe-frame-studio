<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Projects Directory
    |--------------------------------------------------------------------------
    |
    | The base directory where all Recipe Frame Studio projects are stored.
    | Each project gets its own subdirectory with source, frames, and output.
    |
    */

    'projects_directory' => env(
        'RECIPE_STUDIO_PROJECTS_DIR',
        implode(DIRECTORY_SEPARATOR, [getenv('USERPROFILE') ?: getenv('HOME') ?: storage_path(), 'Documents', 'Recipe Frame Studio', 'Projects']),
    ),

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    */

    'ffmpeg_path' => env('FFMPEG_PATH', ''),

    'ffprobe_path' => env('FFPROBE_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Output Defaults
    |--------------------------------------------------------------------------
    */

    'output' => [
        'format' => 'jpg',
        'quality' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Crop Defaults
    |--------------------------------------------------------------------------
    */

    'crop' => [
        'ratio' => '4:5',
        'width' => 1080,
        'height' => 1350,
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Defaults
    |--------------------------------------------------------------------------
    */

    'watermark' => [
        'enabled' => true,
        'type' => 'image',
        'image_path' => 'images/default-watermark.png',
        'position' => 'bottom-right',
        'opacity' => 85,
        'margin' => 30,
        'size' => 18, // percentage of image width
        'text' => '@রান্নাঘরেরডায়েরি',
        'color' => '#ffffff',
        'has_shadow' => false,
        'has_pill' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Presets
    |--------------------------------------------------------------------------
    */

    'presets' => [
        'facebook-portrait' => [
            'label' => 'Facebook Portrait',
            'width' => 1080,
            'height' => 1350,
            'ratio' => '4:5',
        ],
        'square' => [
            'label' => 'Square',
            'width' => 1080,
            'height' => 1080,
            'ratio' => '1:1',
        ],
        'landscape' => [
            'label' => 'Landscape',
            'width' => 1920,
            'height' => 1080,
            'ratio' => '16:9',
        ],
        'original-vertical' => [
            'label' => 'Original Vertical',
            'width' => 1080,
            'height' => 1920,
            'ratio' => '9:16',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Video Formats
    |--------------------------------------------------------------------------
    */

    'supported_video_extensions' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],

];
