<?php
/**
 * Settings Helper Functions
 * Use these functions to fetch dynamic settings from database
 */

// Global settings cache
$_settings_cache = null;

/**
 * Load all settings from database into cache
 */
function loadSettings() {
    global $conn, $_settings_cache;

    if ($_settings_cache !== null) {
        return $_settings_cache;
    }

    $_settings_cache = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $_settings_cache;
}

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    global $_settings_cache;

    if ($_settings_cache === null) {
        loadSettings();
    }

    return isset($_settings_cache[$key]) ? $_settings_cache[$key] : $default;
}

/**
 * The official website address entered in Admin > Settings (e.g. https://www.yourcollege.edu.pk),
 * without a trailing slash; '' until the college confirms its domain. Canonical links, og:url and
 * the published site URL are only output once this is set.
 */
function officialSiteUrl() {
    $url = trim((string) getSetting('official_site_url', ''));
    return preg_match('#^https?://[a-z0-9.-]+\.[a-z]{2,}(:[0-9]+)?(/[^\s]*)?$#i', $url) ? rtrim($url, '/') : '';
}

/** Address the site is being served from right now (scheme, host and folder), without a trailing slash. */
function currentBaseUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
}

/** Base for absolute links that must exist (share images, sitemap, emails): the official address when set. */
function publicBaseUrl() {
    return officialSiteUrl() ?: currentBaseUrl();
}

/**
 * Get site name
 */
function getSiteName() {
    return getSetting('site_name', 'Fusion College of Sciences');
}

/**
 * Contact details, all edited in one place: Admin > Settings > Contact Details.
 * Each has a "confirmed by the college" switch (setting verified_<key> = '1'); the public site,
 * emails and search-engine data only use confirmed values, so an unchecked phone number or
 * address is never presented as official. key => label shown in the admin panel.
 */
function contactFields() {
    $fields = [
        'site_email' => 'Email address',
        'site_phone' => 'Phone number',
        'site_address' => 'Address',
        'office_hours' => 'Office hours',
    ];
    foreach (socialNetworks() as $network) {
        $fields[$network['key']] = $network['label'];
    }
    return $fields;
}

/**
 * Social media networks, in the order their icons appear in the header and footer.
 * name => setting key, label, Font Awesome brand icon, brand colour (admin panel) and an example address.
 */
function socialNetworks() {
    return [
        'facebook'  => ['key' => 'facebook_url',  'label' => 'Facebook',    'icon' => 'fa-facebook-f',  'color' => '#1877F2', 'example' => 'https://www.facebook.com/yourpage'],
        'instagram' => ['key' => 'instagram_url', 'label' => 'Instagram',   'icon' => 'fa-instagram',   'color' => '#E1306C', 'example' => 'https://www.instagram.com/yourpage'],
        'youtube'   => ['key' => 'youtube_url',   'label' => 'YouTube',     'icon' => 'fa-youtube',     'color' => '#FF0000', 'example' => 'https://www.youtube.com/@yourchannel'],
        'tiktok'    => ['key' => 'tiktok_url',    'label' => 'TikTok',      'icon' => 'fa-tiktok',      'color' => '#111111', 'example' => 'https://www.tiktok.com/@yourpage'],
        'whatsapp'  => ['key' => 'whatsapp_url',  'label' => 'WhatsApp',    'icon' => 'fa-whatsapp',    'color' => '#25D366', 'example' => '0301-1234567 or https://wa.me/923011234567'],
        'linkedin'  => ['key' => 'linkedin_url',  'label' => 'LinkedIn',    'icon' => 'fa-linkedin-in', 'color' => '#0A66C2', 'example' => 'https://www.linkedin.com/company/yourpage'],
        'twitter'   => ['key' => 'twitter_url',   'label' => 'X (Twitter)', 'icon' => 'fa-x-twitter',   'color' => '#000000', 'example' => 'https://x.com/yourpage'],
    ];
}

/**
 * A social address as typed in the admin panel, made into a working link: "facebook.com/page" gets
 * https://, and a WhatsApp phone number (0301-1234567, +92 301 1234567) becomes a wa.me chat link.
 */
function normalizeSocialUrl($network, $value) {
    $value = trim((string) $value);
    if ($value === '' || $value[0] === '#') {
        return $value;
    }
    if ($network === 'whatsapp' && preg_match('/^[+0-9][0-9 ()-]{6,}$/', $value)) {
        $digits = preg_replace('/\D/', '', $value);
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        } elseif ($digits[0] === '0') {
            $digits = '92' . substr($digits, 1);   // Pakistani number written without the country code
        }
        return 'https://wa.me/' . $digits;
    }
    return preg_match('#^https?://#i', $value) ? $value : 'https://' . ltrim($value, '/');
}

/** True when the college has confirmed this contact detail in Admin > Settings. */
function contactConfirmed($key) {
    return getSetting('verified_' . $key, '') === '1';
}

/** A contact detail for public use: its value once confirmed, otherwise ''. */
function publicContact($key) {
    return contactConfirmed($key) ? trim((string) getSetting($key, '')) : '';
}

/** Labels of contact details that have a value but are not confirmed yet (shown as a reminder in the admin panel). */
function unconfirmedContactFields() {
    $pending = [];
    foreach (contactFields() as $key => $label) {
        if (trim((string) getSetting($key, '')) !== '' && !contactConfirmed($key)) {
            $pending[$key] = $label;
        }
    }
    return $pending;
}

/**
 * Get site email (confirmed only)
 */
function getSiteEmail() {
    return publicContact('site_email');
}

/**
 * Get site phone (confirmed only)
 */
function getSitePhone() {
    return publicContact('site_phone');
}

/**
 * Get site address. Until a full address is confirmed, the town from the college's official
 * name ("Fusion College of Sciences, Shakargarh") is shown.
 */
function getSiteAddress() {
    $address = publicContact('site_address');
    return $address !== '' ? $address : "Shakargarh\nPunjab, Pakistan";
}

/**
 * Get office hours (confirmed only)
 */
function getOfficeHours() {
    return publicContact('office_hours');
}

/**
 * Opening hours as [day, time] rows, once the office hours are confirmed. The rows come from the
 * day-by-day list (setting contact_hours, one "Day | Time" per line, Admin > Settings > Contact Details);
 * without a list, the one-line office hours become a single row. [] while nothing is confirmed.
 */
function getOpeningHours() {
    if (!contactConfirmed('office_hours')) {
        return [];
    }
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) getSetting('contact_hours', '')) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        [$day, $time] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $rows[] = [$day, $time];
    }
    if (empty($rows) && getOfficeHours() !== '') {
        $rows[] = ['', getOfficeHours()];
    }
    return $rows;
}

/**
 * Get social media URLs (confirmed only)
 */
function getSocialMedia() {
    // "#" and bare placeholders count as "not set" so no dead icons are rendered
    $clean = function ($url) {
        $url = trim((string) $url);
        return ($url === '' || $url === '#') ? '' : $url;
    };
    $links = [];
    foreach (socialNetworks() as $name => $network) {
        $links[$name] = $clean(publicContact($network['key']));
    }
    return $links;
}

/**
 * True when a confirmed contact email is configured (empty or *@example.com means "not set yet")
 */
function hasSiteEmail() {
    $email = trim(getSiteEmail());
    return $email !== '' && !preg_match('/@example\.com$/i', $email);
}

/**
 * Get hero section content
 */
function getHeroContent() {
    return [
        'title' => getSetting('hero_title', 'Welcome to Fusion College of Sciences, Shakargarh'),
        'description' => getSetting('hero_description', 'At Fusion College of Sciences, Shakargarh, students prepare for their Intermediate board examinations, university entry tests and the careers that follow.'),
        'button_text' => getSetting('hero_button_text', 'Apply Now'),
        'button_link' => getSetting('hero_button_link', 'admission'),
        'image' => content('hero_image')
    ];
}

/**
 * Get statistics
 */
function getStatistics() {
    global $conn;

    $count = function ($table) use ($conn) {
        $result = mysqli_query($conn, "SELECT COUNT(DISTINCT id) AS c FROM $table WHERE status = 'active'");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return $row ? (int) $row['c'] : 0;
    };

    // Faculty and programmes are counted from the real records unless an override is entered in Settings.
    // Students and years have no table to count from, so they only show once real values are entered.
    $teachers = getSetting('stats_teachers', '');
    $courses = getSetting('stats_courses', '');

    return [
        'students' => getSetting('stats_students', ''),
        'teachers' => $teachers !== '' ? $teachers : ($count('faculty') ?: ''),
        'courses' => $courses !== '' ? $courses : ($count('courses') ?: ''),
        'years' => getSetting('stats_years', '')
    ];
}

/**
 * Get about page content
 */
function getAboutContent() {
    return [
        'description' => getSetting('about_description', 'Fusion College of Sciences is a science college in Shakargarh committed to academic excellence and character building.'),
        'mission' => getSetting('mission_statement', 'To provide quality science education that prepares every student for higher studies and a successful career.'),
        'vision' => getSetting('vision_statement', 'To be the leading college of sciences in the region, producing capable doctors, engineers, scientists and responsible citizens.')
    ];
}

/**
 * Get footer content
 */
function getFooterContent() {
    return [
        'about_text' => getSetting('footer_about_text', 'Fusion College of Sciences, Shakargarh prepares students for higher education and professional careers through quality science education.'),
        'copyright' => getSetting('copyright_text', 'Fusion College of Sciences, Shakargarh. All rights reserved.')
    ];
}

/**
 * Get leadership messages
 */
function getLeadershipMessages() {
    global $conn;
    static $leaders = null;   // pages ask twice (check + render); one query is enough
    if ($leaders !== null) {
        return $leaders;
    }

    $query = "SELECT DISTINCT * FROM leadership WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $leaders = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $leaders[] = $row;
        }
    }

    return $leaders;
}

/**
 * Get hero carousel slides
 */
function getHeroCarouselSlides() {
    global $conn;

    $query = "SELECT DISTINCT * FROM hero_carousel WHERE status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);

    $slides = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $slides[] = $row;
        }
    }

    return $slides;
}

/**
 * True when a public page has something published, so the menus only link to pages
 * with content (an empty Faculty or Gallery page makes a new site look unfinished).
 * Pages without an entry here (Courses, Admissions, Alumni, Contact...) always show.
 */
function pageHasContent($page) {
    global $conn;
    static $cache = [];
    $tables = [
        'faculty' => ['faculty'],
        'campuses' => ['campuses'],
        'gallery' => ['gallery', 'gallery_videos'],
        'events' => ['events', 'news'],
        'examination' => ['exam_datesheets', 'board_results'],
        'downloads' => ['downloads'],
    ];
    if (!isset($tables[$page])) {
        return true;
    }
    if (!isset($cache[$page])) {
        $cache[$page] = false;
        foreach ($tables[$page] as $table) {
            $result = mysqli_query($conn, "SELECT 1 FROM `$table` WHERE status = 'active' LIMIT 1");
            if ($result && mysqli_num_rows($result) > 0) {
                $cache[$page] = true;
                break;
            }
        }
    }
    return $cache[$page];
}

/**
 * Programme groups on the Courses page, in display order: stored key => [admin label, icon].
 * Each course's `level` column holds one of these keys; the headings are edited in Page Content.
 */
function courseStages() {
    return [
        'intermediate' => ['Intermediate (F.Sc / ICS)', 'fa-flask'],
        'graduate' => ['Graduate Programs (ADP / BS)', 'fa-building-columns'],
        'test_prep' => ['Entry Test Preparation', 'fa-pen-ruler'],
        'short_courses' => ['Short Courses & Skills', 'fa-laptop-code'],
    ];
}

/**
 * Get active feature_cards rows for a given section (why_choose_us,
 * digital_features, learner_attributes, assessment_features, core_values,
 * faculty_highlights, admission_requirements, admission_process)
 */
function getFeatureCards($section) {
    global $conn;

    $section_escaped = mysqli_real_escape_string($conn, $section);
    $query = "SELECT DISTINCT * FROM feature_cards WHERE section = '$section_escaped' AND status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);

    $cards = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cards[] = $row;
        }
    }

    return $cards;
}

/**
 * Render a list of feature_cards as standard .card / .card-icon markup
 * (used by "Why Choose Us", "Learner Attributes", "Core Values",
 * "Why Our Faculty Stands Out"). Pass $centered = true to also center
 * the card text (used by grid-4 sections like Learner Attributes).
 */
function renderFeatureCardGrid($section, $centered = false) {
    $cards = getFeatureCards($section);
    $class = 'card reveal' . ($centered ? ' text-center' : '');
    $icon_style = $centered ? ' style="margin: 0 auto 22px;"' : '';

    foreach ($cards as $card) {
        echo '<div class="' . $class . '">';
        echo '<div class="card-icon"' . $icon_style . '><i class="' . htmlspecialchars($card['icon']) . '"></i></div>';
        echo '<h3>' . htmlspecialchars($card['title']) . '</h3>';
        echo '<p>' . htmlspecialchars($card['description']) . '</p>';
        echo '</div>';
    }
}

/**
 * Render one News / Event card (image, date, category, title, excerpt, Read More).
 * $type is 'news' or 'event'; $row is the matching DB row. Read More points at the
 * entry's anchor on events.php (there is no separate detail page).
 */
function renderUpdateCard($type, $row) {
    $is_event = ($type === 'event');
    $date = $is_event ? $row['event_date'] : $row['created_at'];
    $body = $is_event ? ($row['description'] ?? '') : ($row['content'] ?? '');
    $plain = trim(strip_tags($body));
    $excerpt = mb_strlen($plain) > 120 ? rtrim(mb_substr($plain, 0, 120)) . '…' : $plain;
    $anchor = 'events#' . ($is_event ? 'event-' : 'news-') . intval($row['id']);

    echo '<article class="card update-card reveal">';
    echo '<a href="' . htmlspecialchars($anchor) . '" class="update-media" tabindex="-1" aria-hidden="true">';
    if (!empty($row['image'])) {
        echo '<img src="' . htmlspecialchars($row['image']) . '" alt="" loading="lazy">';
    } else {
        echo '<span class="update-placeholder"><i class="fas ' . ($is_event ? 'fa-calendar-alt' : 'fa-newspaper') . '"></i></span>';
    }
    echo '</a>';
    echo '<div class="update-body">';
    echo '<div class="update-meta">';
    echo '<span class="update-category ' . ($is_event ? 'is-event' : 'is-news') . '">' . ($is_event ? 'Event' : 'News') . '</span>';
    echo '<span class="update-date"><i class="far fa-calendar"></i> ' . date('M d, Y', strtotime($date)) . '</span>';
    echo '</div>';
    echo '<h3><a href="' . htmlspecialchars($anchor) . '">' . htmlspecialchars($row['title']) . '</a></h3>';
    if ($excerpt !== '') {
        echo '<p>' . htmlspecialchars($excerpt) . '</p>';
    }
    echo '<a href="' . htmlspecialchars($anchor) . '" class="level-link">Read More <i class="fas fa-arrow-right"></i></a>';
    echo '</div>';
    echo '</article>';
}

/* ------------------------------------------------------------------
 * Editable page content (AdminCP -> Page Content)
 * The registry in content_fields.php lists every field with its default;
 * saved values live in the settings table.
 * ------------------------------------------------------------------ */

/** All content groups from the registry. */
function contentRegistry() {
    static $registry = null;
    if ($registry === null) {
        $registry = require __DIR__ . '/content_fields.php';
    }
    return $registry;
}

/** Definition (label, type, default...) of one content key, or null. */
function contentDef($key) {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (contentRegistry() as $group) {
            foreach ($group['fields'] as $field) {
                $map[$field['key']] = $field;
            }
        }
    }
    return $map[$key] ?? null;
}

/**
 * Value of an editable content field: the saved setting when there is one, otherwise
 * the registry default. {site_name} is replaced with the current college name.
 * A saved empty text hides that line; an empty image falls back to the default image.
 */
function content($key) {
    $def = contentDef($key);
    $all = loadSettings();
    $value = array_key_exists($key, $all) ? (string) $all[$key] : (string) ($def['default'] ?? '');
    if ($def && $def['type'] === 'image' && trim($value) === '') {
        $value = (string) $def['default'];
    }
    return str_replace('{site_name}', getSiteName(), $value);
}

function renderPageHero($image, $eyebrow, $title, $subtitle = '', $crumb = '', $compact = false, $icon = '') {
    echo '<section class="page-hero' . ($compact ? ' is-compact' : '') . '">';
    // The photo in a size that fits the screen, as WebP where the browser takes it (see .page-hero-bg in style.css)
    echo '<div class="page-hero-bg" style="' . responsiveBackgroundStyle($image) . '"></div>';
    echo '<div class="container"><div class="page-hero-content">';
    if ($crumb !== '') {
        // Same trail for search engines, so results can show "Home › About Us"
        $base = publicBaseUrl();
        echo '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $base . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $crumb, 'item' => $base . '/' . basename($_SERVER['SCRIPT_NAME'], '.php')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        echo '<nav class="breadcrumb" aria-label="Breadcrumb"><a href="./"><i class="fas fa-house" aria-hidden="true"></i> Home</a>'
            . '<i class="fas fa-chevron-right" aria-hidden="true"></i><span aria-current="page">' . htmlspecialchars($crumb) . '</span></nav>';
    }
    if ($eyebrow !== '') {
        echo '<span class="eyebrow on-dark">' . ($icon !== '' ? '<i class="fas ' . htmlspecialchars($icon) . '"></i> ' : '') . htmlspecialchars($eyebrow) . '</span>';
    }
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    echo '<span class="page-hero-line" aria-hidden="true"></span>';
    if ($subtitle !== '') {
        echo '<p>' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '</div></div></section>';
}

/**
 * Inner-page hero (photo, breadcrumb, small label, title, subtitle) driven by content().
 * $compact = the slightly shorter variant used on Examination, Alumni and Downloads.
 */
function renderPageBanner($slug, $compact = false, $icon = '') {
    renderPageHero(
        content("banner_{$slug}_image"),
        content("banner_{$slug}_eyebrow"),
        content("banner_{$slug}_title"),
        content("banner_{$slug}_subtitle"),
        content("seo_{$slug}_title"),
        $compact,
        $icon
    );
}

/** Insert-or-update one setting (settings has no unique key, so look the key up first). */
function saveSetting($key, $value) {
    global $conn, $_settings_cache;
    $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($check, 's', $key);
    mysqli_stmt_execute($check);
    mysqli_stmt_bind_result($check, $exists);
    mysqli_stmt_fetch($check);
    mysqli_stmt_close($check);

    if ($exists > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $value, $key);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($ok && is_array($_settings_cache)) {
        $_settings_cache[$key] = $value;
    }
    return $ok;
}

/** Remove a saved setting so its registry default applies again. */
function deleteSetting($key) {
    global $conn, $_settings_cache;
    $stmt = mysqli_prepare($conn, "DELETE FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, 's', $key);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (is_array($_settings_cache)) {
        unset($_settings_cache[$key]);
    }
    return $ok;
}

/**
 * Next free numeric id for a table whose id column has no auto-numbering (the imported tables lost it).
 * Giving every new row its own id keeps editing and deleting that one row reliable.
 */
function nextRowId($table) {
    global $conn;
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return 1;
    }
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `$table`"));
    return max(1, (int) ($row['next_id'] ?? 1));
}

/**
 * Friendly placeholder for a list that has nothing in it yet (icon, short title, one line of help).
 * $level: heading level of the title; 2 where the placeholder is the first thing under the page's H1.
 */
function renderEmptyState($icon, $title, $text = '', $level = 3) {
    $tag = 'h' . max(2, min(6, (int) $level));
    echo '<div class="empty-state">';
    echo '<span class="empty-state-icon"><i class="fas ' . htmlspecialchars($icon) . '" aria-hidden="true"></i></span>';
    echo '<' . $tag . ' class="empty-state-title">' . htmlspecialchars($title) . '</' . $tag . '>';
    if ($text !== '') {
        echo '<p>' . htmlspecialchars($text) . '</p>';
    }
    echo '</div>';
}

/**
 * Icons the admin panel offers for feature cards, campuses and fee items. The public pages load a
 * small icon font with only the icons the site uses (tools/build-icons.py), and this list is part of
 * it, so every icon chosen here always shows. Add a name here and rebuild the icons to offer more.
 */
function iconChoices() {
    return [
        'fa-graduation-cap' => 'Graduation cap', 'fa-user-graduate' => 'Graduate', 'fa-school' => 'School',
        'fa-building-columns' => 'College building', 'fa-book' => 'Book', 'fa-book-open' => 'Open book',
        'fa-book-open-reader' => 'Reader', 'fa-chalkboard' => 'Chalkboard', 'fa-chalkboard-user' => 'Teacher at board',
        'fa-person-chalkboard' => 'Lecture', 'fa-pen-ruler' => 'Pen and ruler', 'fa-pencil' => 'Pencil',
        'fa-flask' => 'Flask (science)', 'fa-microscope' => 'Microscope', 'fa-atom' => 'Atom (physics)',
        'fa-dna' => 'DNA (biology)', 'fa-stethoscope' => 'Stethoscope (medical)', 'fa-user-doctor' => 'Doctor',
        'fa-calculator' => 'Calculator (maths)', 'fa-square-root-variable' => 'Formula', 'fa-laptop-code' => 'Laptop (computer)',
        'fa-computer' => 'Computer', 'fa-microchip' => 'Microchip', 'fa-gears' => 'Gears (engineering)',
        'fa-briefcase' => 'Briefcase (commerce)', 'fa-chart-line' => 'Chart', 'fa-language' => 'Language',
        'fa-feather-pointed' => 'Feather (arts)', 'fa-palette' => 'Palette', 'fa-brain' => 'Brain', 'fa-lightbulb' => 'Light bulb',
        'fa-seedling' => 'Seedling (growth)', 'fa-compass' => 'Compass', 'fa-globe' => 'Globe', 'fa-trophy' => 'Trophy',
        'fa-award' => 'Award', 'fa-medal' => 'Medal', 'fa-star' => 'Star', 'fa-certificate' => 'Certificate',
        'fa-users' => 'Group', 'fa-people-group' => 'People', 'fa-user-tie' => 'Professional', 'fa-handshake' => 'Handshake',
        'fa-hands-helping' => 'Helping hands', 'fa-heart' => 'Heart', 'fa-comments' => 'Conversation',
        'fa-shield-halved' => 'Shield (safety)', 'fa-video' => 'Camera (CCTV)', 'fa-bus' => 'Bus (transport)',
        'fa-wifi' => 'Wi-Fi', 'fa-mosque' => 'Mosque', 'fa-futbol' => 'Sports', 'fa-dumbbell' => 'Fitness',
        'fa-utensils' => 'Cafeteria', 'fa-house-chimney' => 'Hostel', 'fa-clock' => 'Clock', 'fa-calendar-check' => 'Calendar',
        'fa-clipboard-check' => 'Checklist', 'fa-file-signature' => 'Form', 'fa-id-card' => 'ID card',
        'fa-camera' => 'Photo', 'fa-file-lines' => 'Document', 'fa-money-bill-wave' => 'Fee (money)',
        'fa-wallet' => 'Wallet', 'fa-receipt' => 'Receipt', 'fa-hand-holding-dollar' => 'Scholarship',
        'fa-percent' => 'Discount', 'fa-phone' => 'Phone', 'fa-location-dot' => 'Location',
    ];
}

/**
 * <option>s for an icon picker. $prefix 'fas ' stores "fas fa-star" (feature cards), '' stores "fa-star".
 * A saved icon that is not in the list stays selectable so editing a card never changes it by accident.
 */
function iconOptions($current, $prefix = '') {
    $current = trim((string) $current);
    $html = '';
    $found = false;
    foreach (iconChoices() as $name => $label) {
        $value = $prefix . $name;
        $selected = $value === $current;
        $found = $found || $selected;
        $html .= '<option value="' . htmlspecialchars($value) . '"' . ($selected ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
    }
    if ($current !== '' && !$found) {
        $html = '<option value="' . htmlspecialchars($current) . '" selected>' . htmlspecialchars($current) . ' (current)</option>' . $html;
    }
    return $html;
}

/**
 * The 11-character video ID from any usual YouTube link (watch?v=, youtu.be/, shorts/, embed/, live/),
 * or '' when the text isn't a YouTube link. A bare ID is accepted too.
 */
function youtubeId($url) {
    $url = trim((string) $url);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Check a Facebook video link (a video post, Watch link, reel, share link or fb.watch short link) and
 * return it as a clean https URL for Facebook's video player, or '' when it is not a Facebook video.
 * The video must be public on Facebook, otherwise the player shows "video unavailable".
 */
function facebookVideoUrl($url) {
    $url = trim((string) $url);
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);

    if (preg_match('~^(www\.|m\.|web\.)?fb\.watch$~', $host) && preg_match('~^/[A-Za-z0-9_-]+/?$~', $path)) {
        return 'https://fb.watch' . rtrim($path, '/') . '/';
    }
    if (!preg_match('~^((www|m|web|mbasic)\.)?facebook\.com$~', $host)) {
        return '';
    }
    $is_video = preg_match('~/(videos|reel|watch|share/v|share/r)(/|$)~i', $path)
        || ($path === '/watch' || $path === '/watch/') && preg_match('~(^|&)v=\d+~', $query)
        || $path === '/video.php' && preg_match('~(^|&)v=\d+~', $query);
    if (!$is_video) {
        return '';
    }
    parse_str($query, $params);
    $keep = isset($params['v']) && ctype_digit((string) $params['v']) ? '?v=' . $params['v'] : '';
    return 'https://www.facebook.com' . $path . $keep;
}
