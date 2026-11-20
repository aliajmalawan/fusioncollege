-- =====================================================================
-- Fusion College of Sciences: remove what is left of the previous school's data
--
-- Run once on the live database (phpMyAdmin > SQL), after fusion-rebrand.sql,
-- fusion-content-import.sql and remove-old-gallery.sql. Take a backup first
-- (phpMyAdmin > Export).
--
-- 1. Content the website no longer shows: the previous school's news, events,
--    leaders, demo teachers, courses, fees, campuses, FAQs, feature cards and slides.
--    All of these were switched off during the rebrand; only switched-off rows
--    are deleted, everything shown on the website stays.
-- 2. Records from the previous school's website (before the rebrand on
--    24 October 2026): contact messages, admission forms, alumni sign-ups and
--    reviews, visitor statistics, the online-visitors list and login attempts.
-- 3. The previous school's colour theme and the admin account's old email address.
-- =====================================================================

-- 1. switched-off content
DELETE FROM `news`          WHERE `status` <> 'active';
DELETE FROM `events`        WHERE `status` <> 'active';
DELETE FROM `leadership`    WHERE `status` <> 'active';
DELETE FROM `faculty`       WHERE `status` <> 'active';
DELETE FROM `courses`       WHERE `status` <> 'active';
DELETE FROM `campuses`      WHERE `status` <> 'active';
DELETE FROM `faqs`          WHERE `status` <> 'active';
DELETE FROM `fee_items`     WHERE `status` <> 'active';
DELETE FROM `feature_cards` WHERE `status` <> 'active';
DELETE FROM `hero_carousel` WHERE `status` <> 'active' AND `image_path` LIKE 'images/hero/home-slide-%';

-- 2. records from the previous school's website
DELETE FROM `contacts`             WHERE `created_at`   < '2026-10-24';
DELETE FROM `admissions`           WHERE `created_at`   < '2026-10-24';
DELETE FROM `alumni`               WHERE `created_at`   < '2026-10-24';
DELETE FROM `alumni_reviews`       WHERE `created_at`   < '2026-10-24';
DELETE FROM `website_analytics`    WHERE `created_at`   < '2026-10-24' OR `page_url` LIKE '%tameer%';
DELETE FROM `unique_visitors`      WHERE `first_visit`  < '2026-10-24';
DELETE FROM `admin_login_attempts` WHERE `attempted_at` < '2026-10-24';
DELETE FROM `active_users`;   -- "who is online" list, refills by itself

-- 3. old theme and admin email
DELETE FROM `themes` WHERE `theme_name` LIKE 'Tameer%' AND `is_active` = 0;
UPDATE `admin_users` SET `email` = 'info@fusioncollege.pk' WHERE MD5(`email`) = 'dabf824a9b0f20483029c6ade9c7cb08';   -- the previous school's address (not repeated here)

-- Check: what is left
SELECT 'news' AS what, COUNT(*) AS `rows` FROM `news`
UNION ALL SELECT 'events', COUNT(*) FROM `events`
UNION ALL SELECT 'leadership', COUNT(*) FROM `leadership`
UNION ALL SELECT 'faculty', COUNT(*) FROM `faculty`
UNION ALL SELECT 'courses', COUNT(*) FROM `courses`
UNION ALL SELECT 'campuses', COUNT(*) FROM `campuses`
UNION ALL SELECT 'faqs', COUNT(*) FROM `faqs`
UNION ALL SELECT 'fee items', COUNT(*) FROM `fee_items`
UNION ALL SELECT 'contact messages', COUNT(*) FROM `contacts`
UNION ALL SELECT 'admission forms', COUNT(*) FROM `admissions`;
