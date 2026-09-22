<?php

$sourcePath = 'C:\\Users\\asapu\\.gemini\\antigravity-ide\\brain\\c7d8c493-33d1-41e8-81b4-a80d8e00117e\\.user_uploaded\\media_1790107614677.png';

if (! file_exists($sourcePath)) {
    echo "Error: Source image not found at $sourcePath\n";
    exit(1);
}

$source = imagecreatefrompng($sourcePath);
if (! $source) {
    echo "Error: Failed to load source image.\n";
    exit(1);
}

imagealphablending($source, true);
imagesavealpha($source, true);

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

echo "Source dimensions: {$sourceWidth}x{$sourceHeight}\n";

$publicDir = __DIR__.'/../public';
$imagesDir = $publicDir.'/images';

if (! is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 1. Generate 512x512 icon.png
function resizeImage($source, $targetSize)
{
    $target = imagecreatetruecolor($targetSize, $targetSize);
    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefilledrectangle($target, 0, 0, $targetSize, $targetSize, $transparent);
    imagealphablending($target, true);

    imagecopyresampled(
        $target,
        $source,
        0, 0, 0, 0,
        $targetSize, $targetSize,
        imagesx($source), imagesy($source)
    );

    return $target;
}

// 512x512 high-res icon
$icon512 = resizeImage($source, 512);
imagepng($icon512, $publicDir.'/icon.png', 9);
echo "Generated: public/icon.png\n";

// Logo for UI display
$logo256 = resizeImage($source, 256);
imagepng($logo256, $imagesDir.'/logo.png', 9);
imagepng($logo256, $publicDir.'/logo.png', 9);
echo "Generated: public/images/logo.png\n";

// Default watermark (high-res for 1080p/4k video frames)
$watermark = resizeImage($source, 512);
imagepng($watermark, $imagesDir.'/default-watermark.png', 9);
echo "Generated: public/images/default-watermark.png\n";

// 2. Generate multi-resolution ICO file (PNG-compressed entries)
$sizes = [256, 128, 64, 48, 32, 16];
$icoImages = [];

foreach ($sizes as $size) {
    $resized = resizeImage($source, $size);
    ob_start();
    imagepng($resized, null, 9);
    $pngData = ob_get_clean();
    $icoImages[] = [
        'width' => $size >= 256 ? 0 : $size,
        'height' => $size >= 256 ? 0 : $size,
        'data' => $pngData,
        'size' => strlen($pngData),
    ];
}

// Build ICO header + directory entries + payload
$count = count($icoImages);
$icoData = pack('vvv', 0, 1, $count); // Reserved (0), Type (1=icon), Count

$offset = 6 + ($count * 16);

foreach ($icoImages as $img) {
    $icoData .= pack('CCCCvvVV',
        $img['width'],
        $img['height'],
        0, // colors
        0, // reserved
        1, // color planes
        32, // bpp
        $img['size'],
        $offset
    );
    $offset += $img['size'];
}

foreach ($icoImages as $img) {
    $icoData .= $img['data'];
}

file_put_contents($publicDir.'/icon.ico', $icoData);
file_put_contents($publicDir.'/favicon.ico', $icoData);
echo "Generated: public/icon.ico and public/favicon.ico\n";

// For macOS packaging fallback
file_put_contents($publicDir.'/icon.icns', $icoData);
copy($publicDir.'/icon.png', $publicDir.'/IconTemplate.png');
copy($publicDir.'/icon.png', $publicDir.'/IconTemplate@2x.png');
echo "Generated macOS templates: public/IconTemplate.png\n";

echo "Icon generation complete!\n";
