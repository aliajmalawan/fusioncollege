<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$admin_id = (int) ($_SESSION['admin_id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM admin_users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$admin) {
    header('Location: logout.php');
    exit;
}

$messages = [];   // [type, text]
$form = $_POST['form'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Every change here needs the current password, so an unattended open session can't take over the account
    if (!password_verify((string) ($_POST['current_password'] ?? ''), $admin['password'])) {
        $messages[] = ['error', 'The current password is not correct. Nothing was changed.'];
    } elseif ($form === 'profile') {
        $full_name = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $taken = mysqli_prepare($conn, "SELECT COUNT(*) FROM admin_users WHERE username = ? AND id <> ?");
        mysqli_stmt_bind_param($taken, 'si', $username, $admin_id);
        mysqli_stmt_execute($taken);
        mysqli_stmt_bind_result($taken, $taken_count);
        mysqli_stmt_fetch($taken);
        mysqli_stmt_close($taken);

        if ($full_name === '' || mb_strlen($full_name) > 100) {
            $messages[] = ['error', 'Please enter your name (up to 100 characters).'];
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $messages[] = ['error', 'The username needs 3 to 50 characters: letters, numbers, dot, dash or underscore.'];
        } elseif ($taken_count > 0) {
            $messages[] = ['error', 'That username is already used by another admin account.'];
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = ['error', 'Please enter a valid email address, or leave it empty.'];
        } else {
            $up = mysqli_prepare($conn, "UPDATE admin_users SET full_name = ?, username = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($up, 'sssi', $full_name, $username, $email, $admin_id);
            if (mysqli_stmt_execute($up)) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_name'] = $full_name;
                $messages[] = ['success', 'Profile saved.' . ($username !== $admin['username'] ? ' Sign in with the new username "' . $username . '" from now on.' : '')];
                $admin = array_merge($admin, ['full_name' => $full_name, 'username' => $username, 'email' => $email]);
            } else {
                $messages[] = ['error', 'The profile could not be saved. Please try again.'];
            }
        }
    } elseif ($form === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (strlen($new) < 8) {
            $messages[] = ['error', 'The new password needs at least 8 characters.'];
        } elseif ($new !== $confirm) {
            $messages[] = ['error', 'The two new passwords are not the same.'];
        } elseif (password_verify($new, $admin['password'])) {
            $messages[] = ['error', 'The new password is the same as the current one.'];
        } elseif (in_array(strtolower($new), ['password', '12345678', 'admin123', 'fusion123', 'qwerty123', 'admin@123'], true)) {
            $messages[] = ['error', 'That password is too easy to guess. Please choose another one.'];
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = mysqli_prepare($conn, "UPDATE admin_users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($up, 'si', $hash, $admin_id);
            if (mysqli_stmt_execute($up)) {
                session_regenerate_id(true);
                $messages[] = ['success', 'Password changed. Use the new password the next time you sign in.'];
                $admin['password'] = $hash;
            } else {
                $messages[] = ['error', 'The password could not be changed. Please try again.'];
            }
        }
    }
}

$still_default = adminPasswordIsSetupDefault($admin['password']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <title>My Account - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .account-page { max-width: 1000px; margin: 0 auto; padding: 28px 28px 40px; }
        .account-head h1 { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--navy); margin: 0; }
        .account-head p { margin: 6px 0 0; color: var(--text-muted); font-size: 15px; }
        .account-head { margin-bottom: 24px; }
        .a-alert { display: flex; gap: 12px; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-size: 14.5px; border: 1px solid; }
        .a-alert i { margin-top: 2px; }
        .a-alert-success { background: #ECFDF3; border-color: #ABEFC6; color: #067647; }
        .a-alert-error { background: #FEF3F2; border-color: #FECDCA; color: #B42318; }
        .a-alert-warning { background: #FFFAEB; border-color: #FEDF89; color: #93370D; }
        .a-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        .account-page section.a-card { padding: 0; }   /* the website's own section spacing does not apply here */
        .a-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .a-card-head { display: flex; gap: 14px; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .a-icon { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; background: var(--royal-soft); color: var(--royal); font-size: 17px; flex-shrink: 0; }
        .a-card-head h2 { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--navy); margin: 0; }
        .a-card-head p { margin: 2px 0 0; color: var(--text-muted); font-size: 13.5px; }
        .a-card form { padding: 22px 24px 24px; }
        .a-field { margin-bottom: 16px; }
        .a-field label { display: block; font-weight: 700; font-size: 14px; color: var(--text); margin-bottom: 7px; }
        .a-field input { width: 100%; padding: 11px 14px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14.5px; font-family: var(--font-body); color: var(--text); }
        .a-field input:focus { outline: none; border-color: var(--royal); box-shadow: 0 0 0 4px rgba(20, 86, 192, .12); }
        .a-help { display: block; margin-top: 6px; font-size: 12.5px; color: var(--text-muted); }
        .a-divider { border: 0; border-top: 1px dashed var(--border); margin: 6px 0 18px; }
        .a-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 14.5px; border: 0; cursor: pointer; background: var(--royal); color: #fff; font-family: var(--font-body); }
        .a-btn:hover { background: var(--royal-dark); }
        .a-meta { display: flex; flex-wrap: wrap; gap: 8px 24px; padding: 14px 24px; background: var(--bg-alt); border-top: 1px solid var(--border); border-radius: 0 0 16px 16px; font-size: 13px; color: var(--text-muted); }
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 46px; }
        .pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border: 0; background: none; color: var(--text-muted); cursor: pointer; border-radius: 8px; }
        .pw-toggle:hover { color: var(--royal); background: var(--royal-soft); }
        .pw-meter { height: 6px; border-radius: 999px; background: #E2E8F0; margin-top: 8px; overflow: hidden; }
        .pw-meter span { display: block; height: 100%; width: 0; border-radius: 999px; transition: width .2s, background .2s; }
        @media (max-width: 860px) { .a-grid { grid-template-columns: 1fr; } .account-page { padding: 18px 16px 30px; } }
    </style>
</head>
<body style="background: var(--bg-alt);">
<div class="admin-shell">
<?php $active_page = 'account.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="account-page">
        <div class="account-head admin-topbar-left">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
            <div>
                <h1>My Account</h1>
                <p>Your sign-in details for this admin panel.</p>
            </div>
        </div>

        <?php foreach ($messages as [$type, $text]): ?>
        <div class="a-alert a-alert-<?php echo $type; ?>" role="status"><i class="fas <?php echo $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i><div><?php echo htmlspecialchars($text); ?></div></div>
        <?php endforeach; ?>
        <?php if ($still_default): ?>
        <div class="a-alert a-alert-warning"><i class="fas fa-triangle-exclamation"></i><div><strong>This account still uses the starting password.</strong> Anyone who knows it can sign in. Please choose your own password below.</div></div>
        <?php endif; ?>

        <div class="a-grid">
            <section class="a-card">
                <div class="a-card-head">
                    <div class="a-icon"><i class="fas fa-id-card"></i></div>
                    <div><h2>Profile</h2><p>Name, username and email</p></div>
                </div>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="form" value="profile">
                    <div class="a-field">
                        <label for="full_name">Your name</label>
                        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?php echo htmlspecialchars($admin['full_name']); ?>">
                    </div>
                    <div class="a-field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" maxlength="50" required pattern="[A-Za-z0-9._\-]{3,50}" value="<?php echo htmlspecialchars($admin['username']); ?>" autocomplete="username">
                        <small class="a-help">Used to sign in. Letters, numbers, dot, dash or underscore.</small>
                    </div>
                    <div class="a-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars((string) $admin['email']); ?>">
                    </div>
                    <hr class="a-divider">
                    <div class="a-field">
                        <label for="current_password_profile">Current password</label>
                        <div class="pw-wrap">
                            <input type="password" id="current_password_profile" name="current_password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="a-help">Needed to save any change.</small>
                    </div>
                    <button type="submit" class="a-btn"><i class="fas fa-floppy-disk"></i> Save profile</button>
                </form>
                <div class="a-meta">
                    <span><i class="fas fa-user-shield"></i> Role: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $admin['role']))); ?></span>
                    <span><i class="fas fa-clock"></i> Last sign-in: <?php echo $admin['last_login'] ? date('d M Y, g:i A', strtotime($admin['last_login'])) : '—'; ?></span>
                </div>
            </section>

            <section class="a-card">
                <div class="a-card-head">
                    <div class="a-icon"><i class="fas fa-key"></i></div>
                    <div><h2>Change password</h2><p>At least 8 characters</p></div>
                </div>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="form" value="password">
                    <div class="a-field">
                        <label for="current_password">Current password</label>
                        <div class="pw-wrap">
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="a-field">
                        <label for="new_password">New password</label>
                        <div class="pw-wrap">
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="pw-meter" aria-hidden="true"><span id="pwMeter"></span></div>
                        <small class="a-help" id="pwHint">Longer is stronger: mix words, numbers and symbols.</small>
                    </div>
                    <div class="a-field">
                        <label for="confirm_password">New password again</label>
                        <div class="pw-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="pw-toggle" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="a-btn"><i class="fas fa-key"></i> Change password</button>
                </form>
            </section>
        </div>
    </div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
<script>
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.previousElementSibling, show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.innerHTML = '<i class="fas ' + (show ? 'fa-eye-slash' : 'fa-eye') + '"></i>';
    });
});
(function () {
    var input = document.getElementById('new_password'), bar = document.getElementById('pwMeter'), hint = document.getElementById('pwHint');
    var confirm = document.getElementById('confirm_password');
    input.addEventListener('input', function () {
        var v = input.value, score = 0;
        if (v.length >= 8) score++;
        if (v.length >= 12) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        var levels = [['0%', '#E2E8F0', 'Longer is stronger: mix words, numbers and symbols.'], ['25%', '#DC2626', 'Weak'], ['50%', '#F59E0B', 'Fair'], ['75%', '#16A34A', 'Good'], ['100%', '#15803D', 'Strong']];
        var l = v.length === 0 ? levels[0] : levels[Math.max(1, Math.min(4, score - (v.length < 8 ? 1 : 0)))];
        bar.style.width = l[0]; bar.style.background = l[1]; hint.textContent = l[2];
    });
    confirm.addEventListener('input', function () {
        confirm.setCustomValidity(confirm.value && confirm.value !== input.value ? 'The two new passwords are not the same.' : '');
    });
})();
</script>
</body>
</html>
