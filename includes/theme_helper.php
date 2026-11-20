<?php
/**
 * Theme Helper Functions
 * Manages dynamic theme colors and logo
 */
require_once __DIR__ . '/image_helper.php';

// Get active theme
function getActiveTheme() {
    global $conn;
    static $theme = null;     // colours, logo and favicon all ask for it; read it once per page
    if ($theme !== null) {
        return $theme;
    }

    $query = "SELECT * FROM themes WHERE is_active = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return $theme = mysqli_fetch_assoc($result);
    }

    // Default theme if none active
    return [
        'theme_name' => 'Default Theme',
        'primary_color' => '#0B1F4D',
        'secondary_color' => '#FFFFFF',
        'accent_color' => '#E31E24',
        'logo_path' => 'images/fusion-college-logo.png'
    ];
}

// Get all themes
function getAllThemes() {
    global $conn;

    $query = "SELECT * FROM themes ORDER BY id ASC";
    $result = mysqli_query($conn, $query);

    $themes = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $themes[] = $row;
        }
    }

    return $themes;
}

// Activate theme
function activateTheme($theme_id) {
    global $conn;

    // Deactivate all themes
    mysqli_query($conn, "UPDATE themes SET is_active = 0");

    // Activate selected theme
    $theme_id = mysqli_real_escape_string($conn, $theme_id);
    $query = "UPDATE themes SET is_active = 1 WHERE id = '$theme_id'";

    return mysqli_query($conn, $query);
}

// Generate CSS variables for active theme
function getThemeCSS() {
    $theme = getActiveTheme();

    // Canonical tokens first, then the legacy names the older rules still read
    $css = ":root {\n";
    $css .= "    --primary: " . $theme['primary_color'] . ";\n";
    $css .= "    --accent: " . $theme['accent_color'] . ";\n";
    $css .= "    --primary-color: " . $theme['primary_color'] . ";\n";
    $css .= "    --secondary-color: " . $theme['secondary_color'] . ";\n";
    $css .= "    --accent-color: " . $theme['accent_color'] . ";\n";
    $css .= "}\n";

    return $css;
}

// Get logo path
function getLogoPath() {
    $theme = getActiveTheme();
    return $theme['logo_path'] ?? 'images/fusion-college-logo.png';
}

// Small logo for header/footer/admin: the "-sm" PNG copy writeBrandIcons() saves next to the logo
// (logo.jpg -> logo-sm.png) when there is one, otherwise the logo itself
function getLogoSmallPath() {
    $logo = getLogoPath();
    $small = preg_replace('/(\.[a-z0-9]+)$/i', '-sm.png', $logo);
    if ($small !== $logo && file_exists(__DIR__ . '/../' . $small)) {
        return $small;
    }
    return $logo;
}

/**
 * WebP copy of a PNG logo (logo-sm.png -> logo-sm.webp, transparency kept; less than half the size),
 * made on first use and again when the logo changes. '' when it can't be made, so the PNG is used.
 */
function logoWebpPath($path) {
    $webp = preg_replace('/\.png$/i', '.webp', $path);
    $full = __DIR__ . '/../' . $path;
    $full_webp = __DIR__ . '/../' . $webp;
    if ($webp === $path || !is_file($full) || !function_exists('imagewebp')) {
        return '';
    }
    if (!is_file($full_webp) || filemtime($full_webp) < filemtime($full)) {
        $im = @imagecreatefrompng($full);
        if (!$im) {
            return '';
        }
        imagesavealpha($im, true);
        $ok = @imagewebp($im, $full_webp, 90);
        imagedestroy($im);
        if (!$ok || filesize($full_webp) >= filesize($full)) {
            @unlink($full_webp);
            return '';
        }
    }
    return $webp;
}

// Pixel size [width, height] of a logo file (relative to the site root); [1, 1] when unreadable
function logoSize($path) {
    static $sizes = [];
    if (!isset($sizes[$path])) {
        $info = @getimagesize(__DIR__ . '/../' . $path);
        $sizes[$path] = ($info && $info[0] > 0 && $info[1] > 0) ? [$info[0], $info[1]] : [1, 1];
    }
    return $sizes[$path];
}

// width/height attributes that show a logo $height pixels tall in its own proportions
// (reserves the right space before the image loads, never stretches a wide or tall logo)
function logoImgAttrs($path, $height) {
    [$w, $h] = logoSize($path);
    return ' width="' . max(1, (int) round($height * $w / $h)) . '" height="' . (int) $height . '"';
}

// Full name used in titles, the footer and emails: "Fusion College of Sciences, Shakargarh"
function getSiteFullName() {
    return content('site_full_name');
}

/** Where the default icons live (made from the default logo and kept in the repository). */
function defaultIconPaths() {
    return [
        'ico' => 'favicon.ico',
        'png32' => 'images/icons/favicon-32.png',
        'png192' => 'images/icons/icon-192.png',
        'apple' => 'images/icons/apple-touch-icon.png',
    ];
}

/**
 * Icons the site uses now: the set made in AdminCP > Branding (stored under uploads/branding/,
 * which a deployment never overwrites) or, without one, the defaults.
 */
function getIconPaths() {
    $custom = json_decode((string) getSetting('custom_icons', ''), true);
    if (is_array($custom) && count($custom) === 4) {
        foreach ($custom as $path) {
            if (!is_string($path) || !is_file(__DIR__ . '/../' . $path)) {
                return defaultIconPaths();
            }
        }
        return $custom;
    }
    return defaultIconPaths();
}

/** GD image from a PNG, JPEG or WebP file (absolute path), or false. */
function loadBrandImage($file) {
    $info = @getimagesize($file);
    $loaders = [IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    $loader = $info ? ($loaders[$info[2]] ?? null) : null;
    $img = ($loader && function_exists($loader)) ? @$loader($file) : false;
    if ($img) {
        imagesavealpha($img, true);
    }
    return $img;
}

/** PNG bytes of a GD image (the image is freed). */
function brandPngBytes($img) {
    ob_start();
    imagepng($img, null, 9);
    imagedestroy($img);
    return ob_get_clean();
}

/**
 * Write the browser and phone icons made from one image (path relative to the site root) to $targets
 * (keys ico, png32, png192, apple; paths relative to the site root): favicon.ico with 16/32/48 px,
 * a 32 px PNG, the 192 px app icon and the 180 px Apple touch icon. The image is centred on a square
 * canvas without stretching; the Apple icon gets a white background because iOS shows transparency as black.
 */
function writeIconSet($image_path, array $targets) {
    $root = __DIR__ . '/../';
    $src = loadBrandImage($root . $image_path);
    if (!$src) {
        return false;
    }
    [$sw, $sh] = [imagesx($src), imagesy($src)];

    // Image fitted into a $size square (a $pad share of the edge left empty), on transparent or white
    $square = function ($size, $pad, $white) use ($src, $sw, $sh) {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $white ? imagecolorallocate($img, 255, 255, 255) : imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);
        $box = $size * (1 - 2 * $pad);
        $scale = min($box / $sw, $box / $sh);
        $w = max(1, (int) round($sw * $scale));
        $h = max(1, (int) round($sh * $scale));
        imagecopyresampled($img, $src, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, $sw, $sh);
        return $img;
    };

    foreach ($targets as $path) {
        $dir = dirname($root . $path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
    $ok = (bool) file_put_contents($root . $targets['png32'], brandPngBytes($square(32, 0, false)));
    $ok = file_put_contents($root . $targets['png192'], brandPngBytes($square(192, 0.04, false))) && $ok;
    $ok = file_put_contents($root . $targets['apple'], brandPngBytes($square(180, 0.08, true))) && $ok;

    // favicon.ico holding PNG images (supported by every current browser)
    $frames = [];
    foreach ([16, 32, 48] as $size) {
        $frames[$size] = brandPngBytes($square($size, 0, false));
    }
    $ico = pack('vvv', 0, 1, count($frames));
    $offset = 6 + 16 * count($frames);
    foreach ($frames as $size => $data) {
        $ico .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($data), $offset);
        $offset += strlen($data);
    }
    $ok = file_put_contents($root . $targets['ico'], $ico . implode('', $frames)) && $ok;

    imagedestroy($src);
    return $ok;
}

/** The "-sm" copy of a logo (160 px tall, PNG) used in the header and footer: logo.jpg -> logo-sm.png. */
function writeLogoSmall($logo_path) {
    $root = __DIR__ . '/../';
    $small_path = preg_replace('/(\.[a-z0-9]+)$/i', '-sm.png', $logo_path);
    $src = loadBrandImage($root . $logo_path);
    if (!$src || $small_path === $logo_path) {
        return false;
    }
    [$sw, $sh] = [imagesx($src), imagesy($src)];
    if ($sh <= 160) {
        imagedestroy($src);
        return true;   // already small: the logo itself is used
    }
    $h = 160;
    $w = max(1, (int) round($sw * $h / $sh));
    $small = imagecreatetruecolor($w, $h);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
    imagealphablending($small, true);
    imagecopyresampled($small, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
    imagedestroy($src);
    return (bool) file_put_contents($root . $small_path, brandPngBytes($small));
}

/**
 * Default icons and small logo copy from a logo kept in the repository (tools/install-logo.php).
 * Uploads from the admin panel use setSiteLogo() / setFaviconFromImage() instead.
 */
function writeBrandIcons($logo_path) {
    return writeIconSet($logo_path, defaultIconPaths()) && writeLogoSmall($logo_path);
}

/** Make a new icon set from an image into uploads/branding/ and make it the site's icons. */
function saveCustomIcons($image_path) {
    $stamp = 'icons_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 5);
    $targets = [
        'ico' => "uploads/branding/{$stamp}_favicon.ico",
        'png32' => "uploads/branding/{$stamp}_favicon-32.png",
        'png192' => "uploads/branding/{$stamp}_icon-192.png",
        'apple' => "uploads/branding/{$stamp}_apple-touch-icon.png",
    ];
    if (!writeIconSet($image_path, $targets)) {
        return false;
    }
    $previous = getIconPaths();
    saveSetting('custom_icons', json_encode($targets));
    removeCustomIcons($previous);
    return true;
}

/** Delete an earlier uploaded icon set (never the default icons). */
function removeCustomIcons(array $paths) {
    foreach ($paths as $path) {
        if (is_string($path) && strpos($path, 'uploads/branding/') === 0 && is_file(__DIR__ . '/../' . $path)) {
            @unlink(__DIR__ . '/../' . $path);
        }
    }
}

/** True when the favicon was uploaded separately (it then no longer follows the logo). */
function faviconIsUploaded() {
    return getSetting('favicon_source', 'logo') === 'upload';
}

/**
 * Make $path the site logo (header, footer, emails, sharing) for the active theme, write its small copy,
 * and, unless a separate favicon was uploaded, make the favicon from it too.
 */
function setSiteLogo($path) {
    global $conn;
    $default = 'images/fusion-college-logo.png';
    $stmt = mysqli_prepare($conn, "UPDATE themes SET logo_path = ? WHERE is_active = 1");
    mysqli_stmt_bind_param($stmt, 's', $path);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($path !== $default) {
        writeLogoSmall($path);
    }
    if (!faviconIsUploaded()) {
        $ok = useLogoForFavicon($path) && $ok;
    }
    return $ok;
}

/** Favicon made from an uploaded image (path relative to the site root). */
function setFaviconFromImage($path) {
    if (!saveCustomIcons($path)) {
        return false;
    }
    saveSetting('favicon_source', 'upload');
    return true;
}

/** Favicon follows the logo again ($logo_path, or the current logo); the default logo uses the default icons. */
function useLogoForFavicon($logo_path = null) {
    $logo_path = $logo_path ?? getLogoPath();
    saveSetting('favicon_source', 'logo');
    if ($logo_path === 'images/fusion-college-logo.png') {
        $previous = getIconPaths();
        deleteSetting('custom_icons');
        removeCustomIcons($previous);
        return true;
    }
    return saveCustomIcons($logo_path);
}

// Cache-busting version for CSS/JS links: the file's last-modified time, so browsers
// (the .htaccess caches static files for a month) fetch the new file after every edit.
function assetVersion($path) {
    $file = __DIR__ . '/../' . ltrim($path, '/');
    return is_file($file) ? filemtime($file) : 1;
}

/**
 * Link for a stylesheet in its minified form, with its cache-busting version: css/style.css ->
 * css/style.min.css?v=..., written next to it the first time and again whenever style.css changes
 * (so edit style.css as usual; the .min file is never edited by hand). Falls back to the
 * original file when the folder is not writable.
 */
function minifiedCssUrl($path) {
    $path = ltrim($path, '/');
    $src = __DIR__ . '/../' . $path;
    $min_path = preg_replace('/\.css$/', '.min.css', $path);
    $min = __DIR__ . '/../' . $min_path;
    if (!is_file($src)) {
        return $path;
    }
    if (!is_file($min) || filemtime($min) < filemtime($src)) {
        $css = minifyCss((string) file_get_contents($src));
        $tmp = $min . '.' . getmypid() . '.tmp';
        if ($css === '' || @file_put_contents($tmp, $css) === false || !@rename($tmp, $min)) {
            @unlink($tmp);
            return $path . '?v=' . filemtime($src);
        }
        @touch($min, filemtime($src));     // same time as the source: the version below changes only when style.css does
        clearstatcache(true, $min);
    }
    return $min_path . '?v=' . filemtime($min);
}

// Remove comments and spacing from CSS without touching quoted strings (content: " · " etc.)
function minifyCss($css) {
    // One left-to-right pass, so an apostrophe inside a comment is not taken for a string (and the
    // reverse); strings never span lines in CSS. Comments go, strings are parked and put back at the end.
    $strings = [];
    $css = preg_replace_callback('~/\*.*?\*/|"(?:[^"\\\\\n]++|\\\\.)*+"|\'(?:[^\'\\\\\n]++|\\\\.)*+\'~s', function ($m) use (&$strings) {
        if ($m[0][0] === '/') {
            return '';
        }
        $strings[] = $m[0];
        return "\x01" . (count($strings) - 1) . "\x01";
    }, $css);
    if ($css === null) {
        return '';
    }
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{};,>])\s*/', '$1', $css);
    $css = preg_replace('/([:(])\s+/', '$1', $css);     // "color: red" -> "color:red"; spaces BEFORE ":" stay (".a :hover")
    $css = str_replace(';}', '}', $css);
    $css = preg_replace_callback('/\x01(\d+)\x01/', function ($m) use ($strings) {
        return $strings[(int) $m[1]];
    }, $css);
    return trim($css);
}

// Update theme colors
function updateTheme($theme_id, $primary_color, $secondary_color, $accent_color) {
    global $conn;

    $theme_id = mysqli_real_escape_string($conn, $theme_id);
    $primary_color = mysqli_real_escape_string($conn, $primary_color);
    $secondary_color = mysqli_real_escape_string($conn, $secondary_color);
    $accent_color = mysqli_real_escape_string($conn, $accent_color);

    $query = "UPDATE themes SET
              primary_color = '$primary_color',
              secondary_color = '$secondary_color',
              accent_color = '$accent_color',
              updated_at = NOW()
              WHERE id = '$theme_id'";

    return mysqli_query($conn, $query);
}

// Create new theme
function createTheme($theme_name, $primary_color, $secondary_color, $accent_color, $logo_path = null) {
    global $conn;

    $theme_name = mysqli_real_escape_string($conn, $theme_name);
    $primary_color = mysqli_real_escape_string($conn, $primary_color);
    $secondary_color = mysqli_real_escape_string($conn, $secondary_color);
    $accent_color = mysqli_real_escape_string($conn, $accent_color);
    $logo_path = $logo_path ? mysqli_real_escape_string($conn, $logo_path) : 'images/fusion-college-logo.png';

    $query = "INSERT INTO themes (theme_name, primary_color, secondary_color, accent_color, logo_path, is_active)
              VALUES ('$theme_name', '$primary_color', '$secondary_color', '$accent_color', '$logo_path', 0)";

    return mysqli_query($conn, $query);
}

// Update logo for active theme
function updateThemeLogo($logo_path) {
    global $conn;

    $logo_path = mysqli_real_escape_string($conn, $logo_path);

    // Update logo path for active theme
    $query = "UPDATE themes SET logo_path = '$logo_path', updated_at = NOW() WHERE is_active = 1";

    return mysqli_query($conn, $query);
}

// Upload logo file
function uploadLogo($file) {
    $upload_dir = '../images/logos/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Upload file (compressed - logos stay small so this mostly just
    // protects against someone uploading a huge multi-MB source file)
    if (compressUploadedImage($file['tmp_name'], $filepath, 500, 90)) {
        // Return path relative to website root
        return ['success' => true, 'path' => 'images/logos/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Upload hero image
function uploadHeroImage($file) {
    $upload_dir = '../images/hero/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10MB for hero images

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 10MB.'];
    }

    // Upload file (compressed to a sane max width, always JPEG - a hero
    // banner is a photo, so JPEG compresses it far better than PNG)
    $base_filename = 'hero_' . time();
    $filename = compressUploadedPhotoAsJpeg($file['tmp_name'], $upload_dir, $base_filename, 1920, 85);

    if ($filename) {
        // Return path relative to website root
        return ['success' => true, 'path' => 'images/hero/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Update hero image in settings
function updateHeroImage($image_path) {
    global $conn;

    $image_path = mysqli_real_escape_string($conn, $image_path);

    $check_query = "SELECT id FROM settings WHERE setting_key = 'hero_image'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $query = "UPDATE settings SET setting_value = '$image_path' WHERE setting_key = 'hero_image'";
    } else {
        $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('hero_image', '$image_path')";
    }

    return mysqli_query($conn, $query);
}
?>
