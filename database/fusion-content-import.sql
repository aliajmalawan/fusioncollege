-- =====================================================================
-- Fusion College of Sciences, Shakargarh: content from the college's own
-- website (fusioncollege.edu.pk, October 2026) and its 2026 admission flyer.
--
-- Run AFTER database/fusion-rebrand.sql, in phpMyAdmin (SQL tab).
-- Safe to run more than once: rows are matched by name / file and updated.
-- The photos it points to are in the repository (uploads/faculty, uploads/gallery,
-- uploads/results, images/leadership), so deploy the code first.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Contact details: published by the college on its own website and
--    flyer, so they are marked as confirmed (Admin > Settings > Contact Details)
-- ---------------------------------------------------------------------
DELETE FROM `settings` WHERE `setting_key` IN (
    'site_email', 'verified_site_email', 'site_phone', 'verified_site_phone',
    'site_address', 'verified_site_address', 'office_hours', 'verified_office_hours', 'contact_hours',
    'facebook_url', 'verified_facebook_url', 'official_site_url', 'contact_map_query');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_email', 'info@fusioncollege.pk'), ('verified_site_email', '1'),
('site_phone', '0301-7931624, 0308-3232488'), ('verified_site_phone', '1'),
('site_address', 'Khushali Bank Plaza, Railway Road\nShakargarh, Punjab, Pakistan'), ('verified_site_address', '1'),
('office_hours', 'Mon - Sat: 8:00 AM - 2:00 PM, Sunday closed'), ('verified_office_hours', '1'),
('contact_hours', 'Monday - Saturday | 8:00 AM - 2:00 PM\nSunday | Closed'),
('facebook_url', 'https://www.facebook.com/profile.php?id=100063675867280'), ('verified_facebook_url', '1'),
('official_site_url', 'https://fusioncollege.edu.pk'),
('contact_map_query', 'Fusion College Boys Campus, Shakargarh');

-- ---------------------------------------------------------------------
-- 2. About the college (Vision & Mission page and footer of the old site)
-- ---------------------------------------------------------------------
DELETE FROM `settings` WHERE `setting_key` IN (
    'about_description', 'about_paragraph_2', 'about_paragraph_3', 'mission_statement', 'vision_statement',
    'footer_about_text', 'home_about_text', 'home_about_image', 'home_facilities_image', 'about_story_image',
    'adm_fee_subtitle', 'adm_fee_note', 'home_adm_docs', 'stats_years');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('about_description', 'In Shakargarh, for the last ten years, the faculty of Fusion College has been at the forefront of transformative education. With the legacy of this experience in education behind us, we continue to focus on preparing students for the nation.'),
('about_paragraph_2', 'Students enjoy a unique atmosphere in both laboratory and classroom, defined by a mixture of academic rigour and personal relationships. Our teachers are dedicated to forming students: they are mentored to build confident personalities, nurture innovative ideas and discover their innate potential. Oral exams, daily assessments, quiz competitions, practical projects and class presentations supplement formal written exams, and interactive teaching helps students develop effective communication skills.'),
('about_paragraph_3', 'We believe that education without values is hollow. To instil life-enhancing values and promote integrity and ethical behaviour in work and relationships, additional value-oriented classes are held, and extracurricular activities are an integral part of Fusion.'),
('mission_statement', 'Our mission is to foster a passion for excellence, compassionate commitment, an innovative spirit, the ability to accept challenges, competence, creativity and leadership for service to all.'),
('vision_statement', 'Fusion aims to foster competent, conscientious and compassionate individuals who can and will make a difference wherever they are: morally responsible leaders who champion the values of truth, justice and peace.'),
('footer_about_text', 'At Fusion College, we are dedicated to fostering excellence through holistic education. Our learning environment empowers students to thrive academically, socially and personally, preparing them to lead with integrity and make a positive impact on the world.'),
('home_about_text', 'At Fusion College, we are dedicated to fostering excellence through holistic education. Our learning environment empowers students to thrive academically, socially and personally, preparing them to lead with integrity and make a positive impact on the world.'),
('home_about_image', 'uploads/site/fcs-intro-academic-excellence.jpg'),
('home_facilities_image', 'uploads/site/fcs-facilities-multimedia-class.jpg'),
('about_story_image', 'uploads/site/fcs-about-classroom.jpg'),
('adm_fee_subtitle', 'Scholarship policy and fee structure, session 2026 - 2028'),
('adm_fee_note', 'Fees are fixed for two academic years. Board registration and admission fees are not included and are charged separately as per board policy.\nScholarships need at least 70% to qualify; scholarship holders must keep 85% marks in monthly tests and the annual board exam, and a scholarship ends after two consecutive months below this. Scholarship merit = scholarship test marks 70% + 9th class marks 30%.'),
-- "for the last ten years" (Vision & Mission page of the old site)
('stats_years', '10'),
('home_adm_docs', 'One passport-size photograph\nPhotocopy of the 9th class result card or roll number with marks details\n10th class roll number\nFather''s CNIC number\nStudent''s B-Form number\nContact numbers (phone and WhatsApp)');

-- ---------------------------------------------------------------------
-- 3. Programs: the college offers F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS;
--    MDCAT / ECAT preparation is part of them, not a separate program
-- ---------------------------------------------------------------------
UPDATE `courses` SET `status` = 'inactive' WHERE `name` IN ('MDCAT Preparation', 'ECAT Preparation');

-- ---------------------------------------------------------------------
-- 4. Campuses
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_campuses`;
CREATE TEMPORARY TABLE `tmp_campuses` SELECT `name`, `tagline`, `description`, `address`, `phone`, `icon`, `display_order` FROM `campuses` LIMIT 0;
INSERT INTO `tmp_campuses` VALUES
('Boys Campus', 'Main Campus, Shakargarh', 'Principal: Muhammad Ijaz', 'Khushali Bank Plaza, Railway Road, Shakargarh', '0301-7931624, 0308-3232488', 'fa-building-columns', 1),
('Girls Campus', 'Shakargarh', 'Principal: Zarka Tariq', '1st Floor, UBL Bank, Railway Road, Shakargarh', '0334-5361040, 0334-9738161', 'fa-building-columns', 2);
-- (the Narowal Campus is not listed on the website; any other campus is switched off below)
UPDATE `campuses` c SET c.`status` = 'inactive' WHERE NOT EXISTS (SELECT 1 FROM `tmp_campuses` t WHERE t.`name` = c.`name`);
UPDATE `campuses` c JOIN `tmp_campuses` t ON t.`name` = c.`name`
    SET c.`tagline` = t.`tagline`, c.`description` = t.`description`, c.`address` = t.`address`, c.`phone` = t.`phone`,
        c.`icon` = t.`icon`, c.`display_order` = t.`display_order`, c.`status` = 'active';
INSERT INTO `campuses` (`name`, `tagline`, `description`, `address`, `phone`, `icon`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_campuses` t WHERE NOT EXISTS (SELECT 1 FROM `campuses` c WHERE c.`name` = t.`name`);
DROP TEMPORARY TABLE `tmp_campuses`;
-- campus photos: the Girls Campus building (its sign reads "Girls Campus") and Boys Campus students
UPDATE `campuses` SET `image_path` = 'uploads/gallery/fcs-campus-building.jpg' WHERE `name` = 'Girls Campus' AND (`image_path` IS NULL OR `image_path` = '');
UPDATE `campuses` SET `image_path` = 'uploads/gallery/fcs-students.jpg' WHERE `name` = 'Boys Campus' AND (`image_path` IS NULL OR `image_path` = '');

-- ---------------------------------------------------------------------
-- 5. Faculty and management ("Our Team" and "Our Principals" on the old site)
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_faculty`;
CREATE TEMPORARY TABLE `tmp_faculty` SELECT `name`, `designation`, `subjects`, `qualification`, `photo`, `display_order` FROM `faculty` LIMIT 0;
INSERT INTO `tmp_faculty` VALUES
('Qasim Khan', 'Managing Director', '', 'MPhil Physics (QAU)', 'uploads/faculty/fcs-qasim-khan.jpg', 1),
('Muhammad Ijaz', 'Principal (Boys Campus)', '', '', 'uploads/faculty/fcs-muhammad-ijaz.jpg', 2),
('Zarka Tariq', 'Principal (Girls Campus)', '', '', 'uploads/faculty/fcs-zarka-tariq.jpg', 3),
('Prof. Abdul Qadoos', 'Honorary Advisor', 'Biology', 'PhD Scholar (Biology)', 'uploads/faculty/fcs-abdul-qadoos.jpg', 4),
('Prof. Liaqat Ali Mohsin', 'Honorary Advisor', 'English', 'PhD Scholar (English)', 'uploads/faculty/fcs-liaqat-ali-mohsin.jpg', 5),
('Abdul Qayoom', 'HOD Physics', 'Physics', 'M.S Physics', 'uploads/faculty/fcs-abdul-qayoom.jpg', 6),
('Rashid Farooq', 'HOD Biology', 'Biology', 'MSc Biology', 'uploads/faculty/fcs-rashid-farooq.jpg', 7),
('Akhtar Abbas', 'HOD Chemistry', 'Chemistry', 'BS Chemistry', 'uploads/faculty/fcs-akhtar-abbas.jpg', 8),
('Salman Marwat', 'HOD Maths', 'Mathematics', 'MPhil Maths', 'uploads/faculty/fcs-salman-marwat.jpg', 9),
('Muhammad Awais', 'HOD Computer Science', 'Computer Science', 'B.S Software Engineering', 'uploads/faculty/fcs-muhammad-awais.jpg', 10),
('Zaheer Ahmad Khan', 'HOD English', 'English', 'MPhil English', 'uploads/faculty/fcs-zaheer-ahmad-khan.jpg', 11),
('Shahzia Naseer', 'HOD Urdu', 'Urdu', 'M.A Urdu', 'uploads/faculty/fcs-shahzia-naseer.jpg', 12),
('Qari Muhamad Anwar', 'HOD Islamic Studies', 'Islamic Studies', '', 'uploads/faculty/fcs-qari-muhamad-anwar.jpg', 13);
-- (the previous school's teachers are already hidden by fusion-rebrand.sql)
UPDATE `faculty` f JOIN `tmp_faculty` t ON t.`name` = f.`name`
    SET f.`designation` = t.`designation`, f.`subjects` = t.`subjects`, f.`qualification` = t.`qualification`,
        f.`photo` = t.`photo`, f.`display_order` = t.`display_order`, f.`status` = 'active';
INSERT INTO `faculty` (`name`, `designation`, `subjects`, `qualification`, `photo`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_faculty` t WHERE NOT EXISTS (SELECT 1 FROM `faculty` f WHERE f.`name` = t.`name`);
DROP TEMPORARY TABLE `tmp_faculty`;

-- ---------------------------------------------------------------------
-- 6. Chairman's message (homepage and About page)
-- ---------------------------------------------------------------------
DELETE FROM `leadership` WHERE `name` = 'Qasim Khan' AND `designation` = 'Chairman, Fusion College of Sciences';
INSERT INTO `leadership` (`name`, `designation`, `role_title`, `photo`, `message`, `display_order`, `status`) VALUES
('Qasim Khan', 'Chairman, Fusion College of Sciences', 'Chairman''s Message', 'images/leadership/fcs-chairman-qasim-khan.jpg',
'Fusion College is distinguished by its commitment to providing a holistic educational experience that seamlessly integrates the Punjab Text Board curriculum with innovative teaching strategies. With a rich history of educational excellence, we take pride in our ability to cultivate a diverse array of skills and attributes in our students.\n\nOver the years, Fusion College has established itself as a nurturing environment where students not only excel academically but also develop essential qualities such as confidence, competence, compassion and commitment. Our dedicated faculty and staff are deeply invested in the holistic development of each student, ensuring they are well prepared to navigate the complexities of the modern world.\n\nAt Fusion, we believe in instilling a strong sense of responsibility and accountability in our students. Beyond academic achievement, we aim to empower students to recognise their roles as dignified individuals, dedicated learners and responsible members of their families and communities.\n\nWe view education as a collaborative effort between students, educators, parents and the wider community, and we are dedicated to fostering strong partnerships that support student success. Join us at Fusion College and embark on a journey of personal growth, academic excellence and lifelong learning. Together, we can shape a brighter future for ourselves and our nation.',
1, 'active');

-- ---------------------------------------------------------------------
-- 7. Fee structure and scholarships (admission flyer, session 2026 - 2028)
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_fees`;
CREATE TEMPORARY TABLE `tmp_fees` SELECT `icon`, `label`, `amount`, `display_order` FROM `fee_items` LIMIT 0;
INSERT INTO `tmp_fees` VALUES
('fa-calendar-check', 'Annual charges (admission fee, paper fund, test series, workshop printing, AC charge and academic support)', 'Rs. 30,000 per year', 1),
('fa-money-bill-wave', 'Monthly fee: 9th and 10th marks below 85%', 'Rs. 8,000', 2),
('fa-money-bill-wave', 'Monthly fee: above 85% (472+ in 9th, 1020+ in 10th)', 'Rs. 7,000', 3),
('fa-money-bill-wave', 'Monthly fee: above 90% (500+ in 9th, 1080+ in 10th)', 'Rs. 6,000', 4),
('fa-trophy', 'Scholarship test, top 1-5 (100%)', 'Free, no annual charges', 5),
('fa-trophy', 'Scholarship test, top 6-10 (100%)', 'Free + Rs. 30,000 annual', 6),
('fa-award', 'Scholarship test, top 11-15 (50%)', 'Rs. 4,000 + Rs. 15,000 annual', 7),
('fa-award', 'Scholarship test, top 16-20 (25%)', 'Rs. 5,000 + Rs. 20,000 annual', 8),
('fa-medal', '9th class: 545 marks and above', 'Free, no annual charges', 9),
('fa-medal', '9th class: 540 marks and above', 'Free + Rs. 30,000 annual', 10),
('fa-medal', '10th class: 1170 and above, top 1-5', 'Free, no annual charges', 11),
('fa-medal', '10th class: 1170 and above, 6th onwards', 'Free + Rs. 30,000 annual', 12),
('fa-star', 'Performance scholarship: top three positions for three consecutive months', 'Free + Rs. 30,000 annual', 13);
UPDATE `fee_items` f SET f.`status` = 'inactive' WHERE NOT EXISTS (SELECT 1 FROM `tmp_fees` t WHERE t.`label` = f.`label`);
UPDATE `fee_items` f JOIN `tmp_fees` t ON t.`label` = f.`label`
    SET f.`icon` = t.`icon`, f.`amount` = t.`amount`, f.`display_order` = t.`display_order`, f.`status` = 'active';
INSERT INTO `fee_items` (`icon`, `label`, `amount`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_fees` t WHERE NOT EXISTS (SELECT 1 FROM `fee_items` f WHERE f.`label` = t.`label`);
DROP TEMPORARY TABLE `tmp_fees`;

-- ---------------------------------------------------------------------
-- 8. "Why choose us", facilities and admission requirements
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_cards`;
CREATE TEMPORARY TABLE `tmp_cards` SELECT `section`, `icon`, `title`, `description`, `display_order` FROM `feature_cards` LIMIT 0;
INSERT INTO `tmp_cards` VALUES
('why_choose_us', 'fas fa-graduation-cap', 'Academic Excellence', 'Fusion College is known for its commitment to academic excellence. Experienced educators give students instruction that prepares them for success in higher education and beyond.', 1),
('why_choose_us', 'fas fa-seedling', 'Holistic Development', 'Education goes beyond the classroom: a holistic approach to learning nurtures each student''s intellectual, social and personal growth.', 2),
('why_choose_us', 'fas fa-people-group', 'Strong Community and Support', 'Small class sizes, personal attention from teachers and a network of peers give students the support they need to thrive.', 3),
('why_choose_us', 'fas fa-pen-ruler', 'MDCAT, ECAT and Board Preparation', 'Preparation for MDCAT, ECAT and other competitive exams alongside the board syllabus.', 4),
('facilities', 'fas fa-chalkboard', 'Multimedia Classrooms', 'Classrooms equipped with multimedia, projectors and sound systems.', 1),
('facilities', 'fas fa-microchip', 'AI-Integrated Learning', 'An AI-integrated learning environment and modern teaching methods.', 2),
('facilities', 'fas fa-compass', 'Career Counselling', 'Career counselling and academic guidance for every student.', 3),
('facilities', 'fas fa-book-open', 'Study Guides', 'Study guides and structured learning materials for each subject.', 4),
('facilities', 'fas fa-hands-helping', 'Extra Academic Support', 'Extra help for students who need support in their problem areas.', 5),
('facilities', 'fas fa-comments', 'Communication Skills', 'Emphasis on writing, analytical thinking, social learning and speaking skills.', 6),
('admission_requirements', 'fas fa-flask', 'For Intermediate Programs', 'One passport-size photograph\nPhotocopy of the 9th class result card or roll number with marks details\n10th class roll number\nFather''s CNIC number\nStudent''s B-Form number\nContact numbers (phone and WhatsApp)', 1);
UPDATE `feature_cards` f SET f.`status` = 'inactive'
    WHERE f.`section` IN ('why_choose_us', 'facilities', 'admission_requirements')
      AND NOT EXISTS (SELECT 1 FROM `tmp_cards` t WHERE t.`section` = f.`section` AND t.`title` = f.`title`);
UPDATE `feature_cards` f JOIN `tmp_cards` t ON t.`section` = f.`section` AND t.`title` = f.`title`
    SET f.`icon` = t.`icon`, f.`description` = t.`description`, f.`display_order` = t.`display_order`, f.`status` = 'active';
INSERT INTO `feature_cards` (`section`, `icon`, `title`, `description`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_cards` t WHERE NOT EXISTS (SELECT 1 FROM `feature_cards` f WHERE f.`section` = t.`section` AND f.`title` = t.`title`);
DROP TEMPORARY TABLE `tmp_cards`;

-- ---------------------------------------------------------------------
-- 9. FAQs updated with the college's own information
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_faqs`;
CREATE TEMPORARY TABLE `tmp_faqs` SELECT `question`, `answer`, `display_order` FROM `faqs` LIMIT 0;
INSERT INTO `tmp_faqs` VALUES
('Which programs does Fusion College of Sciences offer?', 'F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS at the Intermediate level, with preparation for MDCAT, ECAT and other competitive exams. See the Programs page for the subjects.', 1),
('Who can apply for admission to the first year?', 'Students who have passed Matric (SSC), or are waiting for their result, can apply. The admissions office will confirm eligibility for each program.', 2),
('How can I apply for admission?', 'Apply online through the Admissions page, or visit the Boys or Girls Campus in Shakargarh during office hours with the required documents.', 3),
('Do you prepare students for MDCAT and ECAT?', 'Yes. Preparation for MDCAT, ECAT and other competitive exams is part of the academic program.', 4),
('What is the fee structure?', 'Annual charges are Rs. 30,000 and the monthly fee is Rs. 6,000 to Rs. 8,000 depending on 9th and 10th class marks. Top scorers in the scholarship test and in board marks study free or at a reduced fee. The full table is on the Admissions page.', 5),
('Do you offer scholarships?', 'Yes. Scholarships are given on the Fusion Scholarship Test, on 9th and 10th class marks, and to the top three positions over three consecutive months. Details are in the fee structure on the Admissions page.', 6),
('Where is the college located?', 'The Boys Campus is at Khushali Bank Plaza, Railway Road, and the Girls Campus on the 1st Floor of UBL Bank, Railway Road, Shakargarh.', 7);
UPDATE `faqs` q SET q.`status` = 'inactive' WHERE NOT EXISTS (SELECT 1 FROM `tmp_faqs` t WHERE t.`question` = q.`question`);
UPDATE `faqs` q JOIN `tmp_faqs` t ON t.`question` = q.`question`
    SET q.`answer` = t.`answer`, q.`display_order` = t.`display_order`, q.`status` = 'active';
INSERT INTO `faqs` (`question`, `answer`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_faqs` t WHERE NOT EXISTS (SELECT 1 FROM `faqs` q WHERE q.`question` = t.`question`);
DROP TEMPORARY TABLE `tmp_faqs`;

-- ---------------------------------------------------------------------
-- 10. Photo gallery and results
-- ---------------------------------------------------------------------
UPDATE `gallery_categories` SET `name` = 'Classes & Laboratories', `display_order` = 1, `status` = 'active' WHERE `slug` = 'classroom-activities';
UPDATE `gallery_categories` SET `status` = 'inactive' WHERE `slug` = 'school-events';
INSERT INTO `gallery_categories` (`name`, `slug`, `display_order`, `status`)
    SELECT 'Campus & Students', 'campus-students', 2, 'active' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `gallery_categories` WHERE `slug` = 'campus-students');
INSERT INTO `gallery_categories` (`name`, `slug`, `display_order`, `status`)
    SELECT 'Achievements', 'achievements', 3, 'active' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `gallery_categories` WHERE `slug` = 'achievements');

DROP TEMPORARY TABLE IF EXISTS `tmp_gallery`;
CREATE TEMPORARY TABLE `tmp_gallery` SELECT `title`, `image_path`, `category`, `display_order` FROM `gallery` LIMIT 0;
INSERT INTO `tmp_gallery` VALUES
('Students at work in the biology laboratory', 'uploads/gallery/fcs-biology-lab.jpg', 'classroom-activities', 1),
('Biology class', 'uploads/gallery/fcs-biology-class.jpg', 'classroom-activities', 2),
('Students with a DNA model', 'uploads/gallery/fcs-dna-model.jpg', 'classroom-activities', 3),
('Fusion College campus', 'uploads/gallery/fcs-campus-building.jpg', 'campus-students', 4),
('Fusion College students', 'uploads/gallery/fcs-students.jpg', 'campus-students', 5),
('Examination hall, boys', 'uploads/gallery/fcs-exam-boys.jpg', 'campus-students', 6),
('Examination hall, girls', 'uploads/gallery/fcs-exam-girls.jpg', 'campus-students', 7),
('Our doctors 2020-23 and engineers 2023', 'uploads/gallery/fcs-doctors-engineers-2020-2023.jpg', 'achievements', 8);
UPDATE `gallery` g JOIN `tmp_gallery` t ON t.`image_path` = g.`image_path`
    SET g.`title` = t.`title`, g.`category` = t.`category`, g.`display_order` = t.`display_order`, g.`status` = 'active';
INSERT INTO `gallery` (`title`, `image_path`, `category`, `display_order`, `status`)
    SELECT t.*, 'active' FROM `tmp_gallery` t WHERE NOT EXISTS (SELECT 1 FROM `gallery` g WHERE g.`image_path` = t.`image_path`);
DROP TEMPORARY TABLE `tmp_gallery`;

INSERT INTO `board_results` (`title`, `board_type`, `year`, `image_path`, `display_order`, `status`)
    SELECT 'Intermediate results 2023 (BISE Gujranwala)', 'Intermediate', 2023, 'uploads/results/fcs-intermediate-results-2023.jpg', 1, 'active' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `board_results` WHERE `image_path` = 'uploads/results/fcs-intermediate-results-2023.jpg');

-- ---------------------------------------------------------------------
-- 11. Downloads (scholarship test papers, keys and results on Google Drive).
--     The old site gave no dates, so the date is left empty instead of guessed.
-- ---------------------------------------------------------------------
ALTER TABLE `downloads` MODIFY `date` DATE NULL;

DROP TEMPORARY TABLE IF EXISTS `tmp_downloads`;
CREATE TEMPORARY TABLE `tmp_downloads` SELECT `description`, `file_path`, `display_order` FROM `downloads` LIMIT 0;
INSERT INTO `tmp_downloads` VALUES
('FST 2026 final result, Shakargarh Campus', 'https://drive.google.com/file/d/1g6zaX5HoanpZlX8D0ZgNJcCTRg2NvD4F/view', 1),
('FST 2026 final result, Narowal Campus', 'https://drive.google.com/file/d/14CGCOip6GPanySHUm5hwNR4DrMV0whKi/view', 2),
('FST 2026 Shakargarh Campus key (Biology)', 'https://drive.google.com/file/d/1iZn7bxJZqRsiyZLUQx64UMYJ7DENzmDL/view', 3),
('FST 2026 Shakargarh Campus key (Computer)', 'https://drive.google.com/file/d/1-ukyrKgdNuVJkGEsaqWnelncjTschG3d/view', 4),
('Scholarship test 2026 Narowal Campus key (Biology)', 'https://drive.google.com/file/d/1PgUap0nRqllepPQqdLgAW8uQ_gY1ekes/view', 5),
('Scholarship test 2026 Narowal Campus key (Computer)', 'https://drive.google.com/file/d/10hF_Up0qhUzB8bf5fThXgG-m_0C5cPND/view', 6),
('Result of scholarship test 2025', 'https://drive.google.com/file/d/1Vfh3KUCF3veJjWM4MA5eoSL_J_jYhHeO/view', 7),
('Fusion Scholarship Test 2025 key', 'https://drive.google.com/file/d/1JTHjJ_YAVJklvybKJ4zEu8iGaxJI3YEx/view', 8),
('FST provisional results 2024', 'https://drive.google.com/file/d/1BlEjOO9QDm8mWz1UykddwVEXeHHu_w5w/view', 9),
('Fusion Scholarship Test key 2024', 'https://drive.google.com/file/d/15P3HGuijDcEZMM5ST89Sf6cKzHIiJC7g/view', 10),
('Fusion Scholarship Computer Test key 2024', 'https://drive.google.com/file/d/1A8L65mz7RgyIu4-FgEZZrYry6d7MsIKD/view', 11),
('Fusion Scholarship Test sample paper', 'https://drive.google.com/file/d/1udMZ7dCH0kLlSQ6liiyeEccmCYjw462N/view', 12),
('Fusion Scholarship Test solution', 'https://drive.google.com/file/d/1dM-3N6YmQR_LFWNRMSFiq03iYjC3NK3q/view', 13);
UPDATE `downloads` d JOIN `tmp_downloads` t ON t.`file_path` = d.`file_path`
    SET d.`description` = t.`description`, d.`display_order` = t.`display_order`, d.`status` = 'active';
INSERT INTO `downloads` (`date`, `description`, `file_path`, `file_name`, `file_type`, `display_order`, `status`)
    SELECT NULL, t.`description`, t.`file_path`, 'Google Drive', 'link', t.`display_order`, 'active'
    FROM `tmp_downloads` t WHERE NOT EXISTS (SELECT 1 FROM `downloads` d WHERE d.`file_path` = t.`file_path`);
DROP TEMPORARY TABLE `tmp_downloads`;

-- ---------------------------------------------------------------------
-- 12. Home slider: a small label per slide, and the slide texts
-- ---------------------------------------------------------------------
SET @add_col = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `hero_carousel` ADD COLUMN `eyebrow` VARCHAR(120) NULL AFTER `image_path`',
    'SELECT ''hero_carousel.eyebrow already exists''')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hero_carousel' AND COLUMN_NAME = 'eyebrow');
PREPARE stmt FROM @add_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- first slide (whatever photo it has): the college's own tagline and the welcome heading
UPDATE `hero_carousel` SET `eyebrow` = 'Igniting Minds, Shaping Futures',
    `title` = 'Welcome to Fusion College of Sciences, Shakargarh',
    `subtitle` = 'Intermediate science education with preparation for MDCAT, ECAT and competitive exams.'
    WHERE `status` = 'active' AND `display_order` = 1;
UPDATE `hero_carousel` SET `eyebrow` = 'F.Sc Pre-Medical', `title` = 'Your Path to a Career in Medicine',
    `subtitle` = 'Biology, Chemistry and Physics taught by subject specialists, with focused MDCAT preparation.', `display_order` = 2
    WHERE `image_path` = 'images/brand/hero-2.jpg';
UPDATE `hero_carousel` SET `eyebrow` = 'F.Sc Pre-Engineering & ICS', `title` = 'Built for Future Engineers and Innovators',
    `subtitle` = 'Mathematics, Physics and Computer Science with ECAT preparation in an AI-integrated learning environment.', `display_order` = 3
    WHERE `image_path` = 'images/brand/hero-3.jpg';
INSERT INTO `hero_carousel` (`image_path`, `eyebrow`, `title`, `subtitle`, `text_color`, `display_order`, `status`)
    SELECT 'images/brand/hero-4.jpg', 'Scholarships, Session 2026 - 2028', 'Scholarships of up to 100%',
        'Awarded on the Fusion Scholarship Test and on 9th and 10th class marks.', '#ffffff', 4, 'active' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `hero_carousel` WHERE `image_path` IN ('images/brand/hero-4.jpg', 'images/hero/fcs-scholarship-test-2026.jpg'));
-- the scholarship slide shows the 2026 scholarship test hall instead of a graphic
UPDATE `hero_carousel` SET `image_path` = 'images/hero/fcs-scholarship-test-2026.jpg'
    WHERE `image_path` = 'images/brand/hero-4.jpg';

-- the scholarship-test hall photo without the caption banner printed across its bottom
UPDATE `gallery` SET `image_path` = 'uploads/gallery/fcs-scholarship-test-hall.jpg'
    WHERE `image_path` = 'uploads/gallery/1793085386_6ae04fcad989a.jpg';

-- Campus Life on the homepage uses each album's first photo as its cover: the clearest recent photos first
UPDATE `gallery` SET `display_order` = -1 WHERE `image_path` IN (
    'uploads/gallery/1793085125_6ae04ec5eef62.jpg', 'uploads/gallery/1793085195_6ae04f0b9419f.jpg',
    'uploads/gallery/1793085236_6ae04f34a8602.jpg', 'uploads/gallery/fcs-scholarship-test-hall.jpg',
    'uploads/gallery/1793085302_6ae04f768dd16.jpg', 'uploads/gallery/1793085336_6ae04f9865c73.jpg',
    'uploads/gallery/1793085430_6ae04ff6961cf.jpg', 'uploads/gallery/fcs-campus-building.jpg');

-- ---------------------------------------------------------------------
-- Check
-- ---------------------------------------------------------------------
SELECT 'faculty' AS what, COUNT(*) AS active FROM `faculty` WHERE `status` = 'active'
UNION ALL SELECT 'campuses', COUNT(*) FROM `campuses` WHERE `status` = 'active'
UNION ALL SELECT 'programs', COUNT(*) FROM `courses` WHERE `status` = 'active'
UNION ALL SELECT 'fee items', COUNT(*) FROM `fee_items` WHERE `status` = 'active'
UNION ALL SELECT 'gallery photos', COUNT(*) FROM `gallery` WHERE `status` = 'active'
UNION ALL SELECT 'downloads', COUNT(*) FROM `downloads` WHERE `status` = 'active';
