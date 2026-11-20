-- =====================================================================
-- Fusion College of Sciences, Shakargarh: database rebrand
--
-- Turns a database that still holds the previous school's content
-- (Tameer-e-Nau Public School, Hasilpur) into Fusion College of Sciences.
--
-- How to run: cPanel > phpMyAdmin > click the website's database on the
-- left > "SQL" tab > paste this whole file > "Go".
-- On this computer: XAMPP > phpMyAdmin > database "fusioncollege" > SQL.
--
-- Safe to run more than once. Nothing is deleted except saved texts that
-- still name the old school (the site then shows the new default text).
-- People, photos, news and results of the old school are only hidden
-- (status inactive / pending): re-enable any of them from AdminCP.
-- Works on MySQL 5.7 / 8 and MariaDB.
-- Tip: Export the database first (phpMyAdmin > Export > Go) as a backup.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PART 1: Tables and columns the code needs (skipped when present)
-- ---------------------------------------------------------------------

-- Courses: program group (Intermediate / Entry Test Preparation / ...) and subject list
SET @add_col = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `level` VARCHAR(20) NOT NULL DEFAULT \'\' AFTER `duration`',
    'SELECT ''courses.level already exists''')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'level');
PREPARE stmt FROM @add_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_col = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `subjects` TEXT NULL AFTER `level`',
    'SELECT ''courses.subjects already exists''')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'subjects');
PREPARE stmt FROM @add_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- YouTube videos on the Photo Gallery page (Admin > Gallery Videos)
CREATE TABLE IF NOT EXISTS `gallery_videos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `youtube_id` VARCHAR(20) NOT NULL,
    `description` VARCHAR(300) NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin login protection (blocks an address after 5 wrong passwords in 15 minutes)
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `username` VARCHAR(50) NOT NULL DEFAULT '',
    `attempted_at` DATETIME NOT NULL,
    KEY `ip_time` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PART 2: Name, contact details and site texts
-- ---------------------------------------------------------------------

-- Saved texts that still describe the old school go, so the new default
-- wording from includes/content_fields.php shows instead
DELETE FROM `settings`
WHERE `setting_value` LIKE '%Tameer%' OR `setting_value` LIKE '%Hasilpur%'
   OR `setting_value` LIKE '%Canal View%' OR `setting_value` LIKE '%Shaheen%'
   OR `setting_value` LIKE '%school%' OR `setting_value` LIKE '%Matric (9th%'
   OR `setting_value` LIKE 'images/site/%' OR `setting_value` LIKE 'images/hero/home-slide-%'
   OR `setting_value` LIKE '%tameer-e-nau-logo%'
   -- banners and photos uploaded for the old school (Admin > Page Content); the new college graphics show instead
   OR (`setting_value` LIKE 'uploads/site/%' AND `setting_key` NOT IN ('principal_photo'))
   OR `setting_key` LIKE 'candle\_%' OR `setting_key` LIKE 'cambridge\_%'
   OR `setting_key` LIKE 'academics\_%' OR `setting_key` LIKE 'assessment\_paragraph\_%'
   OR `setting_key` IN ('courses_early_years_heading', 'courses_early_years_subtitle',
                        'courses_primary_heading', 'courses_primary_subtitle',
                        'courses_middle_heading', 'courses_middle_subtitle',
                        'courses_secondary_heading', 'courses_secondary_subtitle',
                        'home_adm_docs', 'home_adm_docs_note');

-- The settings below are written fresh (delete + insert works whether or
-- not the table has a unique key on setting_key)
DELETE FROM `settings` WHERE `setting_key` IN (
    'site_name', 'hero_title', 'hero_description', 'hero_button_text',
    'hero_button_link', 'hero_image', 'stats_students', 'stats_teachers', 'stats_courses',
    'stats_years', 'about_description', 'about_paragraph_2', 'about_paragraph_3',
    'mission_statement', 'vision_statement', 'footer_about_text', 'copyright_text',
    'principal_message_quote', 'principal_message_para_1', 'principal_message_para_2', 'principal_photo');

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Fusion College of Sciences'),
('hero_title', 'Welcome to Fusion College of Sciences, Shakargarh'),
('hero_description', 'At Fusion College of Sciences, Shakargarh, students prepare for their Intermediate board examinations, university entry tests and the careers that follow.'),
('hero_button_text', 'Apply Now'),
('hero_button_link', 'admission'),
('hero_image', 'images/brand/hero-1.jpg'),
-- Statistics stay hidden until the college enters its real numbers (Admin > Settings > Statistics)
('stats_students', ''),
('stats_teachers', ''),
('stats_courses', ''),
('stats_years', ''),
('about_description', 'Fusion College of Sciences is a science college in Shakargarh, Punjab, dedicated to preparing students for the board examinations, university entry tests and the careers that follow. We offer Intermediate programs in Pre-Medical, Pre-Engineering and Computer Science, along with MDCAT and ECAT entry test preparation.'),
('about_paragraph_2', 'We believe science is best learned through understanding rather than memorisation, and we aim to help every student understand the science behind every formula and walk into the examination hall with confidence.'),
('about_paragraph_3', 'Beyond results, we build discipline, integrity and self-belief, preparing young people from Shakargarh and the surrounding areas to succeed in medicine, engineering, computing and every field they choose.'),
('mission_statement', 'Our mission is to provide quality science education through concept-based learning, so that every student is prepared for the board examinations and entry tests and grows into a responsible, confident citizen.'),
('vision_statement', 'To be the leading college of sciences in the region, recognised for academic excellence and for producing skilled doctors, engineers, scientists and IT professionals who serve Pakistan with integrity.'),
('footer_about_text', 'Fusion College of Sciences, Shakargarh offers Intermediate science programs and entry test preparation, helping students build strong foundations for higher education and professional careers.'),
('copyright_text', 'Fusion College of Sciences, Shakargarh. All rights reserved.'),
-- Principal's message is hidden until the college adds its own (Admin > Settings)
('principal_message_quote', ''),
('principal_message_para_1', ''),
('principal_message_para_2', ''),
('principal_photo', '');

-- Contact details (Admin > Settings > Contact Details). The website only shows a detail once
-- the college ticks "Confirmed" for it. Every detail that is NOT confirmed is reset here:
-- the old school's phone, email, social pages and hours go, and the details seen on the
-- previous Fusion College page are stored as suggestions waiting for confirmation.
-- Confirmed details are never touched, so running this file again keeps them.
DELETE s FROM `settings` s
WHERE s.`setting_key` IN ('site_email', 'site_phone', 'site_address', 'office_hours', 'contact_hours',
                          'facebook_url', 'instagram_url', 'youtube_url', 'twitter_url', 'whatsapp_number', 'google_maps_embed')
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT `setting_key` FROM `settings` WHERE `setting_key` LIKE 'verified\_%' AND `setting_value` = '1') v
      WHERE v.`setting_key` = CONCAT('verified_', IF(s.`setting_key` = 'contact_hours', 'office_hours', s.`setting_key`)));

INSERT INTO `settings` (`setting_key`, `setting_value`)
SELECT * FROM (
    SELECT 'site_email' AS k, 'info@fusioncollege.pk' AS v UNION ALL
    SELECT 'site_phone', '+92 301 7931624' UNION ALL
    SELECT 'site_address', 'Shakargarh, District Narowal\nPunjab, Pakistan' UNION ALL
    SELECT 'office_hours', 'Mon - Sat: 8:00 AM - 2:00 PM, Sunday closed' UNION ALL
    SELECT 'contact_hours', 'Monday - Saturday | 8:00 AM - 2:00 PM\nSunday | Closed' UNION ALL
    SELECT 'facebook_url', '' UNION ALL
    SELECT 'instagram_url', '' UNION ALL
    SELECT 'youtube_url', '' UNION ALL
    SELECT 'twitter_url', ''
) AS suggested
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = suggested.k);

-- A copy of the admin form's security token was stored as a setting by older code
DELETE FROM `settings` WHERE `setting_key` = 'csrf_token';

-- Confirmation switches start off; only the college sets them (never this file)
INSERT INTO `settings` (`setting_key`, `setting_value`)
SELECT CONCAT('verified_', k), '0' FROM (
    SELECT 'site_email' AS k UNION ALL SELECT 'site_phone' UNION ALL SELECT 'site_address' UNION ALL
    SELECT 'office_hours' UNION ALL SELECT 'facebook_url' UNION ALL SELECT 'instagram_url' UNION ALL
    SELECT 'youtube_url' UNION ALL SELECT 'twitter_url'
) AS fields
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = CONCAT('verified_', fields.k));

-- ---------------------------------------------------------------------
-- PART 3: Colours and logo (academic navy, royal blue, brand red)
-- ---------------------------------------------------------------------
INSERT INTO `themes` (`theme_name`, `primary_color`, `secondary_color`, `accent_color`, `logo_path`, `is_active`)
    SELECT 'Fusion Academic Navy', '#0B1F4D', '#FFFFFF', '#E31E24', 'images/fusion-college-logo.png', 0
    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `themes` WHERE `theme_name` = 'Fusion Academic Navy');
UPDATE `themes` SET `primary_color` = '#0B1F4D', `secondary_color` = '#FFFFFF', `accent_color` = '#E31E24'
    WHERE `theme_name` = 'Fusion Academic Navy';
UPDATE `themes` SET `is_active` = IF(`theme_name` = 'Fusion Academic Navy', 1, 0);
-- Every theme points at the new logo unless someone uploaded a custom one
UPDATE `themes` SET `logo_path` = 'images/fusion-college-logo.png'
    WHERE `logo_path` IS NULL OR `logo_path` = '' OR `logo_path` LIKE '%tameer-e-nau-logo%' OR `logo_path` = 'images/logo.png';

-- ---------------------------------------------------------------------
-- PART 4: Home slider
-- ---------------------------------------------------------------------
-- the previous school's slides (the college's own uploaded slides are left alone)
UPDATE `hero_carousel` SET `status` = 'inactive' WHERE `image_path` LIKE 'images/hero/home-slide-%';

-- starter slides, added only while the college has no slides of its own
INSERT INTO `hero_carousel` (`image_path`, `title`, `subtitle`, `text_color`, `display_order`, `status`)
    SELECT 'images/brand/hero-1.jpg', 'Welcome to Fusion College of Sciences, Shakargarh', 'Intermediate science programs and entry test preparation in Shakargarh.', '#ffffff', 1, 'active'
    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `image_path` = 'images/brand/hero-1.jpg')
        AND NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `status` = 'active' AND `image_path` NOT LIKE 'images/brand/%');
INSERT INTO `hero_carousel` (`image_path`, `title`, `subtitle`, `text_color`, `display_order`, `status`)
    SELECT 'images/brand/hero-2.jpg', 'Your Pathway to Medicine', 'F.Sc Pre-Medical and MDCAT preparation for students aiming at medicine, dentistry and the health sciences.', '#ffffff', 2, 'active'
    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `image_path` = 'images/brand/hero-2.jpg')
        AND NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `status` = 'active' AND `image_path` NOT LIKE 'images/brand/%');
INSERT INTO `hero_carousel` (`image_path`, `title`, `subtitle`, `text_color`, `display_order`, `status`)
    SELECT 'images/brand/hero-3.jpg', 'Engineering & Computing Futures', 'F.Sc Pre-Engineering and ICS for students aiming at engineering, computing and the physical sciences.', '#ffffff', 3, 'active'
    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `image_path` = 'images/brand/hero-3.jpg')
        AND NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `status` = 'active' AND `image_path` NOT LIKE 'images/brand/%');

UPDATE `hero_carousel` SET `title` = 'Welcome to Fusion College of Sciences, Shakargarh', `subtitle` = 'Intermediate science programs and entry test preparation in Shakargarh.' WHERE `image_path` = 'images/brand/hero-1.jpg';
UPDATE `hero_carousel` SET `subtitle` = 'F.Sc Pre-Medical and MDCAT preparation for students aiming at medicine, dentistry and the health sciences.' WHERE `image_path` = 'images/brand/hero-2.jpg';
UPDATE `hero_carousel` SET `subtitle` = 'F.Sc Pre-Engineering and ICS for students aiming at engineering, computing and the physical sciences.' WHERE `image_path` = 'images/brand/hero-3.jpg';

-- ---------------------------------------------------------------------
-- PART 5: Programs (Admin > Courses)
-- Each list below is matched by name: rows in the list are updated and
-- switched on, missing ones are added, everything else is hidden (not
-- deleted, so past admission applications still show their course).
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_courses`;
-- Same column types and collation as the real table, so the name match below works on any server
CREATE TEMPORARY TABLE `tmp_courses` SELECT `name`, `description`, `duration`, `level`, `subjects`, `icon`, `display_order` FROM `courses` LIMIT 0;
INSERT INTO `tmp_courses` VALUES
('F.Sc Pre-Medical', 'For students aiming at MBBS, BDS, DPT, Pharm-D, nursing and the life sciences. Prepares students for the HSSC examinations in the Pre-Medical group.', '2 Years (Part I & II)', 'intermediate',
    'Biology\nChemistry\nPhysics\nEnglish\nUrdu\nIslamiat / Pakistan Studies\nTarjuma-tul-Quran', 'fa-stethoscope', 1),
('F.Sc Pre-Engineering', 'For students heading to engineering universities and the physical sciences. Prepares students for the HSSC examinations in the Pre-Engineering group.', '2 Years (Part I & II)', 'intermediate',
    'Mathematics\nPhysics\nChemistry\nEnglish\nUrdu\nIslamiat / Pakistan Studies\nTarjuma-tul-Quran', 'fa-gears', 2),
('ICS (Computer Science)', 'For future software engineers, data scientists and IT professionals. Prepares students for the HSSC examinations in the ICS group.', '2 Years (Part I & II)', 'intermediate',
    'Computer Science\nMathematics\nPhysics / Statistics\nEnglish\nUrdu\nIslamiat / Pakistan Studies\nTarjuma-tul-Quran', 'fa-laptop-code', 3),
('MDCAT Preparation', 'Preparation for the medical and dental college admission test (MDCAT).', '3 - 4 Months', 'test_prep',
    'Biology\nChemistry\nPhysics\nEnglish\nLogical Reasoning', 'fa-heart-pulse', 4),
('ECAT Preparation', 'Preparation for engineering university entry tests (ECAT).', '3 - 4 Months', 'test_prep',
    'Mathematics\nPhysics\nChemistry / Computer Science\nEnglish', 'fa-compass-drafting', 5);

UPDATE `courses` c SET c.`status` = 'inactive'
    WHERE NOT EXISTS (SELECT 1 FROM `tmp_courses` t WHERE t.`name` = c.`name`);
UPDATE `courses` c JOIN `tmp_courses` t ON t.`name` = c.`name`
    SET c.`description` = t.`description`, c.`duration` = t.`duration`, c.`level` = t.`level`,
        c.`subjects` = t.`subjects`, c.`icon` = t.`icon`, c.`display_order` = t.`display_order`, c.`status` = 'active';
INSERT INTO `courses` (`name`, `description`, `duration`, `level`, `subjects`, `icon`, `display_order`, `status`)
    SELECT t.`name`, t.`description`, t.`duration`, t.`level`, t.`subjects`, t.`icon`, t.`display_order`, 'active'
    FROM `tmp_courses` t WHERE NOT EXISTS (SELECT 1 FROM `courses` c WHERE c.`name` = t.`name`);
DROP TEMPORARY TABLE `tmp_courses`;

-- Old fee amounts belonged to the previous school: hidden until the college enters its own (Admin > Fee Items)
UPDATE `fee_items` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';

-- ---------------------------------------------------------------------
-- PART 6: Feature cards, admission requirements and FAQs (same matching)
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_cards`;
CREATE TEMPORARY TABLE `tmp_cards` SELECT `section`, `icon`, `title`, `description`, `display_order` FROM `feature_cards` LIMIT 0;
INSERT INTO `tmp_cards` VALUES
('why_choose_us', 'fas fa-flask', 'Science-Focused Programs', 'F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS: the main science paths after Matric, in one college.', 1),
('why_choose_us', 'fas fa-pen-ruler', 'Entry Test Preparation', 'MDCAT and ECAT preparation programs for students heading to medical and engineering universities.', 2),
('why_choose_us', 'fas fa-book', 'Board-Aligned Studies', 'Programs follow the Intermediate (HSSC) board syllabus that students are examined on.', 3),
('why_choose_us', 'fas fa-location-dot', 'Close to Home', 'Science education in Shakargarh for students of the town and the surrounding areas.', 4),
('admission_requirements', 'fas fa-flask', 'For Intermediate Programs', 'Matric (SSC) result card or detailed marks certificate\nCopy of B-Form or CNIC\nFather/Guardian CNIC copy\nFour passport-size photographs\nCharacter certificate from previous institution\nMigration certificate (for other boards)', 1),
('admission_requirements', 'fas fa-pen-ruler', 'For Entry Test Preparation', 'F.Sc Part I or Part II result card\nCopy of B-Form or CNIC\nTwo passport-size photographs\nCompleted registration form', 2);

UPDATE `feature_cards` f SET f.`status` = 'inactive'
    WHERE f.`section` IN ('why_choose_us', 'admission_requirements')
      AND NOT EXISTS (SELECT 1 FROM `tmp_cards` t WHERE t.`section` = f.`section` AND t.`title` = f.`title`);
UPDATE `feature_cards` f JOIN `tmp_cards` t ON t.`section` = f.`section` AND t.`title` = f.`title`
    SET f.`icon` = t.`icon`, f.`description` = t.`description`, f.`display_order` = t.`display_order`, f.`status` = 'active';
INSERT INTO `feature_cards` (`section`, `icon`, `title`, `description`, `display_order`, `status`)
    SELECT t.`section`, t.`icon`, t.`title`, t.`description`, t.`display_order`, 'active'
    FROM `tmp_cards` t WHERE NOT EXISTS (SELECT 1 FROM `feature_cards` f WHERE f.`section` = t.`section` AND f.`title` = t.`title`);
DROP TEMPORARY TABLE `tmp_cards`;

-- These groups describe systems, testing routines and staff qualifications of the previous
-- school; they stay hidden until the college confirms its own (Admin > Feature Cards)
UPDATE `feature_cards` SET `status` = 'inactive' WHERE `section` IN ('digital_features', 'assessment_features', 'faculty_highlights');

DROP TEMPORARY TABLE IF EXISTS `tmp_faqs`;
CREATE TEMPORARY TABLE `tmp_faqs` SELECT `question`, `answer`, `display_order` FROM `faqs` LIMIT 0;
INSERT INTO `tmp_faqs` VALUES
('Which programs does Fusion College of Sciences offer?', 'We offer Intermediate programs in F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS (Computer Science), along with MDCAT and ECAT entry test preparation. See the Programs page for subjects and details.', 1),
('Who can apply for admission to the first year?', 'Students who have passed Matric (SSC), or are waiting for their result, can apply. The admissions office will confirm eligibility for each program.', 2),
('How can I apply for admission?', 'Apply online through the Admissions page, or visit the college in Shakargarh during office hours. Bring the required documents to complete your admission.', 3),
('Do you prepare students for MDCAT and ECAT?', 'Yes. MDCAT and ECAT preparation programs are offered. See the Programs page for the subjects covered.', 4),
('What is the fee structure?', 'Fees depend on the program. Please contact the admissions office or visit the college for the current fee schedule.', 5),
('Where is the college located?', 'Fusion College of Sciences is in Shakargarh, District Narowal, Punjab. See the Contact page for directions and our location map.', 6);

UPDATE `faqs` q SET q.`status` = 'inactive'
    WHERE NOT EXISTS (SELECT 1 FROM `tmp_faqs` t WHERE t.`question` = q.`question`);
UPDATE `faqs` q JOIN `tmp_faqs` t ON t.`question` = q.`question`
    SET q.`answer` = t.`answer`, q.`display_order` = t.`display_order`, q.`status` = 'active';
INSERT INTO `faqs` (`question`, `answer`, `display_order`, `status`)
    SELECT t.`question`, t.`answer`, t.`display_order`, 'active'
    FROM `tmp_faqs` t WHERE NOT EXISTS (SELECT 1 FROM `faqs` q WHERE q.`question` = t.`question`);
DROP TEMPORARY TABLE `tmp_faqs`;

-- ---------------------------------------------------------------------
-- PART 7: Hide the previous school's people, photos, news and results
-- (each can be switched back on in AdminCP if it belongs to the college)
-- ---------------------------------------------------------------------
UPDATE `leadership` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `faculty` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `campuses` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `news` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `events` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `notifications` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `gallery` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `gallery_videos` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `board_results` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `exam_datesheets` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `downloads` SET `status` = 'inactive' WHERE `created_at` < '2026-10-24';
UPDATE `alumni` SET `status` = 'pending' WHERE `status` = 'approved' AND `created_at` < '2026-10-24';
UPDATE `alumni_reviews` SET `status` = 'pending' WHERE `status` = 'approved';

-- Gallery albums keep their links but get college names
UPDATE `gallery_categories` SET `name` = 'Classes & Laboratories' WHERE `slug` = 'classroom-activities';
UPDATE `gallery_categories` SET `name` = 'College Events' WHERE `slug` = 'school-events';

-- ---------------------------------------------------------------------
-- Check: what the site now uses
-- ---------------------------------------------------------------------
SELECT `setting_key`, LEFT(`setting_value`, 80) AS `value` FROM `settings`
    WHERE `setting_key` IN ('site_name', 'site_email', 'site_phone', 'office_hours', 'hero_title')
       OR `setting_key` LIKE 'verified\_%';
SELECT `theme_name`, `primary_color`, `accent_color`, `logo_path` FROM `themes` WHERE `is_active` = 1;
SELECT `name`, `level`, `status` FROM `courses` WHERE `status` = 'active' ORDER BY `display_order`;
