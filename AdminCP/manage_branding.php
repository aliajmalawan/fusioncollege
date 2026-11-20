<?php
/**
 * Logo & Favicon: upload the college logo and the browser-tab icon (favicon).
 * Files are kept under uploads/branding/ (a deployment never overwrites them); every icon size
 * the site needs is made from the uploaded image automatically (see setSiteLogo() and
 * setFaviconFromImage() in includes/theme_helper.php).
 */
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

const DEFAULT_LOGO = 'images/fusion-college-logo.png';

/**
 * Save an uploaded logo or favicon image under uploads/branding/ in its own format (PNG transparency
 * is kept), scaled down to $max px when larger. Returns ['path' => ...] or ['error' => ...].
 */
function saveBrandUpload($file, $prefix, $max, $min_side) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Please choose an image first.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'The upload did not finish. Please try again with a file under 8 MB.'];
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        return ['error' => 'The file is larger than 8 MB. Please choose a smaller image.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
        return ['error' => 'Please upload a PNG, JPG or WebP image. (An .ico or .svg file can be saved as PNG first.)'];
    }
    if (min($info[0], $info[1]) < $min_side) {
        return ['error' => "The image is too small ({$info[0]} x {$info[1]} px). Please use one at least {$min_side} px on its shortest side."];
    }
    $name = compressUploadedImageKeepFormat($file['tmp_name'], '../uploads/branding/', $prefix . '_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 5), $max, 92);
    return $name ? ['path' => 'uploads/branding/' . $name] : ['error' => 'The image could not be saved. Please try again.'];
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_logo') {
        $saved = saveBrandUpload($_FILES['logo'] ?? null, 'logo', 1200, 64);
        if (isset($saved['error'])) {
            $error = $saved['error'];
        } elseif (setSiteLogo($saved['path'])) {
            $message = faviconIsUploaded()
                ? 'New logo saved. It now appears in the header, footer, emails and share previews. (Your uploaded favicon is kept.)'
                : 'New logo saved. It now appears in the header, footer, emails and share previews, and the favicon was made from it.';
        } else {
            $error = 'The logo was saved but could not be applied. Please try again.';
        }
    } elseif ($action === 'reset_logo') {
        setSiteLogo(DEFAULT_LOGO);
        $message = 'The default logo is back.';
    } elseif ($action === 'upload_favicon') {
        $saved = saveBrandUpload($_FILES['favicon'] ?? null, 'favicon', 512, 48);
        if (isset($saved['error'])) {
            $error = $saved['error'];
        } elseif (setFaviconFromImage($saved['path'])) {
            $message = 'New favicon saved. Browsers may keep showing the old icon for a short while; a hard refresh (Ctrl + F5) shows the new one.';
        } else {
            $error = 'The favicon could not be made from that image. Please try another PNG, JPG or WebP file.';
        }
    } elseif ($action === 'favicon_from_logo') {
        $message = useLogoForFavicon() ? 'The favicon is now made from the logo again.' : '';
        $error = $message === '' ? 'The favicon could not be made from the current logo.' : '';
    }
    if ($error === '') {
        // Post/Redirect/Get: refreshing the page doesn't upload the file again
        $_SESSION['branding_flash'] = $message;
        header('Location: manage_branding.php', true, 303);
        exit;
    }
}
if (!empty($_SESSION['branding_flash'])) {
    $message = $_SESSION['branding_flash'];
    unset($_SESSION['branding_flash']);
}

$logo = getLogoPath();
$logo_is_default = $logo === DEFAULT_LOGO;
[$logo_w, $logo_h] = logoSize($logo);
$icons = getIconPaths();
$favicon_uploaded = faviconIsUploaded();
$v = function ($path) { return htmlspecialchars('../' . $path) . '?v=' . assetVersion($path); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <title>Logo &amp; Favicon - Admin Panel</title>
    <link rel="icon" type="image/png" href="<?php echo $v($icons['png32']); ?>">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .brand-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        .brand-preview { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
        .brand-preview figure { margin: 0; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .brand-preview figure.on-navy { background: var(--navy, #0B1F4D); }
        .brand-preview img { height: 72px; width: auto; max-width: 100%; object-fit: contain; }
        .brand-preview figcaption { font-size: 12px; color: #64748B; }
        .brand-preview figure.on-navy figcaption { color: rgba(255,255,255,.75); }
        .brand-meta { font-size: 13px; color: #64748B; margin-bottom: 16px; }
        .brand-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #E8EFFB; color: var(--royal, #1456C0); }
        .icon-row { display: flex; align-items: flex-end; gap: 22px; flex-wrap: wrap; margin-bottom: 18px; }
        .icon-row figure { margin: 0; text-align: center; }
        .icon-row figcaption { font-size: 12px; color: #64748B; margin-top: 6px; }
        .icon-row img { display: block; margin: 0 auto; image-rendering: auto; }
        /* a browser tab drawn in CSS to show the favicon in context */
        .tab-mock { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; background: #fff; border: 1px solid #CBD5E1; border-bottom: 0; border-radius: 10px 10px 0 0; font-size: 13px; color: #172033; max-width: 100%; }
        .tab-mock span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tab-bar { background: #e9ecf3; padding: 8px 10px 0; border-radius: 10px 10px 0 0; margin-bottom: 18px; }
        .brand-upload { border-top: 1px solid #E8EEF6; padding-top: 18px; }
        .brand-upload .ad-field { margin-bottom: 14px; }
        .brand-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .brand-actions form { margin: 0; }
        @media (max-width: 1100px) { .brand-grid { grid-template-columns: 1fr; } }
        @media (max-width: 520px) { .brand-preview { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_branding.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
<div class="ad-wrap">

    <div class="ad-head">
        <div class="ad-head-left">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
            <div>
                <h1>Logo &amp; Favicon</h1>
                <p>The college logo and the small icon shown in browser tabs, bookmarks and on phone home screens.</p>
            </div>
        </div>
        <a href="../" target="_blank" rel="noopener" class="ad-btn ghost"><i class="fas fa-arrow-up-right-from-square"></i> View website</a>
    </div>

    <?php if ($message): ?>
        <div class="ad-alert ok" role="status"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="ad-alert bad" role="alert"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="brand-grid">
        <!-- Logo -->
        <div class="ad-panel">
            <div class="ad-panel-head">
                <h2><i class="fas fa-image"></i> College logo</h2>
                <span class="brand-badge"><?php echo $logo_is_default ? 'Default logo' : 'Uploaded logo'; ?></span>
            </div>
            <div class="ad-panel-body">
                <div class="brand-preview">
                    <figure>
                        <img src="<?php echo $v($logo); ?>" alt="Current logo on a white background">
                        <figcaption>Header (white)</figcaption>
                    </figure>
                    <figure class="on-navy">
                        <img src="<?php echo $v($logo); ?>" alt="Current logo on the navy footer">
                        <figcaption>Footer (navy)</figcaption>
                    </figure>
                </div>
                <p class="brand-meta"><?php echo (int) $logo_w; ?> x <?php echo (int) $logo_h; ?> px. Shown in its own shape everywhere: never cropped or stretched.</p>

                <form method="POST" enctype="multipart/form-data" class="brand-upload">
                    <input type="hidden" name="action" value="upload_logo">
                    <div class="ad-field">
                        <label for="logo">Upload a new logo</label>
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" required>
                        <?php echo uploadSizeHint('800 × 800 px or larger', 'any shape, transparent PNG best', 8); ?>
                        <small>PNG with a transparent background works best (JPG and WebP also work), up to 8 MB. Any shape is fine: round, square or wide.<?php echo $favicon_uploaded ? '' : ' The favicon is made from it automatically.'; ?></small>
                    </div>
                    <div class="brand-actions">
                        <button type="submit" class="ad-btn primary"><i class="fas fa-upload"></i> Upload logo</button>
                    </div>
                </form>
                <?php if (!$logo_is_default): ?>
                <form method="POST" style="margin-top: 10px;" onsubmit="return confirm('Put the default logo back?');">
                    <input type="hidden" name="action" value="reset_logo">
                    <button type="submit" class="ad-btn ghost"><i class="fas fa-rotate-left"></i> Restore default logo</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Favicon -->
        <div class="ad-panel">
            <div class="ad-panel-head">
                <h2><i class="fas fa-star"></i> Favicon (browser tab icon)</h2>
                <span class="brand-badge"><?php echo $favicon_uploaded ? 'Uploaded separately' : 'Made from the logo'; ?></span>
            </div>
            <div class="ad-panel-body">
                <div class="tab-bar">
                    <div class="tab-mock"><img src="<?php echo $v($icons['png32']); ?>" width="16" height="16" alt=""><span><?php echo htmlspecialchars(getSiteFullName()); ?></span></div>
                </div>
                <div class="icon-row">
                    <figure><img src="<?php echo $v($icons['png32']); ?>" width="32" height="32" alt="32 pixel favicon"><figcaption>Browser tab</figcaption></figure>
                    <figure><img src="<?php echo $v($icons['apple']); ?>" width="60" height="60" alt="Phone home-screen icon" style="border-radius: 14px;"><figcaption>iPhone home screen</figcaption></figure>
                    <figure><img src="<?php echo $v($icons['png192']); ?>" width="72" height="72" alt="App icon"><figcaption>Android / app icon</figcaption></figure>
                </div>

                <form method="POST" enctype="multipart/form-data" class="brand-upload">
                    <input type="hidden" name="action" value="upload_favicon">
                    <div class="ad-field">
                        <label for="favicon">Upload a favicon</label>
                        <input type="file" id="favicon" name="favicon" accept="image/png,image/jpeg,image/webp" required>
                        <?php echo uploadSizeHint('512 × 512 px', 'square', 8); ?>
                        <small>A square PNG of at least 192 x 192 px looks best. A simple symbol reads better than the full logo at this tiny size. All the icon sizes above are made from it.</small>
                    </div>
                    <div class="brand-actions">
                        <button type="submit" class="ad-btn primary"><i class="fas fa-upload"></i> Upload favicon</button>
                    </div>
                </form>
                <?php if ($favicon_uploaded): ?>
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="favicon_from_logo">
                    <button type="submit" class="ad-btn ghost"><i class="fas fa-wand-magic-sparkles"></i> Make the favicon from the logo instead</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
<script>
// Show the chosen image in the previews before uploading
[['logo', '.brand-preview img'], ['favicon', '.icon-row img, .tab-mock img']].forEach(([id, selector]) => {
    document.getElementById(id).addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const url = URL.createObjectURL(this.files[0]);
            this.closest('.ad-panel').querySelectorAll(selector).forEach(img => { img.src = url; });
        }
    });
});
</script>
</body>
</html>
