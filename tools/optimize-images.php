<?php
/**
 * Optimise photos that were added before automatic compression (or copied in by hand):
 *
 *   C:\xampp\php\php.exe tools\optimize-images.php          (report only, changes nothing)
 *   C:\xampp\php\php.exe tools\optimize-images.php --fix    (do it)
 *
 * For every JPEG/PNG photo in uploads/ and images/hero/ (logos and branding excluded):
 *  - a JPEG larger than 1920 px is scaled down to 1920 px and saved as a progressive JPEG,
 *  - a WebP copy is written next to it (photo.jpg -> photo.webp) when it is missing; the gallery
 *    viewer, slider and banners serve that smaller copy to browsers that support it,
 *  - home slider photos get 640 / 1280 px copies so phones and laptops download less,
 *  - the gallery thumbnail is built so the first visitor does not wait for it.
 * Original file names never change, so nothing on the site needs updating. Safe to run again.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/image_helper.php';

$fix = in_array('--fix', $argv, true);
$root = dirname(__DIR__) . '/';
chdir($root);
$max = 1920;

$files = [];
foreach (['uploads', 'images/hero'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (!preg_match('/\.(jpe?g|png)$/i', $path) || preg_match('~/(thumbs|branding)/~', $path) || preg_match('~/logo_[^/]*$~i', $path)
            || preg_match('/-(640|700|960|1280)\.(jpe?g|png)$/i', $path)) {
            continue;   // generated copies are rebuilt from their photo, not optimised on their own
        }
        $files[] = $path;
    }
}
sort($files);

$before = $after = 0;
$resized = $twins = 0;
foreach ($files as $path) {
    $info = @getimagesize($path);
    if (!$info) {
        continue;
    }
    [$w, $h, $type] = $info;
    $size = filesize($path);
    $before += $size;
    $notes = [];

    if ($type === IMAGETYPE_JPEG && max($w, $h) > $max) {
        $notes[] = "{$w}x{$h} -> {$max}px";
        if ($fix) {
            ensureImageMemory($w, $h);
            $src = @imagecreatefromjpeg($path);
            if ($src) {
                $scale = $max / max($w, $h);
                $nw = (int) round($w * $scale);
                $nh = (int) round($h * $scale);
                $dst = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imageinterlace($dst, true);
                $tmp = $path . '.tmp';
                if (imagejpeg($dst, $tmp, 85) && filesize($tmp) < $size) {
                    rename($tmp, $path);
                    $resized++;
                } else {
                    @unlink($tmp);
                }
                imagedestroy($src);
                imagedestroy($dst);
                clearstatcache(true, $path);
            }
        }
    }

    $twin = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if (!is_file($twin) || filemtime($twin) < filemtime($path)) {
        $notes[] = 'WebP copy';
        if ($fix && createWebpTwin($path)) {
            $twins++;
        }
    }

    if (strpos($path, 'images/hero/') === 0 && max($w, $h) > 640) {
        $variant = preg_replace('/\.(jpe?g|png)$/i', '-640.$1', $path);
        if (!is_file($variant) || filemtime($variant) < filemtime($path)) {
            $notes[] = '640/1280 px copies';
            if ($fix) {
                createSizeVariants($path);
            }
        }
    }

    if ($fix && strpos($path, 'uploads/gallery/') === 0) {
        mediaThumb($path);
    }

    clearstatcache(true, $path);
    $after += filesize($path);
    if ($notes) {
        printf("%-70s %8s  %s\n", $path, formatFileSize($size), implode(', ', $notes));
    }
}

printf("\n%d photos checked, %s in all%s.\n", count($files), formatFileSize($before),
    $fix ? sprintf(" (now %s); %d scaled down, %d WebP copies written", formatFileSize($after), $resized, $twins) : '');
if (!$fix) {
    echo "Nothing was changed. Run again with --fix to do the items listed above.\n";
}
