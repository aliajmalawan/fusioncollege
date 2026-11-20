<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = 'success';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = true;

    // Principal photo upload (optional) - only touch the setting if a new
    // file was actually chosen, so re-saving the rest of the form doesn't
    // wipe out an existing photo.
    $photo_savings = '';
    if (isset($_FILES['principal_photo_upload']) && $_FILES['principal_photo_upload']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['principal_photo_upload']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = '../uploads/staff/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $base_filename = 'principal_' . time() . '_' . uniqid();
            $new_filename = compressUploadedPhotoAsJpeg($_FILES['principal_photo_upload']['tmp_name'], $upload_dir, $base_filename, 800, 85);
            if ($new_filename) {
                $new_filepath = $upload_dir . $new_filename;
                $_POST['principal_photo'] = 'uploads/staff/' . $new_filename;
                $photo_savings = describeCompressionSavings($_FILES['principal_photo_upload']['tmp_name'], $new_filepath);
            }
        }
    }

    // Social links are plain text inputs (not type="url") so a value without a scheme doesn't
    // block submission: "facebook.com/page" becomes a full link, a WhatsApp number a chat link.
    foreach (socialNetworks() as $network => $info) {
        if (isset($_POST[$info['key']])) {
            $_POST[$info['key']] = normalizeSocialUrl($network, $_POST[$info['key']]);
        }
    }

    // Contact details: an empty value can't be "confirmed", and an email that isn't a valid address stays unconfirmed
    $contact_note = '';
    foreach (array_keys(contactFields()) as $contact_key) {
        if (!isset($_POST[$contact_key])) {
            continue;
        }
        $_POST[$contact_key] = trim((string) $_POST[$contact_key]);
        if ($_POST[$contact_key] === '' || $_POST[$contact_key][0] === '#') {
            $_POST['verified_' . $contact_key] = '0';
        }
    }
    // Official website address: only a full http(s) address is kept
    if (isset($_POST['official_site_url'])) {
        $_POST['official_site_url'] = rtrim(trim((string) $_POST['official_site_url']), '/');
        if ($_POST['official_site_url'] !== '' && !preg_match('#^https?://[a-z0-9.-]+\.[a-z]{2,}(:[0-9]+)?(/[^\s]*)?$#i', $_POST['official_site_url'])) {
            $_POST['official_site_url'] = '';
            $contact_note .= ' The website address was not a full address (like https://www.yourcollege.edu.pk), so it was not saved.';
        }
    }
    if (($_POST['site_email'] ?? '') !== '' && !filter_var($_POST['site_email'], FILTER_VALIDATE_EMAIL)) {
        $_POST['verified_site_email'] = '0';
        $contact_note .= ' The email address does not look valid, so it stays hidden until it is corrected.';
    }

    foreach ($_POST as $key => $value) {
        if ($key !== 'submit' && $key !== 'csrf_token') {   // the form's security token is not a setting
            $key_escaped = mysqli_real_escape_string($conn, $key);
            $value_escaped = mysqli_real_escape_string($conn, $value);

            // Check if setting exists
            $check_query = "SELECT id FROM settings WHERE setting_key = '$key_escaped'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                // Update existing setting
                $update_query = "UPDATE settings SET setting_value = '$value_escaped' WHERE setting_key = '$key_escaped'";
                if (!mysqli_query($conn, $update_query)) {
                    $success = false;
                }
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped')";
                if (!mysqli_query($conn, $insert_query)) {
                    $success = false;
                }
            }
        }
    }

    if ($success) {
        $message = "Settings saved." . ($photo_savings ? " Principal photo compressed{$photo_savings}." : "") . $contact_note;
        $message_type = $contact_note ? 'warning' : 'success';
    } else {
        $message = "Some settings could not be saved. Please try again.";
        $message_type = 'error';
    }
}

// Load settings using helper function
loadSettings();

$sections = [
    'general' => ['fa-circle-info', 'General'],
    'contact-details' => ['fa-address-book', 'Contact Details'],
    'social-media' => ['fa-share-nodes', 'Social Media'],
    'home-page' => ['fa-house', 'Home Page'],
    'statistics' => ['fa-chart-simple', 'Statistics'],
    'about-page' => ['fa-building-columns', 'About Page'],
    'principal' => ['fa-user-tie', "Principal's Message"],
    'assessment' => ['fa-clipboard-check', 'Assessment'],
    'footer' => ['fa-table-columns', 'Footer'],
];

// Badge for a contact detail or social link: not set / shown on the website / saved but hidden
$contact_state = function ($key) {
    $value = trim((string) getSetting($key, ''));
    if ($value === '' || $value[0] === '#') {
        return 'empty';
    }
    return contactConfirmed($key) ? 'confirmed' : 'pending';
};
$state_labels = ['empty' => 'Not set', 'confirmed' => 'Shown on website', 'pending' => 'Hidden'];

$social_count = count(array_filter(getSocialMedia(), function ($url) { return $url !== '' && $url[0] !== '#'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <title>Site Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .settings-page { max-width: 1240px; margin: 0 auto; padding: 28px 28px 0; }

        /* page header */
        .settings-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .settings-head h1 { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--navy); margin: 0; letter-spacing: -0.01em; }
        .settings-head p { margin: 6px 0 0; color: var(--text-muted); font-size: 15px; }
        .settings-head-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .s-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 14.5px; text-decoration: none; border: 1px solid transparent; cursor: pointer; font-family: var(--font-body); transition: background .2s, border-color .2s, color .2s; }
        .s-btn-primary { background: var(--royal); color: #fff; box-shadow: 0 6px 16px rgba(20, 86, 192, .25); }
        .s-btn-primary:hover { background: var(--royal-dark); }
        .s-btn-ghost { background: #fff; color: var(--navy); border-color: var(--border); }
        .s-btn-ghost:hover { border-color: var(--royal); color: var(--royal); }

        .s-alert { display: flex; gap: 12px; align-items: flex-start; padding: 14px 18px; border-radius: 12px; margin-bottom: 22px; font-size: 14.5px; border: 1px solid; }
        .s-alert i { margin-top: 2px; }
        .s-alert-success { background: #ECFDF3; border-color: #ABEFC6; color: #067647; }
        .s-alert-warning { background: #FFFAEB; border-color: #FEDF89; color: #93370D; }
        .s-alert-error { background: #FEF3F2; border-color: #FECDCA; color: #B42318; }

        /* two columns: section menu + cards */
        .settings-layout { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 28px; align-items: start; }
        .settings-nav { position: sticky; top: 24px; background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 10px; }
        .settings-nav a { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 9px; color: var(--text); text-decoration: none; font-size: 14.5px; font-weight: 600; }
        .settings-nav a i { width: 18px; text-align: center; color: var(--text-muted); }
        .settings-nav a:hover { background: var(--bg-alt); }
        .settings-nav a.is-active { background: var(--royal-soft); color: var(--royal); }
        .settings-nav a.is-active i { color: var(--royal); }
        .settings-nav .nav-count { margin-left: auto; font-size: 11.5px; font-weight: 700; background: var(--bg-alt); color: var(--text-muted); border-radius: 999px; padding: 2px 8px; }

        .settings-page section.s-card { padding: 0; }   /* the website's own section spacing does not apply here */
        .s-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; margin-bottom: 24px; scroll-margin-top: 20px; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .s-card-head { display: flex; gap: 16px; align-items: flex-start; padding: 22px 26px; border-bottom: 1px solid var(--border); }
        .s-icon { flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--royal-soft); color: var(--royal); font-size: 18px; }
        .s-card-head h2 { font-family: var(--font-display); font-size: 19px; font-weight: 800; color: var(--navy); margin: 0; }
        .s-card-head p { margin: 4px 0 0; color: var(--text-muted); font-size: 14px; line-height: 1.55; }
        .s-card-body { padding: 24px 26px 6px; }

        /* fields */
        .s-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 22px; }
        .s-field { margin-bottom: 20px; }
        .s-field.span-2 { grid-column: 1 / -1; }
        .s-label { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-weight: 700; font-size: 14px; color: var(--text); margin-bottom: 8px; }
        .settings-page input[type=text], .settings-page input[type=email], .settings-page input[type=url],
        .settings-page input[type=number], .settings-page textarea {
            width: 100%; padding: 11px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14.5px; color: var(--text);
            background: #fff; font-family: var(--font-body); line-height: 1.5; transition: border-color .15s, box-shadow .15s;
        }
        .settings-page textarea { resize: vertical; min-height: 84px; }
        .settings-page input:focus, .settings-page textarea:focus { outline: none; border-color: var(--royal); box-shadow: 0 0 0 4px rgba(20, 86, 192, .12); }
        .settings-page input::placeholder, .settings-page textarea::placeholder { color: #94A3B8; }
        .s-help { display: block; margin-top: 7px; font-size: 12.5px; line-height: 1.5; color: var(--text-muted); }
        .s-note { display: flex; gap: 10px; background: var(--bg-alt); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; font-size: 13.5px; line-height: 1.55; color: #475569; margin-bottom: 20px; }
        .s-note i { color: var(--royal); margin-top: 3px; }
        .s-note a { color: var(--royal); font-weight: 600; }
        .s-warning { display: flex; gap: 10px; background: #FFFAEB; border: 1px solid #FEDF89; color: #93370D; border-radius: 10px; padding: 12px 14px; font-size: 13.5px; margin-bottom: 20px; }

        .s-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
        .s-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .s-badge.is-confirmed { background: #ECFDF3; color: #067647; }
        .s-badge.is-pending { background: #FFFAEB; color: #B54708; }
        .s-badge.is-empty { background: #F1F5F9; color: #64748B; }

        /* on/off switch for "show on the website" */
        .s-switch { position: relative; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13.5px; font-weight: 600; color: var(--text); user-select: none; margin-top: 14px; }
        .s-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .s-switch .track { position: relative; width: 40px; height: 22px; border-radius: 999px; background: #CBD5E1; transition: background .2s; flex-shrink: 0; }
        .s-switch .track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25); transition: transform .2s; }
        .s-switch input:checked + .track { background: #16A34A; }
        .s-switch input:checked + .track::after { transform: translateX(18px); }
        .s-switch input:focus-visible + .track { box-shadow: 0 0 0 4px rgba(20, 86, 192, .2); }

        .contact-grid { align-items: start; }
        .contact-item { border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 20px; }
        .contact-item .s-field { margin-bottom: 0; }
        .contact-item .s-field + .s-field { margin-top: 16px; }

        /* social media cards */
        .social-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
        .social-card { border: 1px solid var(--border); border-radius: 14px; padding: 16px; transition: border-color .2s, box-shadow .2s; }
        .social-card.is-on { border-color: #ABEFC6; box-shadow: 0 0 0 3px #ECFDF3; }
        .social-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .social-logo { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 18px; flex-shrink: 0; }
        .social-name { display: block; font-weight: 800; color: var(--navy); font-size: 15px; line-height: 1.2; margin-bottom: 4px; }
        .social-open { margin-left: auto; width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; }
        .social-open:hover { color: var(--royal); border-color: var(--royal); }
        .social-card .s-switch { margin-top: 12px; }

        .principal-photo { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .principal-photo img { width: 72px; height: 72px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); }
        .principal-photo .placeholder { width: 72px; height: 72px; border-radius: 12px; display: grid; place-items: center; background: var(--bg-alt); color: #94A3B8; font-size: 26px; border: 1px dashed #CBD5E1; }

        /* save bar stays at the bottom of the screen */
        .settings-savebar { position: sticky; bottom: 0; z-index: 20; display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;
            margin: 4px 0 0; padding: 14px 20px; background: rgba(255,255,255,.96); backdrop-filter: blur(8px); border: 1px solid var(--border); border-bottom: 0; border-radius: 14px 14px 0 0; box-shadow: 0 -8px 24px rgba(11, 31, 77, .06); }
        .settings-savebar .state { font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
        .settings-savebar.is-dirty .state { color: #B54708; font-weight: 600; }

        @media (max-width: 1100px) {
            .settings-layout { grid-template-columns: 1fr; gap: 18px; }
            .settings-nav { position: static; display: flex; overflow-x: auto; gap: 4px; padding: 6px; }
            .settings-nav a { white-space: nowrap; padding: 8px 12px; }
            .settings-nav .nav-count { display: none; }
        }
        @media (max-width: 760px) {
            .settings-page { padding: 18px 16px 0; }
            .s-grid, .social-grid { grid-template-columns: 1fr; }
            .s-card-head, .s-card-body { padding-left: 18px; padding-right: 18px; }
            .settings-head h1 { font-size: 24px; }
            .settings-head-actions .s-btn-primary { display: none; }   /* the save bar below does this on phones */
        }
    </style>
</head>
<body style="background: var(--bg-alt);">
<div class="admin-shell">
<?php $active_page = 'settings.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="settings-page">
        <div class="settings-head">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <div>
                    <h1>Site Settings</h1>
                    <p>College name, contact details, social media and the main website texts.</p>
                </div>
            </div>
            <div class="settings-head-actions">
                <a class="s-btn s-btn-ghost" href="../" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View website</a>
                <button type="submit" form="settingsForm" name="submit" class="s-btn s-btn-primary"><i class="fas fa-floppy-disk"></i> Save changes</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="s-alert s-alert-<?php echo $message_type; ?>" role="status">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
        <?php endif; ?>

        <div class="settings-layout">
            <nav class="settings-nav" aria-label="Settings sections">
                <?php foreach ($sections as $id => [$icon, $label]): ?>
                <a href="#<?php echo $id; ?>"><i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($label); ?><?php if ($id === 'social-media'): ?> <span class="nav-count" title="Pages shown on the website"><?php echo $social_count; ?>/<?php echo count(socialNetworks()); ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>

            <form method="POST" enctype="multipart/form-data" id="settingsForm">
                <!-- General -->
                <section class="s-card" id="general">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-circle-info"></i></div>
                        <div><h2>General</h2><p>The college's name and its official web address.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-grid">
                            <div class="s-field">
                                <label class="s-label" for="site_name">College name *</label>
                                <input type="text" id="site_name" name="site_name" required value="<?php echo htmlspecialchars(getSetting('site_name', 'Fusion College of Sciences')); ?>">
                                <small class="s-help">Used in page titles, the header and emails.</small>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="official_site_url">Official website address</label>
                                <input type="url" id="official_site_url" name="official_site_url" value="<?php echo htmlspecialchars(getSetting('official_site_url', '')); ?>" placeholder="https://www.yourcollege.edu.pk">
                                <small class="s-help">Starts with https://. Used for search engines, link previews and the sitemap. Leave empty until the domain is confirmed.</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Details: one place for every public contact detail, each confirmed separately -->
                <section class="s-card" id="contact-details">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-address-book"></i></div>
                        <div><h2>Contact Details</h2><p>Shown in the header, footer, contact page and emails. A detail appears on the website only while its switch is on.</p></div>
                    </div>
                    <div class="s-card-body">
                        <?php $pending_contacts = unconfirmedContactFields(); ?>
                        <?php if ($pending_contacts): ?>
                        <div class="s-warning"><i class="fas fa-triangle-exclamation"></i><div>Saved but hidden on the website: <strong><?php echo htmlspecialchars(implode(', ', $pending_contacts)); ?></strong></div></div>
                        <?php endif; ?>
                        <?php
                        $contact_inputs = [
                            'site_email' => ['email', 'e.g. info@yourcollege.edu.pk', 'Also the reply address on emails the website sends.'],
                            'site_phone' => ['text', 'e.g. 0301-1234567', 'Several numbers: separate them with commas. Visitors can tap to call.'],
                            'site_address' => ['textarea', "Street / area\nShakargarh, Punjab, Pakistan", 'One part per line.'],
                            'office_hours' => ['text', 'e.g. Mon - Sat: 8:00 AM - 2:00 PM, Sunday closed', 'Short version, shown in the header.'],
                        ];
                        ?>
                        <div class="s-grid contact-grid">
                        <?php foreach ($contact_inputs as $key => [$input_type, $placeholder, $help]):
                            $value = (string) getSetting($key, '');
                            $state = $contact_state($key);
                        ?>
                            <div class="contact-item">
                                <div class="s-field">
                                    <label class="s-label" for="field_<?php echo $key; ?>"><?php echo htmlspecialchars(contactFields()[$key]); ?> <span class="s-badge is-<?php echo $state; ?>" data-badge="<?php echo $key; ?>"><?php echo $state_labels[$state]; ?></span></label>
                                    <?php if ($input_type === 'textarea'): ?>
                                    <textarea id="field_<?php echo $key; ?>" name="<?php echo $key; ?>" rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>"><?php echo htmlspecialchars($value); ?></textarea>
                                    <?php else: ?>
                                    <input type="<?php echo $input_type; ?>" id="field_<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>">
                                    <?php endif; ?>
                                    <small class="s-help"><?php echo htmlspecialchars($help); ?></small>
                                </div>
                                <?php if ($key === 'office_hours'): // the day-by-day list shares the office-hours switch ?>
                                <div class="s-field">
                                    <label class="s-label" for="field_contact_hours">Opening hours by day</label>
                                    <textarea id="field_contact_hours" name="contact_hours" rows="3" placeholder="Monday - Saturday | 8:00 AM - 2:00 PM&#10;Sunday | Closed"><?php echo htmlspecialchars((string) getSetting('contact_hours', '')); ?></textarea>
                                    <small class="s-help">For the footer and contact page. One line each: Day | Time. Write "Closed" for days off.</small>
                                </div>
                                <?php endif; ?>
                                <label class="s-switch">
                                    <input type="hidden" name="verified_<?php echo $key; ?>" value="0">
                                    <input type="checkbox" name="verified_<?php echo $key; ?>" value="1" data-confirm="<?php echo $key; ?>"<?php echo contactConfirmed($key) ? ' checked' : ''; ?>>
                                    <span class="track"></span> Confirmed by the college: show on the website
                                </label>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Social Media -->
                <section class="s-card" id="social-media">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-share-nodes"></i></div>
                        <div><h2>Social Media</h2><p>Every page with a link and its switch on gets an icon in the website's header and footer. Empty ones are left out.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="social-grid">
                        <?php foreach (socialNetworks() as $network => $info):
                            $key = $info['key'];
                            $value = trim((string) getSetting($key, ''));
                            if ($value !== '' && $value[0] === '#') { $value = ''; }   // an old "#" placeholder counts as empty
                            $state = $contact_state($key);
                        ?>
                            <div class="social-card<?php echo $state === 'confirmed' ? ' is-on' : ''; ?>" data-social="<?php echo $key; ?>">
                                <div class="social-top">
                                    <span class="social-logo" style="background: <?php echo $info['color']; ?>;"><i class="fab <?php echo $info['icon']; ?>"></i></span>
                                    <div>
                                        <label class="social-name" for="field_<?php echo $key; ?>"><?php echo htmlspecialchars($info['label']); ?></label>
                                        <span class="s-badge is-<?php echo $state; ?>" data-badge="<?php echo $key; ?>"><?php echo $state_labels[$state]; ?></span>
                                    </div>
                                    <?php if ($value !== ''): ?>
                                    <a class="social-open" href="<?php echo htmlspecialchars($value); ?>" target="_blank" rel="noopener" title="Open the <?php echo htmlspecialchars($info['label']); ?> page"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                    <?php endif; ?>
                                </div>
                                <input type="text" id="field_<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>" placeholder="<?php echo htmlspecialchars($info['example']); ?>">
                                <label class="s-switch">
                                    <input type="hidden" name="verified_<?php echo $key; ?>" value="0">
                                    <input type="checkbox" name="verified_<?php echo $key; ?>" value="1" data-confirm="<?php echo $key; ?>"<?php echo contactConfirmed($key) && $value !== '' ? ' checked' : ''; ?>>
                                    <span class="track"></span> Show on the website
                                </label>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div class="s-note"><i class="fas fa-lightbulb"></i><div>Paste the full address of the page, copied from the browser. For WhatsApp a phone number such as 0301-1234567 is enough: it becomes a chat link.</div></div>
                    </div>
                </section>

                <!-- Home Page -->
                <section class="s-card" id="home-page">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-house"></i></div>
                        <div><h2>Home Page</h2><p>Heading, text and button of the top banner.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-note"><i class="fas fa-circle-info"></i><div>Each slide's own heading and text are edited in <a href="manage_hero_slides.php">Home Slider</a>. These texts fill in when a slide has none, or when no slides are active.</div></div>
                        <div class="s-grid">
                            <div class="s-field span-2">
                                <label class="s-label" for="hero_title">Heading</label>
                                <input type="text" id="hero_title" name="hero_title" value="<?php echo htmlspecialchars(getSetting('hero_title', 'Welcome to Fusion College of Sciences, Shakargarh')); ?>">
                            </div>
                            <div class="s-field span-2">
                                <label class="s-label" for="hero_description">Text below the heading</label>
                                <textarea id="hero_description" name="hero_description" rows="3"><?php echo htmlspecialchars(getSetting('hero_description', 'Intermediate science programs and entry test preparation in Shakargarh.')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="hero_button_text">Button text</label>
                                <input type="text" id="hero_button_text" name="hero_button_text" value="<?php echo htmlspecialchars(getSetting('hero_button_text', 'Apply Now')); ?>">
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="hero_button_link">Button link</label>
                                <input type="text" id="hero_button_link" name="hero_button_link" value="<?php echo htmlspecialchars(getSetting('hero_button_link', 'admission')); ?>" placeholder="e.g. admission or courses">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Statistics -->
                <section class="s-card" id="statistics">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-chart-simple"></i></div>
                        <div><h2>Statistics</h2><p>The figures on the home page. Leave a box empty to hide that figure or to count it automatically.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-grid">
                            <div class="s-field">
                                <label class="s-label" for="stats_years">Years of excellence</label>
                                <input type="number" id="stats_years" name="stats_years" min="0" value="<?php echo htmlspecialchars(getSetting('stats_years', '')); ?>" placeholder="Empty = hidden">
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="stats_students">Students</label>
                                <input type="number" id="stats_students" name="stats_students" min="0" value="<?php echo htmlspecialchars(getSetting('stats_students', '')); ?>" placeholder="Empty = hidden">
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="stats_teachers">Faculty members</label>
                                <input type="number" id="stats_teachers" name="stats_teachers" min="0" value="<?php echo htmlspecialchars(getSetting('stats_teachers', '')); ?>" placeholder="Empty = counted from Faculty">
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="stats_courses">Programs</label>
                                <input type="number" id="stats_courses" name="stats_courses" min="0" value="<?php echo htmlspecialchars(getSetting('stats_courses', '')); ?>" placeholder="Empty = counted from Courses">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- About Page -->
                <section class="s-card" id="about-page">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-building-columns"></i></div>
                        <div><h2>About Page</h2><p>The college's story, mission and vision.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-grid">
                            <div class="s-field span-2">
                                <label class="s-label" for="about_description">Our story: first paragraph</label>
                                <textarea id="about_description" name="about_description" rows="4"><?php echo htmlspecialchars(getSetting('about_description', 'Fusion College of Sciences is committed to academic excellence, scientific thinking and character building.')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="about_paragraph_2">Our story: second paragraph</label>
                                <textarea id="about_paragraph_2" name="about_paragraph_2" rows="6"><?php echo htmlspecialchars(getSetting('about_paragraph_2')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="about_paragraph_3">Our story: third paragraph</label>
                                <textarea id="about_paragraph_3" name="about_paragraph_3" rows="6"><?php echo htmlspecialchars(getSetting('about_paragraph_3')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="mission_statement">Mission</label>
                                <textarea id="mission_statement" name="mission_statement" rows="4"><?php echo htmlspecialchars(getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="vision_statement">Vision</label>
                                <textarea id="vision_statement" name="vision_statement" rows="4"><?php echo htmlspecialchars(getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Principal's Message (About Page) -->
                <section class="s-card" id="principal">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-user-tie"></i></div>
                        <div><h2>Principal's Message</h2><p>A short message with photo for the About page.</p></div>
                    </div>
                    <div class="s-card-body">
                        <?php if (!empty(getLeadershipMessages())): ?>
                        <div class="s-note"><i class="fas fa-circle-info"></i><div>The website currently shows the messages from <a href="manage_leadership.php">Leadership</a>. This message is only used when Leadership has no active entries.</div></div>
                        <?php endif; ?>
                        <div class="s-field">
                            <label class="s-label" for="principal_photo_upload">Photo</label>
                            <?php $principal_photo = getSetting('principal_photo'); ?>
                            <div class="principal-photo">
                                <?php if (!empty($principal_photo)): ?>
                                <img src="../<?php echo htmlspecialchars($principal_photo); ?>" alt="Principal's photo">
                                <?php else: ?>
                                <span class="placeholder"><i class="fas fa-user"></i></span>
                                <?php endif; ?>
                                <div>
                                    <input type="file" id="principal_photo_upload" name="principal_photo_upload" accept="image/jpeg,image/png,image/webp">
                                    <?php echo uploadSizeHint('600 × 600 px', 'square, face in the middle'); ?>
                                    <small class="s-help">JPG, PNG or WebP. Compressed automatically on upload.</small>
                                </div>
                            </div>
                        </div>
                        <div class="s-field">
                            <label class="s-label" for="principal_message_quote">Opening quote (shown in italics)</label>
                            <textarea id="principal_message_quote" name="principal_message_quote" rows="2"><?php echo htmlspecialchars(getSetting('principal_message_quote')); ?></textarea>
                        </div>
                        <div class="s-grid">
                            <div class="s-field">
                                <label class="s-label" for="principal_message_para_1">Paragraph 1</label>
                                <textarea id="principal_message_para_1" name="principal_message_para_1" rows="4"><?php echo htmlspecialchars(getSetting('principal_message_para_1')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="principal_message_para_2">Paragraph 2</label>
                                <textarea id="principal_message_para_2" name="principal_message_para_2" rows="4"><?php echo htmlspecialchars(getSetting('principal_message_para_2')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Assessment (About Page) -->
                <section class="s-card" id="assessment">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div><h2>Our Approach to Assessment</h2><p>Two paragraphs on the About page. Left empty, the standard text (shown in grey) is used.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-grid">
                            <div class="s-field">
                                <label class="s-label" for="assessment_paragraph_1">Paragraph 1</label>
                                <textarea id="assessment_paragraph_1" name="assessment_paragraph_1" rows="5" placeholder="<?php echo htmlspecialchars(content('assessment_paragraph_1')); ?>"><?php echo htmlspecialchars(getSetting('assessment_paragraph_1')); ?></textarea>
                            </div>
                            <div class="s-field">
                                <label class="s-label" for="assessment_paragraph_2">Paragraph 2</label>
                                <textarea id="assessment_paragraph_2" name="assessment_paragraph_2" rows="5" placeholder="<?php echo htmlspecialchars(content('assessment_paragraph_2')); ?>"><?php echo htmlspecialchars(getSetting('assessment_paragraph_2')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Footer -->
                <section class="s-card" id="footer">
                    <div class="s-card-head">
                        <div class="s-icon"><i class="fas fa-table-columns"></i></div>
                        <div><h2>Footer</h2><p>The short introduction and copyright line at the bottom of every page.</p></div>
                    </div>
                    <div class="s-card-body">
                        <div class="s-field">
                            <label class="s-label" for="footer_about_text">About the college</label>
                            <textarea id="footer_about_text" name="footer_about_text" rows="3"><?php echo htmlspecialchars(getSetting('footer_about_text', 'Fusion College of Sciences, Shakargarh prepares students for higher education and professional careers through quality science education.')); ?></textarea>
                        </div>
                        <div class="s-field">
                            <label class="s-label" for="copyright_text">Copyright line</label>
                            <input type="text" id="copyright_text" name="copyright_text" value="<?php echo htmlspecialchars(getSetting('copyright_text', 'Fusion College of Sciences, Shakargarh. All Rights Reserved.')); ?>">
                        </div>
                    </div>
                </section>

                <div class="settings-savebar" id="saveBar">
                    <span class="state" id="saveState"><i class="fas fa-circle-check"></i> All changes saved</span>
                    <button type="submit" name="submit" class="s-btn s-btn-primary"><i class="fas fa-floppy-disk"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
<script>
(function () {
    var form = document.getElementById('settingsForm');
    var labels = { empty: 'Not set', confirmed: 'Shown on website', pending: 'Hidden' };

    // each switch and its badge agree while editing
    form.querySelectorAll('input[data-confirm]').forEach(function (box) {
        var key = box.getAttribute('data-confirm');
        var field = form.querySelector('#field_' + key);
        var card = form.querySelector('[data-social="' + key + '"]');
        var touched = false;
        function update() {
            var state = (!field || field.value.trim() === '') ? 'empty' : (box.checked ? 'confirmed' : 'pending');
            form.querySelectorAll('[data-badge="' + key + '"]').forEach(function (badge) {
                badge.className = 's-badge is-' + state;
                badge.textContent = labels[state];
            });
            if (card) card.classList.toggle('is-on', state === 'confirmed');
        }
        box.addEventListener('change', function () { touched = true; update(); });
        if (field) {
            field.addEventListener('input', function () {
                // pasting a social link usually means "show it": switch it on unless the switch was set by hand
                if (card && !touched) box.checked = field.value.trim() !== '';
                update();
            });
        }
    });

    // unsaved-changes note in the save bar
    var bar = document.getElementById('saveBar'), state = document.getElementById('saveState'), dirty = false;
    function markDirty() {
        if (dirty) return;
        dirty = true;
        bar.classList.add('is-dirty');
        state.innerHTML = '<i class="fas fa-circle-exclamation"></i> Unsaved changes';
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // highlight the section in view
    var links = document.querySelectorAll('.settings-nav a');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                links.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + entry.target.id); });
            });
        }, { rootMargin: '-15% 0px -70% 0px' });
        form.querySelectorAll('.s-card').forEach(function (card) { observer.observe(card); });
    }
})();
</script>
</body>
</html>
