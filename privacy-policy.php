<?php
require_once 'includes/config.php';
$page_title = 'Privacy Policy';
$meta_description = 'How the website of ' . getSiteFullName() . ' handles the information you share through its forms, and what is recorded when you visit.';
// Describes what this website's forms and visit statistics actually collect (see admission.php,
// contact.php, alumni.php and includes/analytics_helper.php); keep it in step when those change.
$policy_updated = '24 October 2026';
?>
<?php include 'includes/header.php'; ?>

<?php renderPageHero(content('banner_about_image'), 'Legal', 'Privacy Policy', 'How this website handles the information you share with us', 'Privacy Policy', true, 'fa-shield-halved'); ?>

<section class="bg-light">
    <div class="container">
        <article class="card policy-card reveal">
            <p class="policy-updated">Last updated: <?php echo $policy_updated; ?></p>

            <h2>About this policy</h2>
            <p>This policy explains what information the website of <?php echo htmlspecialchars(getSiteFullName()); ?> collects, why, and who can see it. It covers this website only.</p>

            <h2>Information you give us</h2>
            <p>You only share personal information with us when you fill in one of the forms on this website:</p>
            <ul>
                <li><strong>Admission form:</strong> student name, father's or guardian's name, CNIC / B-Form number, phone number, email address (optional), home address, previous education, the program applied for, any additional information you add, and a document if you choose to upload one.</li>
                <li><strong>Contact form:</strong> your name, email address, phone number, the subject and your message.</li>
                <li><strong>Alumni registration:</strong> name, father's name, the course and year you passed, current job, department and city, mobile and WhatsApp numbers, an optional photo and your feedback.</li>
                <li><strong>Alumni review:</strong> your name, passing year, rating and review.</li>
            </ul>

            <h2>How this information is used</h2>
            <ul>
                <li>Admission applications are used to process the application and to contact the applicant about it.</li>
                <li>Contact messages are used to reply to you.</li>
                <li>Each submitted form is stored in the website's database and sent by email to the college office. When you give an email address, a confirmation is emailed to you.</li>
                <li>Alumni entries and reviews appear on the website only after the college approves them. An approved alumni entry shows the photo, name, course, passing year, current job and city; an approved review shows the name, passing year, rating and review. Phone numbers are never shown on the website.</li>
            </ul>

            <h2>Information recorded when you visit</h2>
            <p>To count visits and see which pages are useful, the website records the page you open, the date and time, the page you came from, your browser and device type, and your IP address. It uses a session cookie that the forms need to work securely. The website shows no advertising and uses no advertising cookies.</p>

            <h2>Services from other providers</h2>
            <p>Some parts of the website are loaded from other companies' servers, which then receive your IP address under their own privacy policies: fonts from Google Fonts, the map on the Contact page from Google Maps, and video thumbnails from YouTube (a video itself only loads from YouTube when you press play).</p>

            <h2>Your choices</h2>
            <p>To ask what information the college holds about you, or to have it corrected or removed, please <a href="contact">contact the college</a><?php if (hasSiteEmail()): ?> or email <a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a><?php endif; ?>.</p>

            <h2>Changes to this policy</h2>
            <p>If the website starts collecting different information, this page will be updated and the date at the top will change.</p>
        </article>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
