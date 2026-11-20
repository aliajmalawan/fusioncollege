-- =====================================================================
-- Fusion College of Sciences: remove the previous school's gallery photos
--
-- Run once on the live database (phpMyAdmin > SQL), after
-- fusion-rebrand.sql and fusion-content-import.sql.
-- Deletes only gallery photos that are switched off (the previous school's
-- photos were switched off by fusion-rebrand.sql) and are not the college's
-- own (uploads/gallery/fcs-...), plus the empty "College Events" album.
-- Photos uploaded later in AdminCP > Gallery and shown on the site are kept.
--
-- The image files themselves stay on the server until deleted in cPanel >
-- File Manager > public_html/uploads/gallery (everything except fcs-* files).
-- =====================================================================

DELETE FROM `gallery`
WHERE `status` = 'inactive' AND `image_path` NOT LIKE 'uploads/gallery/fcs-%';

DELETE FROM `gallery_categories`
WHERE `slug` = 'school-events'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `category` FROM `gallery`) g WHERE g.`category` = 'school-events');

SELECT `category`, COUNT(*) AS `photos` FROM `gallery` GROUP BY `category`;
