# Fusion College of Sciences, Shakargarh — website

Public website and admin panel (AdminCP) of Fusion College of Sciences, Shakargarh. Plain PHP and
MySQL, no framework or build step. It was rebuilt from an earlier school website; the database
change for that is in `database/fusion-rebrand.sql`.

## Run it locally (XAMPP)

1. Put the folder in `htdocs` and create a MySQL database named `fusioncollege`.
2. Import a database export, then run in phpMyAdmin (SQL tab), in this order:
   `database/fusion-rebrand.sql` (removes the previous school's branding), then
   `database/fusion-content-import.sql` (the college's own content, see below).
   Always run them in this order; running the first one again needs the second one again.
3. Open `http://localhost/fusioncollege/`. Admin panel: `/AdminCP/`.

Database settings default to XAMPP (`root`, no password). On a server, copy
`includes/config.local.example.php` to `includes/config.local.php` and fill in the real values;
that file is never committed.

## Where things are edited

| What | Where |
|------|-------|
| Contact details (email, phone, address, opening hours, social pages) | AdminCP > Settings > Contact Details |
| Official website address (domain) | AdminCP > Settings > General Information |
| Page texts, banners, SEO titles and descriptions | AdminCP > Page Content |
| Logo and favicon (all browser-tab and phone icon sizes are made automatically) | AdminCP > Logo & Favicon |
| Programs, faculty, gallery, news, results, downloads | their own AdminCP pages |
| Colours and fonts | `css/style.css` (design tokens at the top: navy, royal blue, red; Manrope and Inter) |
| Admin username, email and password | AdminCP > My Account |
| Visitor statistics | AdminCP > Website Analytics (today's numbers also on the Dashboard) |

Pages that have nothing published (faculty, gallery, news...) are left out of the menus, the
sitemap and search results until they have content.

## Content from the old Fusion College website

`database/fusion-content-import.sql` holds what the college published on its previous website
(fusioncollege.edu.pk, October 2026) and its 2026 admission flyer: contact details and opening
hours, About / vision / mission texts, the chairman's message, 13 faculty members with photos,
the Boys and Girls campuses, the 2026-2028 fee and scholarship structure, required
documents, FAQs, gallery photos, the 2023 Intermediate results poster and the scholarship-test
downloads (Google Drive links). The photos are in `uploads/faculty`, `uploads/gallery`,
`uploads/results` and `images/leadership`.

## Photos and videos

- Every photo uploaded in AdminCP is resized and compressed automatically (progressive JPEG plus
  a smaller WebP copy); the gallery shows light thumbnails and opens the full photo in a viewer.
- Videos: post the video publicly on the college's Facebook page or YouTube channel and paste its
  link in AdminCP > Gallery Videos (an optional cover photo can be added). Videos stream from
  Facebook/YouTube and only load when a visitor presses play, so the site stays fast and uses
  no hosting space for them.
- Photos copied into `uploads/` by hand (or from before compression) can be optimised with
  `C:\xampp\php\php.exe tools\optimize-images.php --fix` (run it without `--fix` to see what it would do).

## WhatsApp button

Every page has a green WhatsApp button that opens a chat with the number set in AdminCP > Settings >
Social Media (it disappears when WhatsApp is switched off there). Its text and the message filled in
for the visitor are in AdminCP > Page Content > WhatsApp Button.

## Contact details are shown only once confirmed

Every contact detail has a "Confirmed by the college" switch. The website, emails and search data
use only confirmed details; anything unconfirmed stays saved but hidden. The admin dashboard
lists details still waiting. `database/fusion-rebrand.sql` never confirms anything itself.

## Official domain and SEO

Until **Official website address** is set (AdminCP > Settings > General Information, e.g.
`https://www.yourcollege.edu.pk`):

- pages have no canonical link and no `og:url`, and structured data publishes no site URL;
- share images, the sitemap, robots.txt and links in emails use the address the site is being
  served from.

Once it is set, canonical links, `og:url`, structured data, the sitemap and robots.txt all use it.
Titles follow "Page | Fusion College of Sciences, Shakargarh"; the full name is editable in Page Content.

## Before launch

- [ ] Upload the official logo (and, if wanted, a separate favicon) in AdminCP > Logo & Favicon.
      Uploads are kept in `uploads/branding/`, which a deployment never overwrites. (To make a logo the
      built-in default in the repository instead: `C:\xampp\php\php.exe tools\install-logo.php "path\to\logo.png"`.)
- [ ] Check the imported contact details, opening hours (read as 8 AM - 2 PM) and fees in AdminCP.
- [ ] Check `ADMIN_EMAIL` in `includes/config.php` (or `config.local.php`): new applications and
      messages are emailed there. Send one test inquiry on the live server and check it arrives.
- [ ] Have the college review the privacy policy (`privacy-policy.php`).
- [ ] Check the admin account's email address (`remove-old-data.sql` sets it to info@fusioncollege.pk).
- [ ] Change the admin password in AdminCP > My Account straight after launch (the Dashboard shows a
      reminder while the setup password is still in use).

## First launch on fusioncollege.edu.pk (File Manager upload)

1. Back up whatever is in `public_html` now (cPanel > Backup, or download it), then empty it.
2. cPanel > MultiPHP Manager: PHP 8.2 (8.1+ works, 7.4 minimum); Select PHP Extensions: gd, mysqli, mbstring on.
3. cPanel > SSL/TLS Status: run AutoSSL so https://fusioncollege.edu.pk and www both have a certificate
   (`.htaccess` forwards every http:// and www. address to https://fusioncollege.edu.pk).
4. cPanel > MySQL Databases: create a database and a user, add the user with ALL PRIVILEGES.
   phpMyAdmin > that database > Import: the `fusioncollege-LIVE-import-<date>.sql` export (content,
   settings and the admin account; visitor statistics start empty).
5. File Manager > public_html > Upload the `fusioncollege-LIVE-files-<date>.zip`, Extract it there
   (Settings > Show Hidden Files to see `.htaccess` and `.user.ini`).
6. Copy `includes/config.local.example.php` to `includes/config.local.php` and fill in the database
   name, user and password.
7. Open https://fusioncollege.edu.pk, log in at /AdminCP and change the password at once (My Account).
   Send yourself a message from the Contact page to check that emails arrive.
8. Google Search Console: add the domain and submit https://fusioncollege.edu.pk/sitemap.xml.

## Deploying (cPanel)

`.cpanel.yml` copies the site into `public_html` on "Deploy HEAD Commit". It never deletes files on
the server, so files removed from the repository stay there until deleted in cPanel's File Manager.
After deploying code that needs it, run `database/fusion-rebrand.sql` and then
`database/fusion-content-import.sql` in phpMyAdmin (both are safe to run again, in that order).
Once, also run `database/remove-old-gallery.sql` and then `database/remove-old-data.sql` (take a backup
first): they delete the previous school's gallery photos, hidden news, events, teachers, courses and fees,
and the old website's messages, applications and visitor statistics. Files the old school uploaded can then
be deleted in cPanel's File Manager.

All four scripts leave the college's own content alone (anything added in AdminCP after 24 October 2026,
including uploaded slides and gallery photos), so they are safe to run again.
