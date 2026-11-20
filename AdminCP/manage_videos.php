<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$is_error = false;

try {
    // Facebook videos need two more columns than the first version of this table had: add them once
    $have = [];
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM gallery_videos");
    while ($cols && ($col = mysqli_fetch_assoc($cols))) {
        $have[] = $col['Field'];
    }
    if (!in_array('video_url', $have, true)) {
        mysqli_query($conn, "ALTER TABLE gallery_videos ADD COLUMN video_url VARCHAR(500) NULL AFTER youtube_id");
    }
    if (!in_array('poster_image', $have, true)) {
        mysqli_query($conn, "ALTER TABLE gallery_videos ADD COLUMN poster_image VARCHAR(255) NULL AFTER video_url");
    }
} catch (mysqli_sql_exception $e) {
    // table missing: the page explains how to create it
}

// Remove a cover photo uploaded for a video (never touches anything outside uploads/gallery/videos/)
function deleteVideoPoster($path) {
    $path = (string) $path;
    if (strpos($path, 'uploads/gallery/videos/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    foreach ([$path, preg_replace('/\.(jpe?g|png)$/i', '.webp', $path)] as $file) {
        if (is_file('../' . $file)) {
            @unlink('../' . $file);
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $old = mysqli_query($conn, "SELECT poster_image FROM gallery_videos WHERE id = " . $id);
    $old = $old ? mysqli_fetch_assoc($old) : null;
    $stmt = mysqli_prepare($conn, "DELETE FROM gallery_videos WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok && $old) {
        deleteVideoPoster($old['poster_image']);
    }
    $message = $ok ? 'Video removed.' : 'Error removing video.';
    $is_error = !$ok;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['video_link'] ?? '');
    $youtube_id = youtubeId($link);
    $facebook_url = $youtube_id === '' ? facebookVideoUrl($link) : '';
    $description = trim($_POST['description'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
    $video_id = intval($_POST['video_id'] ?? 0);

    $current = null;
    if ($video_id > 0) {
        $current = mysqli_query($conn, "SELECT * FROM gallery_videos WHERE id = " . $video_id);
        $current = $current ? mysqli_fetch_assoc($current) : null;
    }
    $poster = $current['poster_image'] ?? null;

    if ($title === '') {
        $message = 'Error: please give the video a title.';
        $is_error = true;
    } elseif ($youtube_id === '' && $facebook_url === '') {
        $message = 'Error: that is not a YouTube or Facebook video link. Open the video, press Share > Copy link, and paste it here (e.g. https://youtu.be/abc123XYZ00 or https://www.facebook.com/.../videos/123...).';
        $is_error = true;
    } else {
        // Optional cover photo: compressed like every other upload
        if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/gallery/videos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $saved = @getimagesize($_FILES['poster']['tmp_name'])
                ? compressUploadedPhotoAsJpeg($_FILES['poster']['tmp_name'], $upload_dir, 'video_' . time() . '_' . substr(uniqid(), -5), 1280, 82)
                : false;
            if ($saved) {
                deleteVideoPoster($poster);
                $poster = 'uploads/gallery/videos/' . $saved;
                mediaThumb($poster);
            } else {
                $message = 'Note: the cover photo could not be read (use a JPG, PNG or WebP photo), so the video was saved without it. ';
            }
        } elseif (!empty($_POST['remove_poster'])) {
            deleteVideoPoster($poster);
            $poster = null;
        }

        $video_url = $facebook_url !== '' ? $facebook_url : null;
        if ($video_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE gallery_videos SET title = ?, youtube_id = ?, video_url = ?, poster_image = ?, description = ?, display_order = ?, status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'sssssisi', $title, $youtube_id, $video_url, $poster, $description, $display_order, $status, $video_id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO gallery_videos (title, youtube_id, video_url, poster_image, description, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssis', $title, $youtube_id, $video_url, $poster, $description, $display_order, $status);
        }
        $ok = mysqli_stmt_execute($stmt);
        $message .= $ok ? 'Video saved. It now appears in the Videos section of the Photo Gallery page.' : 'Error saving video.';
        $is_error = !$ok;
    }
}

$edit_video = null;
if (isset($_GET['edit'])) {
    // intval keeps this safe; plain query because mysqli_stmt_get_result needs mysqlnd
    $edit_result = mysqli_query($conn, "SELECT * FROM gallery_videos WHERE id = " . intval($_GET['edit']));
    $edit_video = $edit_result ? mysqli_fetch_assoc($edit_result) : null;
}
$edit_link = '';
if ($edit_video) {
    $edit_link = $edit_video['youtube_id'] !== '' ? 'https://youtu.be/' . $edit_video['youtube_id'] : (string) ($edit_video['video_url'] ?? '');
}

try {
    $videos = mysqli_query($conn, "SELECT * FROM gallery_videos ORDER BY display_order ASC, id DESC");
} catch (mysqli_sql_exception $e) {
    $videos = false;   // table missing: the page explains how to create it
}
$table_missing = $videos === false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <title>Gallery Videos - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_videos.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-video"></i> Gallery Videos</h1>
            </div>
            <a href="../gallery.php#videos" target="_blank" class="btn btn-outline-dark"><i class="fas fa-eye"></i> View on website</a>
        </div>

        <?php if ($table_missing): ?>
            <div class="alert alert-error"><i class="fas fa-database"></i> The videos table doesn't exist yet. Run <strong>database/upgrade-gallery-videos.sql</strong> once in phpMyAdmin, then reload this page.</div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert <?php echo $is_error ? 'alert-error' : 'alert-success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <p style="color: var(--text-light); margin-bottom: 20px;">Post the video on the college's Facebook page or YouTube channel (set it to <strong>Public</strong>), then paste its link here. Videos play on the Photo Gallery page and only load when a visitor presses play, so they never slow the website down, and they stream in HD without using the website's storage.</p>

        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_video ? 'Edit Video' : 'Add a Video'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_video): ?>
                    <input type="hidden" name="video_id" value="<?php echo intval($edit_video['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="video_title">Title *</label>
                    <input type="text" id="video_title" name="title" required maxlength="200" value="<?php echo $edit_video ? htmlspecialchars($edit_video['title']) : ''; ?>" placeholder="e.g., Annual Sports Day 2026">
                </div>

                <div class="form-group">
                    <label for="video_url">Facebook or YouTube video link *</label>
                    <input type="text" id="video_url" name="video_link" required value="<?php echo htmlspecialchars($edit_link); ?>" placeholder="https://www.facebook.com/.../videos/... or https://youtu.be/...">
                    <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">On Facebook: open the video &gt; Share &gt; Copy link. On YouTube: Share &gt; Copy.</small>
                </div>

                <div class="form-group">
                    <label for="video_poster">Cover photo (optional)</label>
                    <?php if ($edit_video && !empty($edit_video['poster_image'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <img src="../<?php echo htmlspecialchars($edit_video['poster_image']); ?>" alt="" width="128" height="72" style="border-radius: 6px; object-fit: cover;">
                            <label style="font-weight: 500; display: flex; align-items: center; gap: 6px; margin: 0;"><input type="checkbox" name="remove_poster" value="1"> Remove this cover photo</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="video_poster" name="poster" accept="image/*">
                    <?php echo uploadSizeHint('1280 × 720 px', 'widescreen 16:9'); ?>
                    <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">Shown before the video plays. YouTube videos use their own thumbnail when you leave this empty; Facebook videos show a branded cover with the title. Photos are compressed automatically.</small>
                </div>

                <div class="form-group">
                    <label for="video_desc">Short description (optional)</label>
                    <input type="text" id="video_desc" name="description" maxlength="300" value="<?php echo $edit_video ? htmlspecialchars((string) $edit_video['description']) : ''; ?>" placeholder="One line shown under the video">
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="video_order">Display Order</label>
                        <input type="number" id="video_order" name="display_order" value="<?php echo $edit_video ? intval($edit_video['display_order']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label for="video_status">Status</label>
                        <select id="video_status" name="status">
                            <option value="active" <?php echo (!$edit_video || $edit_video['status'] == 'active') ? 'selected' : ''; ?>>Active (shown)</option>
                            <option value="inactive" <?php echo ($edit_video && $edit_video['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (hidden)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_video ? 'Update' : 'Add Video'; ?></button>
                <?php if ($edit_video): ?>
                    <a href="manage_videos.php" class="btn btn-outline-dark" style="margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Videos</h2>
            <?php if ($videos && mysqli_num_rows($videos) > 0): ?>
                <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left;">Video</th>
                            <th style="padding: 12px; text-align: left;">Title</th>
                            <th style="padding: 12px; text-align: left;">Order</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                            <th style="padding: 12px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($v = mysqli_fetch_assoc($videos)): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px;">
                                    <?php $is_fb = $v['youtube_id'] === '';
                                    $cover = !empty($v['poster_image']) ? '../' . $v['poster_image'] : ($is_fb ? '' : 'https://i.ytimg.com/vi/' . $v['youtube_id'] . '/mqdefault.jpg'); ?>
                                    <?php if ($cover !== ''): ?>
                                        <img src="<?php echo htmlspecialchars($cover); ?>" alt="" width="128" height="72" loading="lazy" style="border-radius: 6px; display: block; object-fit: cover;">
                                    <?php else: ?>
                                        <span style="width: 128px; height: 72px; border-radius: 6px; display: flex; align-items: center; justify-content: center; background: #0B1F4D; color: #fff; font-size: 26px;"><i class="fab fa-facebook"></i></span>
                                    <?php endif; ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; margin-top: 6px; font-size: 12px; color: var(--text-light);"><i class="fab <?php echo $is_fb ? 'fa-facebook' : 'fa-youtube'; ?>"></i> <?php echo $is_fb ? 'Facebook' : 'YouTube'; ?></span>
                                </td>
                                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($v['title']); ?></strong><?php if (!empty($v['description'])): ?><br><span style="color: var(--text-light); font-size: 13px;"><?php echo htmlspecialchars($v['description']); ?></span><?php endif; ?></td>
                                <td style="padding: 12px;"><?php echo intval($v['display_order']); ?></td>
                                <td style="padding: 12px;"><span style="background: <?php echo $v['status'] == 'active' ? 'var(--primary)' : 'var(--muted)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px;"><?php echo ucfirst($v['status']); ?></span></td>
                                <td style="padding: 12px; white-space: nowrap;">
                                    <a href="?edit=<?php echo intval($v['id']); ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                                    <a href="?action=delete&id=<?php echo intval($v['id']); ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: var(--accent);" onclick="return confirm('Remove this video from the gallery?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php elseif (!$table_missing): ?>
                <p style="text-align: center; padding: 30px; color: var(--text-light);">No videos yet. Add the first one above.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
