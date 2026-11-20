    </main>

    <!-- Footer: brand and introduction | quick links | opening hours | contact.
         Contact details and hours appear only once confirmed in Admin > Settings > Contact Details. -->
    <?php
    $footer_social = array_filter(getSocialMedia());
    $footer_hours = getOpeningHours();
    $footer_phones = array_values(array_filter(array_map('trim', explode(',', getSitePhone()))));
    // The address is saved on several lines in Settings; the footer shows it on one: "Shakargarh, Punjab, Pakistan"
    $footer_address = implode(', ', array_filter(array_map(fn($line) => trim($line, " 	,"), preg_split('/
|
|
/', getSiteAddress()))));
    ?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section footer-brand">
                    <a href="./" class="footer-logo">
                        <picture><?php if ($logo_webp = logoWebpPath(getLogoSmallPath())): ?><source type="image/webp" srcset="<?php echo htmlspecialchars($logo_webp); ?>?v=<?php echo assetVersion($logo_webp); ?>"><?php endif; ?><img src="<?php echo getLogoSmallPath(); ?>" alt="<?php echo htmlspecialchars(getSiteFullName()); ?> logo"<?php echo logoImgAttrs(getLogoSmallPath(), 72); ?> loading="lazy" onerror="this.style.display='none'"></picture>
                        <span>
                            <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                            <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                        </span>
                    </a>
                    <p><?php echo htmlspecialchars(getFooterContent()['about_text']); ?></p>
                    <a href="admission" class="btn btn-secondary footer-apply"><i class="fas fa-pen-to-square" aria-hidden="true"></i> <?php echo htmlspecialchars(content('apply_button_label')); ?></a>
                </div>

                <nav class="footer-section footer-links" aria-labelledby="footer-links-title">
                    <h2 class="footer-heading" id="footer-links-title">Quick Links</h2>
                    <ul>
                        <li><a href="./">Home</a></li>
                        <li><a href="about">About Us</a></li>
                        <li><a href="courses">Academic Programs</a></li>
                        <li><a href="admission">Admissions</a></li>
                        <li><a href="alumni">Alumni</a></li>
                        <li><a href="contact">Contact Us</a></li>
                        <li><a href="privacy-policy">Privacy Policy</a></li>
                    </ul>
                </nav>

                <div class="footer-section footer-hours">
                    <h2 class="footer-heading">Opening Hours</h2>
                    <?php if (!empty($footer_hours)): ?>
                    <ul class="footer-hours-list">
                        <?php foreach ($footer_hours as [$day, $time]): ?>
                        <li<?php echo strcasecmp($time, 'Closed') === 0 ? ' class="is-closed"' : ''; ?>>
                            <?php if ($day !== ''): ?><span class="footer-hours-day"><?php echo htmlspecialchars($day); ?></span><?php endif; ?>
                            <span class="footer-hours-time"><?php echo htmlspecialchars($time); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="footer-note">Opening hours will be published here soon. Until then, please <a href="contact">send the admissions office a message</a>.</p>
                    <?php endif; ?>
                </div>

                <div class="footer-section footer-contact">
                    <h2 class="footer-heading">Contact Us</h2>
                    <ul class="footer-contact-list">
                        <li><i class="fas fa-location-dot" aria-hidden="true"></i><span><span class="sr-only">Address: </span><?php echo htmlspecialchars($footer_address); ?></span></li>
                        <?php foreach ($footer_phones as $number): ?>
                        <li><i class="fas fa-phone" aria-hidden="true"></i><a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $number)); ?>"><span class="sr-only">Phone: </span><?php echo htmlspecialchars($number); ?></a></li>
                        <?php endforeach; ?>
                        <?php if (hasSiteEmail()): ?>
                        <li><i class="fas fa-envelope" aria-hidden="true"></i><a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><span class="sr-only">Email: </span><?php echo htmlspecialchars(getSiteEmail()); ?></a></li>
                        <?php endif; ?>
                        <?php if (empty($footer_phones) && !hasSiteEmail()): ?>
                        <li><i class="fas fa-paper-plane" aria-hidden="true"></i><a href="contact">Send us a message</a></li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($footer_social)): ?>
                    <div class="social-links" aria-label="Social media">
                        <?php foreach (socialNetworks() as $network => $info): ?>
                            <?php if (!empty($footer_social[$network])): ?>
                            <a href="<?php echo htmlspecialchars($footer_social[$network]); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars($info['label']); ?>"><i class="fab <?php echo $info['icon']; ?>" aria-hidden="true"></i></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSiteFullName()); ?>. All rights reserved.</p>
                <p class="footer-legal"><a href="privacy-policy">Privacy Policy</a><span aria-hidden="true">&middot;</span><a href="contact">Contact</a></p>
            </div>
        </div>
    </footer>

    <!-- Website Analytics Tracker -->
    <?php
    // Track page views for analytics (only on public pages, not admin)
    if (file_exists(__DIR__ . '/analytics_tracker.php')) {
        include __DIR__ . '/analytics_tracker.php';
    }
    ?>

    <div class="scroll-progress" id="scroll-progress" aria-hidden="true"></div>
    <?php include __DIR__ . '/whatsapp_button.php'; ?>
    <button type="button" class="back-to-top" id="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>

    <!-- JavaScript -->
    <script src="js/main.js?v=<?php echo assetVersion('js/main.js'); ?>"></script>
</body>
</html>
