<?php
$site_name = getSiteName();
$seo_location = content('seo_title_suffix');
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
// Home: "Fusion College of Sciences, Shakargarh | F.Sc, ICS ..."; inner pages: "About Us | Fusion College of Sciences, Shakargarh"
$site_full_name = getSiteFullName();
$full_title = $is_home ? $site_full_name . ' | ' . $seo_location : ((!empty($page_title) ? $page_title . ' | ' : '') . $site_full_name);
$meta_description = $meta_description ?? content('seo_home_description');
// Absolute links: share images and structured data use publicBaseUrl(); canonical and og:url are only
// output once the official website address is set in Admin > Settings (officialSiteUrl()), so the site
// never declares a temporary or guessed domain as the real one.
$site_base_url = publicBaseUrl();
$official_url = officialSiteUrl();
$page_slug = basename($_SERVER['SCRIPT_NAME'], '.php');
// A page with nothing published yet is kept out of search results (it is also left out of the sitemap and menus)
$noindex = !empty($noindex) || !pageHasContent($page_slug);
// One clean address per page (no .php, no query string): https://site/ for home, https://site/about for the rest,
// so /about.php, /about/ and ?utm=... all point search engines to the same URL.
$current_url = $official_url !== '' ? $official_url . '/' . ($page_slug === 'index' ? '' : $page_slug) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B1F4D">
    <?php if (basename($_SERVER['SCRIPT_NAME']) === '404.php'): ?><base href="<?php echo htmlspecialchars(currentBaseUrl()); ?>/"><?php // the 404 page can be shown for any nested address, so resolve its links from the site root ?><?php endif; ?>
    <title><?php echo htmlspecialchars($full_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="Fusion College of Sciences, Fusion College Shakargarh, college in Shakargarh, F.Sc Pre-Medical Shakargarh, F.Sc Pre-Engineering, ICS, MDCAT preparation, ECAT preparation, Narowal">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
    <?php if ($current_url !== ''): ?><link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>"><?php endif; ?>

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_full_name); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($full_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php if ($current_url !== ''): ?><meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>"><?php endif; ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($site_base_url . '/' . getLogoPath()); ?>">
    <?php [$og_logo_w, $og_logo_h] = logoSize(getLogoPath()); ?>
    <meta property="og:image:width" content="<?php echo $og_logo_w; ?>">
    <meta property="og:image:height" content="<?php echo $og_logo_h; ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($site_full_name); ?> logo">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($full_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($site_base_url . '/' . getLogoPath()); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Web fonts (text shows in the fallback font until they arrive): loaded without blocking the first paint -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap"></noscript>

    <!-- Structured Data (helps Google show rich results / knowledge panel) -->
    <script type="application/ld+json">
    <?php
        $schema_social = array_values(array_filter(getSocialMedia(), function ($url, $network) {
            return !empty($url) && $url[0] !== '#' && $network !== 'whatsapp';   // a chat link is not a profile page
        }, ARRAY_FILTER_USE_BOTH));
        // Town, region and country come from the college's official name ("..., Shakargarh");
        // the street part is added only once the college confirms its full address in Settings.
        $address_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', getSiteAddress()))));
        $schema_address = ['@type' => 'PostalAddress', 'addressLocality' => 'Shakargarh', 'addressRegion' => 'Punjab', 'addressCountry' => 'PK'];
        if (publicContact('site_address') !== '' && !empty($address_lines)) {
            $schema_address['streetAddress'] = implode(', ', $address_lines);
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollegeOrUniversity',
            'name' => $site_full_name,
            'alternateName' => getSiteName(),
            'description' => $meta_description,
            'url' => $official_url !== '' ? $official_url . '/' : '',
            'logo' => $site_base_url . '/' . getLogoPath(),
            'telephone' => trim(getSitePhone()),
            'email' => hasSiteEmail() ? getSiteEmail() : '',
            'address' => $schema_address,
            'sameAs' => $schema_social,
        ];
        // leave out anything not filled in yet instead of publishing empty or placeholder values
        echo json_encode(array_filter($schema, function ($v) { return $v !== '' && $v !== []; }), JSON_UNESCAPED_SLASHES);
    ?>
    </script>

    <script>document.documentElement.classList.add('js');</script>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(minifiedCssUrl('css/style.css')); ?>">

    <!-- Dynamic Theme Colors -->
    <style>
        <?php echo getThemeCSS(); ?>
    </style>

    <!-- Icons: Font Awesome with only the icons this site uses (built by tools/build-icons.py, about 20 KB in all) -->
    <link rel="preload" href="css/fonts/fa-solid-900-subset.woff2?v=<?php echo substr((string) @md5_file(__DIR__ . '/../css/fonts/fa-solid-900-subset.woff2'), 0, 8); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="css/icons.css?v=<?php echo assetVersion('css/icons.css'); ?>">

    <!-- Favicon -->
    <?php // Icons from AdminCP > Branding (or the defaults); ?v= changes with each new file, so browsers fetch it again
    $icons = getIconPaths(); ?>
    <link rel="icon" href="<?php echo htmlspecialchars($icons['ico']); ?>?v=<?php echo assetVersion($icons['ico']); ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($icons['png32']); ?>?v=<?php echo assetVersion($icons['png32']); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($icons['apple']); ?>?v=<?php echo assetVersion($icons['apple']); ?>">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <!-- Header: two tiers (contact/apply strip on top, dark centred menu below), sticky on every page -->
    <?php
    $nav_page = basename($_SERVER['SCRIPT_NAME']);
    $nav_active = static function (array $pages) use ($nav_page) {
        return in_array($nav_page, $pages, true);
    };

    // Main menu, built from the pages that have something to show (pageHasContent), so it never
    // links to an empty page. A group with one page becomes a plain link; two or more become a submenu.
    // Each entry: [label, href, page file] or [label, [entries]].
    $nav_group = static function ($group_label, array $entries, array $single_labels = []) {
        $entries = array_values(array_filter($entries));
        if (count($entries) === 0) {
            return null;
        }
        if (count($entries) === 1) {
            [$label, $href, $file] = $entries[0];
            return [$single_labels[$file] ?? $label, $href, $file];
        }
        return [$group_label, $entries];
    };
    $nav_items = array_values(array_filter([
        ['Home', './', 'index.php'],
        ['About Us', 'about', 'about.php'],
        $nav_group('Academics', [
            ['Programs & Courses', 'courses', 'courses.php'],
            pageHasContent('examination') ? ['Examination', 'examination', 'examination.php'] : null,
            pageHasContent('downloads') ? ['Downloads', 'downloads', 'downloads.php'] : null,
        ], ['courses.php' => 'Programs']),
        ['Admissions', 'admission', 'admission.php'],
        pageHasContent('faculty') ? ['Faculty', 'faculty', 'faculty.php'] : null,
        $nav_group('Student Life', [
            pageHasContent('gallery') ? ['Photo Gallery', 'gallery', 'gallery.php'] : null,
            pageHasContent('campuses') ? ['Our Campuses', 'campuses', 'campuses.php'] : null,
        ], ['gallery.php' => 'Student Life']),
        ['Alumni', 'alumni', 'alumni.php'],
        pageHasContent('events') ? ['News & Events', 'events', 'events.php'] : null,
        ['Contact Us', 'contact', 'contact.php'],
    ]));
    $topbar_phone = trim(explode(',', getSitePhone())[0]);
    $social_links = array_filter(getSocialMedia());
    ?>
    <header class="site-header" id="site-header">
        <div class="edu-topbar">
            <div class="container edu-topbar-inner">
                <a class="brand" href="./" aria-label="<?php echo htmlspecialchars($site_full_name); ?> - Home">
                    <picture><?php if ($logo_webp = logoWebpPath(getLogoSmallPath())): ?><source type="image/webp" srcset="<?php echo htmlspecialchars($logo_webp); ?>?v=<?php echo assetVersion($logo_webp); ?>"><?php endif; ?><img src="<?php echo getLogoSmallPath(); ?>" alt="" class="brand-logo"<?php echo logoImgAttrs(getLogoSmallPath(), 56); ?> onerror="this.style.display='none'"></picture>
                    <span class="brand-text">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                        <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                    </span>
                </a>

                <div class="edu-topbar-info-group">
                    <?php if ($topbar_phone !== ''): ?>
                    <a class="edu-topbar-info" href="tel:<?php echo htmlspecialchars(preg_replace('/[^+0-9]/', '', $topbar_phone)); ?>">
                        <span class="edu-topbar-info-icon"><i class="fas fa-phone"></i></span>
                        <span><small>Call Us</small><strong><?php echo htmlspecialchars($topbar_phone); ?></strong></span>
                    </a>
                    <?php else: ?>
                    <a class="edu-topbar-info" href="contact">
                        <span class="edu-topbar-info-icon"><i class="fas fa-location-dot"></i></span>
                        <span><small>Visit Us</small><strong><?php echo htmlspecialchars(trim(preg_split('/\r\n|\r|\n/', getSiteAddress())[0])); ?></strong></span>
                    </a>
                    <?php endif; ?>
                    <?php if (hasSiteEmail()): ?>
                    <a class="edu-topbar-info" href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>">
                        <span class="edu-topbar-info-icon"><i class="fas fa-envelope"></i></span>
                        <span><small>Email Us</small><strong><?php echo htmlspecialchars(getSiteEmail()); ?></strong></span>
                    </a>
                    <?php elseif (getOfficeHours() !== ''): ?>
                    <a class="edu-topbar-info" href="contact">
                        <span class="edu-topbar-info-icon"><i class="fas fa-clock"></i></span>
                        <span><small>Office Hours</small><strong><?php echo htmlspecialchars(getOfficeHours()); ?></strong></span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="edu-topbar-actions">
                    <?php if (!empty($social_links)): ?>
                    <nav class="edu-topbar-social" aria-label="Social media">
                        <?php foreach (socialNetworks() as $key => $network):
                            if (empty($social_links[$key])) continue; ?>
                            <a href="<?php echo htmlspecialchars($social_links[$key]); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars($network['label']); ?>"><i class="fab <?php echo $network['icon']; ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php endif; ?>

                    <a class="edu-apply-btn" href="admission"><i class="fas fa-pen-to-square" aria-hidden="true"></i> <?php echo htmlspecialchars(content('apply_button_label')); ?></a>

                    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-nav">
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="nav-backdrop" id="nav-backdrop"></div>

        <nav class="edu-navbar" aria-label="Primary navigation">
            <div class="container edu-navbar-inner">
                <div class="site-nav" id="site-nav">
                    <div class="nav-drawer-head">
                        <span>Menu</span>
                        <button type="button" class="nav-close" id="nav-close" aria-label="Close menu"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                    </div>
                    <?php foreach ($nav_items as $item): ?>
                    <?php if (is_array($item[1])): // submenu ?>
                    <?php $group_files = array_column($item[1], 2); ?>
                    <div class="nav-dropdown<?php echo $nav_active($group_files) ? ' is-active' : ''; ?>">
                        <span class="nav-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars($item[0]); ?> <i class="fas fa-chevron-down nav-caret" aria-hidden="true"></i></span>
                        <div class="nav-dropdown-panel">
                            <?php foreach ($item[1] as [$label, $href, $file]): ?>
                            <a href="<?php echo $href; ?>"<?php echo $nav_active([$file]) ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo htmlspecialchars($label); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: [$label, $href, $file] = $item; ?>
                    <a href="<?php echo $href; ?>"<?php echo $nav_active([$file]) ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo htmlspecialchars($label); ?></a>
                    <?php endif; ?>
                    <?php endforeach; ?>

                    <a class="btn btn-secondary header-apply-btn" href="admission"><?php echo htmlspecialchars(content('apply_button_label')); ?></a>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">
