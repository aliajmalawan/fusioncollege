<?php
/**
 * Branded HTML emails (admission and contact notifications).
 *
 * Every email the website sends uses the same layout as the site: the college logo
 * and name on the navy header, a red accent line, the content on a light
 * background and the college's contact details in a navy footer.
 * Styles are inline and the layout is table-based, because most email apps
 * ignore <style> blocks and modern CSS layout.
 */

// Brand palette (same values as the tokens in css/style.css)
const EMAIL_NAVY = '#0B1F4D';
const EMAIL_ROYAL = '#1456C0';
const EMAIL_ROYAL_LIGHT = '#8DB3F0';   // text and links on navy
const EMAIL_RED = '#E31E24';
const EMAIL_RED_SOFT = '#FDECEC';
const EMAIL_CREAM = '#F4F7FC';
const EMAIL_SOFT = '#E8EFFB';
const EMAIL_BORDER = '#D9E2F0';
const EMAIL_TEXT = '#172033';
const EMAIL_MUTED = '#64748B';
const EMAIL_FONT = "Inter, 'Segoe UI', Helvetica, Arial, sans-serif";   // Inter where installed, like the website

/** Absolute URL of the site root, e.g. https://example.com (emails can't use relative links). */
function emailSiteUrl() {
    return publicBaseUrl();
}

/**
 * Wrap content in the branded email layout.
 * $heading is plain text; $content_html is already-escaped HTML.
 */
function brandEmail($heading, $content_html) {
    $site = htmlspecialchars(getSiteFullName());
    $base = emailSiteUrl();
    $logo = htmlspecialchars($base . '/' . getLogoSmallPath());
    $logo_attrs = logoImgAttrs(getLogoSmallPath(), 52);
    $address = nl2br(htmlspecialchars(getSiteAddress()));
    $phone = htmlspecialchars(getSitePhone());
    $email = htmlspecialchars(getSiteEmail());
    $hours = htmlspecialchars(getOfficeHours());
    $heading = htmlspecialchars($heading);
    $year = date('Y');

    $contact_bits = [];
    if ($phone !== '') { $contact_bits[] = $phone; }
    if (hasSiteEmail()) { $contact_bits[] = '<a href="mailto:' . $email . '" style="color:' . EMAIL_ROYAL_LIGHT . ';text-decoration:none;">' . $email . '</a>'; }
    $contact_line = implode(' &nbsp;&middot;&nbsp; ', $contact_bits);

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $heading . '</title></head>'
        . '<body style="margin:0;padding:0;background:' . EMAIL_CREAM . ';font-family:' . EMAIL_FONT . ';color:' . EMAIL_TEXT . ';">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . EMAIL_CREAM . ';padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border:1px solid ' . EMAIL_BORDER . ';border-radius:12px;overflow:hidden;">'
        // Header: logo + college name
        . '<tr><td style="background:' . EMAIL_NAVY . ';padding:22px 28px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="padding-right:14px;vertical-align:middle;"><img src="' . $logo . '"' . $logo_attrs . ' alt="' . $site . ' logo" style="display:block;"></td>'
        . '<td style="vertical-align:middle;"><div style="font-size:18px;font-weight:700;color:#FFFFFF;line-height:1.3;">' . $site . '</div>'
        . '<div style="font-size:13px;color:' . EMAIL_ROYAL_LIGHT . ';">' . htmlspecialchars(trim((string) strtok(getSiteAddress(), "\n"))) .'</div></td>'
        . '</tr></table></td></tr>'
        // Red accent line
        . '<tr><td style="height:4px;background:' . EMAIL_RED . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
        // Body
        . '<tr><td style="padding:30px 28px 26px;font-size:15px;line-height:1.65;color:' . EMAIL_TEXT . ';">'
        . '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:' . EMAIL_NAVY . ';">' . $heading . '</h1>'
        . $content_html
        . '</td></tr>'
        // Footer
        . '<tr><td style="background:' . EMAIL_NAVY . ';border-top:3px solid ' . EMAIL_RED . ';padding:20px 28px;font-size:12.5px;line-height:1.7;color:#C9D6EE;text-align:center;">'
        . '<div style="font-weight:700;color:#FFFFFF;font-size:14px;">' . $site . '</div>'
        . '<div>' . $address . '</div>'
        . ($contact_line !== '' ? '<div>' . $contact_line . '</div>' : '')
        . ($hours !== '' ? '<div>Office hours: ' . $hours . '</div>' : '')
        . '<div style="margin-top:8px;"><a href="' . htmlspecialchars($base) . '/" style="color:' . EMAIL_ROYAL_LIGHT . ';text-decoration:none;">' . htmlspecialchars(preg_replace('#^https?://#', '', $base)) . '</a> &nbsp;&middot;&nbsp; &copy; ' . $year . '</div>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Two-column details table; values are plain text (escaped here), labels too. */
function emailInfoTable(array $rows) {
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;border:1px solid ' . EMAIL_BORDER . ';border-radius:8px;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:10px 14px;width:38%;background:' . EMAIL_SOFT . ';border-bottom:1px solid ' . EMAIL_BORDER . ';font-weight:600;font-size:14px;color:' . EMAIL_NAVY . ';vertical-align:top;">' . htmlspecialchars($label) . '</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid ' . EMAIL_BORDER . ';font-size:14px;vertical-align:top;">' . nl2br(htmlspecialchars((string) $value)) . '</td>'
            . '</tr>';
    }
    return $html . '</table>';
}

/** Highlighted note. $tone: 'success' (royal blue) or 'action' (red). $html is already-escaped HTML. */
function emailNote($html, $tone = 'success') {
    $bar = $tone === 'action' ? EMAIL_RED : EMAIL_ROYAL;
    $bg = $tone === 'action' ? EMAIL_RED_SOFT : EMAIL_SOFT;
    return '<div style="margin:18px 0;padding:14px 16px;background:' . $bg . ';border-left:4px solid ' . $bar . ';border-radius:6px;font-size:14.5px;">' . $html . '</div>';
}

/**
 * The domain emails are sent from: the official website address (Admin > Settings), else the domain
 * being served. Never taken from the request alone, because a visitor's browser chooses that header.
 */
function emailDomain() {
    $host = (string) parse_url(publicBaseUrl(), PHP_URL_HOST);
    $host = preg_replace('/^www\./', '', strtolower($host));
    return preg_match('/^[a-z0-9.-]+$/', $host) && $host !== '' ? $host : 'localhost';
}

/** Mail headers shared by every site email. */
function emailHeaders($reply_to = '') {
    $host = emailDomain();
    $from_name = str_replace(["\r", "\n", '"'], '', getSiteName());
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= 'From: ' . $from_name . ' <noreply@' . $host . ">\r\n";
    $reply_to = str_replace(["\r", "\n"], '', (string) $reply_to);
    if ($reply_to !== '' && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $reply_to . "\r\n";
    }
    return $headers;
}

/**
 * Send one branded email and report whether the mail server accepted it. A failure is
 * logged (never shown), so pages can tell visitors honestly whether a confirmation was sent;
 * the submission itself is always saved in the database before any email is tried.
 */
function sendSiteMail($to, $subject, $html, $headers) {
    // -f sets the envelope sender to the same noreply@ address as "From", so mail providers see the
    // college's own domain throughout (fewer messages in spam). @: a missing mail server must not break the page
    $domain = emailDomain();
    $sent = $domain !== 'localhost'
        ? @mail($to, $subject, $html, $headers, '-fnoreply@' . $domain)
        : @mail($to, $subject, $html, $headers);
    if (!$sent) {
        error_log('Website email not sent (' . $subject . ') to ' . $to);
    }
    return $sent;
}
