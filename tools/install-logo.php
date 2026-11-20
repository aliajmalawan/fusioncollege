<?php
/**
 * Make a supplied logo file the website's built-in default logo.
 *
 *   C:\xampp\php\php.exe tools\install-logo.php "C:\path\to\Fusion College Logo.png"
 *
 * Saves it as images/fusion-college-logo.png (PNG, proportions and colours unchanged; only
 * scaled down when the longest side is over 1200 px), then writes the small header/footer
 * copy, favicon.ico and the app icons from it. Commit the changed files and deploy.
 * (To change the logo on the live site without a deploy, upload it in
 * AdminCP > Page Content > Site-wide instead: that does the same for the live server.)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/theme_helper.php';

$source = $argv[1] ?? '';
$info = $source !== '' ? @getimagesize($source) : false;
$loaders = [IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
if (!$info || !isset($loaders[$info[2]])) {
    fwrite(STDERR, "Give the path of a PNG, JPG or WebP logo. (For an SVG or PDF logo, export a PNG first.)\n");
    exit(1);
}

$src = $loaders[$info[2]]($source);
[$w, $h] = [imagesx($src), imagesy($src)];
$scale = min(1, 1200 / max($w, $h));
$nw = (int) round($w * $scale);
$nh = (int) round($h * $scale);
$out = imagecreatetruecolor($nw, $nh);
imagealphablending($out, false);
imagesavealpha($out, true);
imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

$target = 'images/fusion-college-logo.png';
imagepng($out, __DIR__ . '/../' . $target, 9);
imagedestroy($out);
imagedestroy($src);

if (!writeBrandIcons($target)) {
    fwrite(STDERR, "Logo saved, but the icons could not be written.\n");
    exit(1);
}
echo "Installed {$target} ({$nw} x {$nh}) with its small copy, favicon.ico and app icons.\n";
