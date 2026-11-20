<?php
/**
 * Registry of every piece of public-site content that is editable from
 * AdminCP -> Page Content (manage_content.php).
 *
 * Each field: key (settings table), label, type, default (what the site shows
 * until someone saves a value) and an optional hint.
 *   type: text | textarea | lines (one item per line) | url | image | logo
 * Defaults may use {site_name}, replaced with the site name at render time.
 * Values are stored in the existing `settings` table, so no schema change.
 */

/** Small helper so the registry below stays readable. */
function cf($key, $label, $type, $default, $hint = '') {
    return ['key' => $key, 'label' => $label, 'type' => $type, 'default' => $default, 'hint' => $hint];
}

/** Banner + SEO fields shared by every inner page. */
function pageFields($slug, $eyebrow, $title, $subtitle, $seoTitle, $seoDesc) {
    return [
        cf("banner_{$slug}_image", 'Banner photo', 'image', "images/brand/banner-{$slug}.jpg", 'Wide photo shown behind the page title (landscape, at least 1400px wide works best).'),
        cf("banner_{$slug}_eyebrow", 'Banner small label', 'text', $eyebrow),
        cf("banner_{$slug}_title", 'Banner title (main heading)', 'text', $title),
        cf("banner_{$slug}_subtitle", 'Banner subtitle', 'text', $subtitle),
        cf("seo_{$slug}_title", 'Browser / Google title', 'text', $seoTitle, 'Shown on the browser tab and in search results. "| Full name" is added automatically.'),
        cf("seo_{$slug}_description", 'Google description', 'textarea', $seoDesc, 'Aim for 120-155 characters.'),
    ];
}

$registry = [
    'global' => ['label' => 'Site-wide', 'icon' => 'fa-globe', 'fields' => [
        cf('site_logo', 'College logo', 'logo', 'images/fusion-college-logo.png', 'The official logo (PNG with a transparent background is best, JPG/WebP also work, up to 20 MB). It keeps its own shape and proportions everywhere: header, footer, emails and share previews. The browser tab icon follows it unless a separate favicon is uploaded under Logo & Favicon in the sidebar.'),
        cf('site_full_name', 'Full official name', 'text', '{site_name}, Shakargarh', 'Used in browser titles, the footer copyright, emails and search results. {site_name} is the name set in Settings.'),
        cf('site_tagline', 'Tagline under the college name', 'text', 'Shakargarh', 'Small line below the name in the header and footer.'),
        cf('seo_title_suffix', 'Home page title suffix', 'text', 'F.Sc, ICS & Entry Test Preparation', 'Home page browser title becomes "Full name | this text".'),
        cf('apply_button_label', 'Header "Apply" button text', 'text', 'Apply for Admission'),
        cf('seo_home_description', 'Home page Google description', 'textarea', '{site_name}, Shakargarh: Intermediate science programs (F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS) and MDCAT and ECAT entry test preparation.'),
    ]],

    'whatsapp' => ['label' => 'WhatsApp Button', 'icon' => 'fa-comment-dots', 'fields' => [
        cf('whatsapp_button_label', 'Button text', 'text', 'Chat on WhatsApp', 'Shown beside the green WhatsApp button on large screens. The button appears on every page while the WhatsApp link is shown (Settings > Social Media).'),
        cf('whatsapp_message', 'Message filled in for the visitor', 'text', 'Assalam-o-Alaikum, I would like to know about admissions at {site_name}.', 'The visitor can change it before sending. Leave empty to start with an empty message.'),
    ]],

    'home_hero' => ['label' => 'Home: Hero & About', 'icon' => 'fa-house', 'fields' => [
        cf('home_hero_eyebrow', 'Hero small label', 'text', 'Intermediate Science College · Shakargarh'),
        cf('hero_title', 'Hero headline', 'text', 'Welcome to Fusion College of Sciences, Shakargarh'),
        cf('hero_description', 'Hero text', 'textarea', 'At {site_name}, Shakargarh, students prepare for their Intermediate board examinations, university entry tests and the careers that follow.'),
        cf('hero_image', 'Fallback hero photo', 'image', '', 'Only used when there are no active slides. The home slider itself is managed under Home Slider in the sidebar.'),
        cf('home_hero_btn1_label', 'Hero button 1 text', 'text', 'Explore Programs'),
        cf('home_hero_btn1_link', 'Hero button 1 link', 'url', 'courses'),
        cf('home_hero_btn2_label', 'Hero button 2 text', 'text', 'Contact Admissions'),
        cf('home_hero_btn2_link', 'Hero button 2 link', 'url', 'contact'),
        cf('home_about_eyebrow', 'About section small label', 'text', 'Introduction'),
        cf('home_about_heading', 'About section heading', 'text', 'About {site_name}'),
        cf('home_about_text', 'About section introduction', 'textarea', '{site_name} in Shakargarh offers Intermediate science programs for students who want a focused, science-based education after Matric, with a clear path toward medicine, engineering, computing and the sciences.'),
        cf('home_about_image', 'About section photo', 'image', 'uploads/site/fcs-intro-academic-excellence.jpg', 'Portrait photo (4:5) looks best.'),
        cf('home_about_btn_label', 'About section button text', 'text', 'Learn More'),
        cf('stats_label_students', 'Statistics label: students', 'text', 'Students', 'Numbers are edited in Settings -> Statistics.'),
        cf('stats_label_faculty', 'Statistics label: faculty', 'text', 'Faculty Members'),
        cf('stats_label_years', 'Statistics label: years', 'text', 'Years of Excellence'),
        cf('stats_label_programs', 'Statistics label: programs', 'text', 'Programs'),
        cf('stats_label_campuses', 'Statistics label: campuses', 'text', 'Campuses'),
    ]],

    'home_sections' => ['label' => 'Home: Sections', 'icon' => 'fa-table-cells-large', 'fields' => [
        cf('home_quick_1_title', 'Quick link 1: title', 'text', 'Admissions', 'Quick links under the slider. Clear the title to hide a card.'),
        cf('home_quick_1_text', 'Quick link 1: text', 'text', 'Apply online in a few simple steps'),
        cf('home_quick_1_link', 'Quick link 1: link', 'url', 'admission'),
        cf('home_quick_2_title', 'Quick link 2: title', 'text', 'Academics'),
        cf('home_quick_2_text', 'Quick link 2: text', 'text', 'F.Sc, ICS and entry test programs'),
        cf('home_quick_2_link', 'Quick link 2: link', 'url', 'courses'),
        cf('home_quick_3_title', 'Quick link 3: title', 'text', 'Fee & Scholarships'),
        cf('home_quick_3_text', 'Quick link 3: text', 'text', 'Fee structure and scholarship policy'),
        cf('home_quick_3_link', 'Quick link 3: link', 'url', 'admission#fees'),
        cf('home_quick_4_title', 'Quick link 4: title', 'text', 'Contact Us'),
        cf('home_quick_4_text', 'Quick link 4: text', 'text', 'Visit us or send a message'),
        cf('home_quick_4_link', 'Quick link 4: link', 'url', 'contact'),
        cf('home_academics_eyebrow', 'Academics: small label', 'text', 'Academics'),
        cf('home_academics_heading', 'Academics: heading', 'text', 'Programs Built for Your Ambition'),
        cf('home_academics_subtitle', 'Academics: subtitle', 'text', 'Choose the path that leads to your future in medicine, engineering or computing', 'The program cards themselves are the active courses under Courses in the sidebar.'),
        cf('home_facilities_eyebrow', 'Facilities: small label', 'text', 'Campus & Facilities', 'The facility cards themselves are in Feature Cards -> Facilities.'),
        cf('home_facilities_heading', 'Facilities: heading', 'text', 'Our Facilities'),
        cf('home_facilities_subtitle', 'Facilities: subtitle', 'text', 'Facilities that support learning at {site_name}'),
        cf('home_facilities_image', 'Facilities: photo', 'image', 'uploads/site/fcs-facilities-multimedia-class.jpg', 'Shown beside the facilities list (landscape or square).'),
        cf('home_philosophy_eyebrow', 'Philosophy: small label', 'text', 'Our Philosophy'),
        cf('home_philosophy_heading', 'Philosophy: heading', 'text', 'Understanding, Not Memorisation'),
        cf('home_philosophy_text', 'Philosophy: paragraph', 'textarea', "At {site_name}, we believe science is learned by understanding, questioning and practising, not by memorising. Our aim is to explain concepts clearly, connect them to the real world, and help every student turn hard work into strong results."),
        cf('home_assessment_eyebrow', 'Assessment (About page): small label', 'text', 'Assessment Philosophy'),
        cf('home_assessment_heading', 'Assessment: heading', 'text', 'Our Approach to Assessment'),
        cf('assessment_paragraph_1', 'Assessment: paragraph 1', 'textarea', 'Assessment at {site_name} is aligned with the Intermediate board examinations and the university entry tests our students prepare for, so they practise in the format that matters.'),
        cf('assessment_paragraph_2', 'Assessment: paragraph 2', 'textarea', 'Results are used to find weak topics early and to keep students and parents informed about progress.'),
        cf('home_attributes_eyebrow', 'Learner attributes (About page): small label', 'text', 'What We Nurture'),
        cf('home_attributes_heading', 'Learner attributes: heading', 'text', 'Learner Attributes We Build'),
        cf('home_attributes_subtitle', 'Learner attributes: subtitle', 'text', 'Qualities we aim to develop in every student at {site_name}'),
        cf('home_digital_eyebrow', 'Digital campus: small label', 'text', 'Smart Campus'),
        cf('home_digital_heading', 'Digital campus: heading', 'text', 'Connected Digital Campus'),
        cf('home_digital_subtitle', 'Digital campus: subtitle', 'text', 'Results, attendance and fee records that keep students and parents informed at every step.'),
        cf('home_why_eyebrow', 'Why choose us: small label', 'text', 'Why Choose Us'),
        cf('home_why_heading', 'Why choose us: heading', 'text', 'Why Choose Fusion College of Sciences?'),
        cf('home_why_subtitle', 'Why choose us: subtitle', 'text', 'Science programs and entry test preparation in Shakargarh'),
        cf('home_life_eyebrow', 'Campus life: small label', 'text', 'Campus Life', 'The photo cards come from Gallery categories automatically.'),
        cf('home_life_heading', 'Campus life: heading', 'text', 'Life at {site_name}'),
        cf('home_life_subtitle', 'Campus life: subtitle', 'text', 'Moments from classes, events and celebrations at {site_name}'),
        cf('home_testimonials_eyebrow', 'Testimonials: small label', 'text', 'Testimonials'),
        cf('home_testimonials_heading', 'Testimonials: heading', 'text', 'What Our Community Says'),
        cf('home_testimonials_subtitle', 'Testimonials: subtitle', 'text', 'Real experiences shared by our students and alumni'),
        cf('home_news_eyebrow', 'News & events: small label', 'text', 'Stay Updated'),
        cf('home_news_heading', 'News & events: heading', 'text', 'News & Events'),
        cf('home_news_subtitle', 'News & events: subtitle', 'text', 'The latest announcements and upcoming activities from {site_name}'),
        cf('home_faq_eyebrow', 'FAQs: small label', 'text', 'Got Questions?'),
        cf('home_faq_heading', 'FAQs: heading', 'text', 'Frequently Asked Questions'),
        cf('home_faq_subtitle', 'FAQs: subtitle', 'text', 'Quick answers for prospective students and parents'),
        cf('home_contact_eyebrow', 'Contact block: small label', 'text', 'Get In Touch'),
        cf('home_contact_heading', 'Contact block: heading', 'text', 'Have a Question? Send Us a Message'),
        cf('home_contact_text', 'Contact block: text', 'textarea', 'Send us your question and the admissions office will get back to you. For urgent queries, call or visit the college.'),
    ]],

    'home_admissions' => ['label' => 'Home: Admissions & Closing', 'icon' => 'fa-user-plus', 'fields' => [
        cf('home_adm_eyebrow', 'Admissions: small label', 'text', 'Admissions'),
        cf('home_adm_heading', 'Admissions: heading', 'text', 'Begin Your Journey in Science'),
        cf('home_adm_text', 'Admissions: introduction', 'textarea', 'Joining {site_name} is simple. Complete the online application, bring the required documents to the admissions office, and our team will help you choose the right program.'),
        cf('home_adm_btn1_label', 'Admissions: button 1 text', 'text', 'Apply Now'),
        cf('home_adm_btn2_label', 'Admissions: button 2 text', 'text', 'Contact Admissions'),
        cf('home_adm_docs', 'Required documents', 'lines', "Matric (SSC) result card or detailed marks certificate\nCopy of B-Form or CNIC\nFather/Guardian CNIC copy\nFour passport-size photographs\nCharacter certificate from previous institution", 'One document per line.'),
        cf('home_adm_docs_note', 'Documents note', 'text', 'Our admissions office will confirm the exact documents for your chosen program.'),
        cf('home_cta_heading', 'Closing banner: heading', 'text', 'Your Future in Science Starts Here'),
        cf('home_cta_text', 'Closing banner: text', 'textarea', 'Join {site_name}, Shakargarh, and prepare for the university and career you want.'),
        cf('home_cta_btn1_label', 'Closing banner: button 1 text', 'text', 'Contact Us'),
        cf('home_cta_btn2_label', 'Closing banner: button 2 text', 'text', 'Apply Now'),
    ]],

    'about' => ['label' => 'About page', 'icon' => 'fa-circle-info', 'fields' => array_merge(
        pageFields('about', 'About Us', 'About {site_name}', 'Learn about our mission, vision, and commitment to educational excellence', 'About Us', 'Learn about {site_name}, Shakargarh: our mission, vision and core values, and our commitment to quality science education and student success.'),
        [
            cf('about_story_eyebrow', 'Story: small label', 'text', 'Who We Are'),
            cf('about_story_heading', 'Story: heading', 'text', 'Our Story'),
            cf('about_description', 'Story: paragraph 1', 'textarea', ''),
            cf('about_paragraph_2', 'Story: paragraph 2', 'textarea', ''),
            cf('about_paragraph_3', 'Story: paragraph 3', 'textarea', ''),
            cf('about_story_image', 'Story photo', 'image', 'uploads/site/fcs-about-classroom.jpg'),
            cf('mission_statement', 'Mission statement', 'textarea', ''),
            cf('vision_statement', 'Vision statement', 'textarea', ''),
            cf('principal_message_quote', "Principal's message: quote", 'textarea', '', 'Leave all principal fields empty to hide the card.'),
            cf('principal_message_para_1', "Principal's message: paragraph 1", 'textarea', ''),
            cf('principal_message_para_2', "Principal's message: paragraph 2", 'textarea', ''),
            cf('principal_photo', "Principal's photo", 'image', ''),
        ]
    )],

    'courses' => ['label' => 'Courses page', 'icon' => 'fa-book', 'fields' => array_merge(
        pageFields('courses', 'Academics', 'Programs & Courses', 'F.Sc Pre-Medical, F.Sc Pre-Engineering and ICS, with MDCAT and ECAT preparation', 'Programs & Courses', 'Programs at {site_name}, Shakargarh: F.Sc Pre-Medical, F.Sc Pre-Engineering, ICS, and MDCAT and ECAT preparation. See subjects and how to apply.'),
        [
            cf('courses_path_eyebrow', 'Overview: small label', 'text', 'Our Programs', 'The courses themselves are added and edited under Courses in the sidebar.'),
            cf('courses_path_heading', 'Overview: heading', 'text', 'Find the Right Program for You'),
            cf('courses_path_subtitle', 'Overview: subtitle', 'text', 'Every program is built around board exams, entry tests and your next step after college'),
            cf('courses_intermediate_heading', 'Intermediate: heading', 'text', 'Intermediate Programs'),
            cf('courses_intermediate_subtitle', 'Intermediate: subtitle', 'text', 'Two-year F.Sc and ICS programs (Part I & II) leading to the HSSC board examinations'),
            cf('courses_graduate_heading', 'Graduate: heading', 'text', 'Graduate Programs'),
            cf('courses_graduate_subtitle', 'Graduate: subtitle', 'text', 'Associate degree and BS programs for students continuing after Intermediate'),
            cf('courses_test_prep_heading', 'Entry test prep: heading', 'text', 'Entry Test Preparation'),
            cf('courses_test_prep_subtitle', 'Entry test prep: subtitle', 'text', 'Preparation for the MDCAT and ECAT university entry tests'),
            cf('courses_short_courses_heading', 'Short courses: heading', 'text', 'Short Courses & Skills'),
            cf('courses_short_courses_subtitle', 'Short courses: subtitle', 'text', 'Practical courses that build skills for university and work'),
            cf('courses_card_btn', 'Course card button text', 'text', 'Apply for admission'),
            cf('courses_req_eyebrow', 'Requirements: small label', 'text', 'Requirements'),
            cf('courses_req_heading', 'Requirements: heading', 'text', 'Admission Requirements'),
            cf('courses_req_subtitle', 'Requirements: subtitle', 'text', 'What you need to know before applying'),
            cf('courses_cta_heading', 'Bottom banner: heading', 'text', 'Ready to Enroll?'),
            cf('courses_cta_text', 'Bottom banner: text', 'text', 'Take the first step toward your career in science with {site_name}'),
        ]
    )],
    'admission' => ['label' => 'Admission page', 'icon' => 'fa-file-pen', 'fields' => pageFields('admission', 'Join Us', 'Admission Application', 'Start your educational journey with {site_name}', 'Admissions', 'Apply online to {site_name}, Shakargarh, for F.Sc, ICS and entry test programs. See the admission process, required documents and how to reach us.')],
    'faculty' => ['label' => 'Faculty page', 'icon' => 'fa-chalkboard-user', 'fields' => pageFields('faculty', 'Our Team', 'Our Faculty', 'Meet the teachers of {site_name}', 'Our Faculty', 'Meet the faculty of {site_name}, Shakargarh, who teach the Intermediate science and entry test programs.')],
    'campuses' => ['label' => 'Campus page', 'icon' => 'fa-building-columns', 'fields' => pageFields('campuses', 'Our Network', 'Our Campuses', 'Campus locations of {site_name}', 'Our Campuses', 'Campus locations and contact details of {site_name}, Shakargarh.')],
    'gallery' => ['label' => 'Gallery page', 'icon' => 'fa-images', 'fields' => pageFields('gallery', 'Campus Life', 'Photo Gallery', 'Explore moments captured at {site_name}', 'Photo Gallery', 'Photos and videos of classes, college events and celebrations at {site_name}, Shakargarh.')],
    'events' => ['label' => 'News & Events page', 'icon' => 'fa-calendar-days', 'fields' => pageFields('events', "What's Happening", 'News & Events', 'Stay updated with latest happenings at {site_name}', 'News & Events', 'Latest news, announcements and upcoming events from {site_name}, Shakargarh: admissions updates, results, competitions and celebrations.')],
    'examination' => ['label' => 'Examination page', 'icon' => 'fa-file-lines', 'fields' => pageFields('examination', 'Academics', 'Examination', 'Datesheets & Board Results', 'Examination', 'Exam datesheets, test schedules and Intermediate board results of {site_name}, Shakargarh. Check upcoming exams and our past results.')],
    'alumni' => ['label' => 'Alumni page', 'icon' => 'fa-user-graduate', 'fields' => array_merge(pageFields('alumni', 'Alumni Network', 'Our Alumni', 'Join our alumni community and share your success story', 'Alumni', 'Meet the alumni of {site_name}, Shakargarh, read where our former students are today, share your own experience and join the alumni network.'), [cf('alumni_default_photo', 'Default alumni photo', 'image', 'images/site/alumni-default.png', 'Shown for every alumnus who has no photo of their own. A square picture works best. Each alumnus\'s own photo is changed under Alumni in the sidebar.')])],
    'downloads' => ['label' => 'Downloads page', 'icon' => 'fa-download', 'fields' => pageFields('downloads', 'Resources', 'Downloads', 'Important documents and files for students', 'Downloads', 'Download admission forms, notices, syllabus and study material from {site_name}, Shakargarh, all in one place for students and parents.')],
    'contact' => ['label' => 'Contact page', 'icon' => 'fa-envelope', 'fields' => array_merge(
        pageFields('contact', 'Get In Touch', 'Contact Us', "Get in touch with us — we're here to help!", 'Contact Us', 'Contact {site_name}, Shakargarh. Find our address and location map, or send the admissions office a message.'),
        [cf('contact_map_query', 'Map location', 'text', 'Shakargarh, Punjab, Pakistan', 'Text searched on Google Maps for the embedded map. It shows the town until the college confirms its exact location: then enter the full address or the college name as listed on Google Maps.')]
    )],
];

// Section headings and banner texts on the inner pages, added to their page's group
$sectionFields = [
    'about' => [
        cf('about_values_eyebrow', 'Core values: small label', 'text', 'Our Values'),
        cf('about_values_heading', 'Core values: heading', 'text', 'Our Core Values'),
        cf('about_values_subtitle', 'Core values: subtitle', 'text', 'The principles that guide everything we do'),
        cf('about_cta_heading', 'Bottom banner: heading', 'text', 'Join Our Educational Community'),
        cf('about_cta_text', 'Bottom banner: text', 'textarea', 'Discover how {site_name} can help you reach your academic and career goals'),
    ],
    'admission' => [
        cf('adm_form_eyebrow', 'Form: small label', 'text', 'Apply Online'),
        cf('adm_form_heading', 'Form: heading', 'text', 'Admission Form'),
        cf('adm_process_eyebrow', 'Process: small label', 'text', 'How It Works'),
        cf('adm_process_heading', 'Process: heading', 'text', 'Admission Process'),
        cf('adm_process_subtitle', 'Process: subtitle', 'text', 'Simple steps to join {site_name}'),
        cf('adm_fee_eyebrow', 'Fees: small label', 'text', 'Fee Structure'),
        cf('adm_fee_heading', 'Fees: heading', 'text', 'Fee Information'),
        cf('adm_fee_subtitle', 'Fees: subtitle', 'text', 'Fees for each program'),
        cf('adm_fee_note', 'Fees: note under the list', 'textarea', 'For any questions about fees, please contact the admissions office.', 'Conditions, charges not included, scholarship rules and so on.'),
        cf('adm_cta_heading', 'Bottom banner: heading', 'text', 'Need Help with Admission?'),
        cf('adm_cta_text', 'Bottom banner: text', 'textarea', 'Our admission team is here to assist you'),
    ],
    'alumni' => [
        cf('alumni_list_eyebrow', 'Alumni list: small label', 'text', 'Success Stories'),
        cf('alumni_list_heading', 'Alumni list: heading', 'text', 'Our Alumni'),
        cf('alumni_reviews_eyebrow', 'Reviews: small label', 'text', 'Testimonials'),
        cf('alumni_reviews_heading', 'Reviews: heading', 'text', 'Alumni Reviews'),
        cf('alumni_form_eyebrow', 'Registration form: small label', 'text', 'Get Involved'),
        cf('alumni_form_heading', 'Registration form: heading', 'text', 'Alumni Registration Form'),
        cf('alumni_review_form_eyebrow', 'Review form: small label', 'text', 'Share Feedback'),
        cf('alumni_review_form_heading', 'Review form: heading', 'text', 'Submit Your Review'),
    ],
    'campuses' => [
        cf('campuses_cta_heading', 'Bottom banner: heading', 'text', 'Want to Learn More?'),
        cf('campuses_cta_text', 'Bottom banner: text', 'textarea', 'Get in touch with us for admissions and details about any of our campuses'),
    ],
    'contact' => [
        cf('contact_form_eyebrow', 'Form: small label', 'text', 'Contact Form'),
        cf('contact_form_heading', 'Form: heading', 'text', 'Send us a Message'),
        cf('contact_map_eyebrow', 'Map: small label', 'text', 'Our Location'),
        cf('contact_map_heading', 'Map: heading', 'text', 'Find Us Here'),
        cf('contact_faq_eyebrow', 'FAQs: small label', 'text', 'FAQs'),
        cf('contact_faq_heading', 'FAQs: heading', 'text', 'Frequently Asked Questions'),
        cf('contact_faq_subtitle', 'FAQs: subtitle', 'text', 'Quick answers to common questions'),
    ],
    'events' => [
        cf('events_upcoming_eyebrow', 'Upcoming events: small label', 'text', 'Calendar'),
        cf('events_upcoming_heading', 'Upcoming events: heading', 'text', 'Upcoming Events'),
        cf('events_upcoming_subtitle', 'Upcoming events: subtitle', 'text', 'Mark your calendar for these exciting events'),
        cf('events_news_eyebrow', 'News: small label', 'text', 'Newsroom'),
        cf('events_news_heading', 'News: heading', 'text', 'Latest News & Announcements'),
        cf('events_news_subtitle', 'News: subtitle', 'text', 'Recent updates from {site_name}'),
        cf('events_cta_heading', 'Bottom banner: heading', 'text', 'Stay Connected!'),
        cf('events_cta_text', 'Bottom banner: text', 'textarea', 'For admission updates and questions, stay in touch with the admissions office'),
    ],
    'examination' => [
        cf('exam_datesheet_eyebrow', 'Datesheets: small label', 'text', 'Schedule'),
        cf('exam_datesheet_heading', 'Datesheets: heading', 'text', 'Exam Datesheets'),
        cf('exam_results_eyebrow', 'Results: small label', 'text', 'Results'),
        cf('exam_results_heading', 'Results: heading', 'text', 'Board Results'),
    ],
    'faculty' => [
        cf('faculty_why_eyebrow', 'Why our faculty: small label', 'text', 'What Sets Us Apart'),
        cf('faculty_why_heading', 'Why our faculty: heading', 'text', 'Why Our Faculty Stands Out'),
        cf('faculty_why_subtitle', 'Why our faculty: subtitle', 'text', 'Qualities that make our teachers exceptional'),
        cf('faculty_cta_heading', 'Bottom banner: heading', 'text', 'Study Science with Us'),
        cf('faculty_cta_text', 'Bottom banner: text', 'textarea', 'Join {site_name} and begin your journey in science'),
    ],
    'gallery' => [
        cf('gallery_videos_eyebrow', 'Videos: small label', 'text', 'Watch'),
        cf('gallery_videos_heading', 'Videos: heading', 'text', 'College Videos'),
        cf('gallery_videos_subtitle', 'Videos: subtitle', 'text', 'Events, activities and celebrations at {site_name}'),
    ],
    'home_sections' => [
        cf('home_campuses_eyebrow', 'Campuses: small label', 'text', 'Our Network'),
        cf('home_campuses_heading', 'Campuses: heading', 'text', 'Our Campuses'),
        cf('home_campuses_subtitle', 'Campuses: subtitle', 'text', 'Campus locations of {site_name}'),
    ],
];
foreach ($sectionFields as $group => $fields) {
    $registry[$group]['fields'] = array_merge($registry[$group]['fields'], $fields);
}

return $registry;
