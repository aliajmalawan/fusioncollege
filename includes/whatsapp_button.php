<?php
/**
 * WhatsApp button in the corner of every page: opens a WhatsApp chat with the college, with a short
 * greeting filled in. Shown while the WhatsApp link is on (Admin > Settings > Social Media); its texts
 * are edited in Admin > Page Content > WhatsApp Button.
 */
$wa_link = getSocialMedia()['whatsapp'] ?? '';
if ($wa_link === '' || $wa_link[0] === '#') {
    return;
}
$wa_message = trim(content('whatsapp_message'));
if ($wa_message !== '') {
    $wa_link .= (strpos($wa_link, '?') === false ? '?' : '&') . 'text=' . rawurlencode($wa_message);
}
$wa_label = content('whatsapp_button_label');
?>
<a class="wa-float" href="<?php echo htmlspecialchars($wa_link); ?>" target="_blank" rel="noopener" aria-label="<?php echo htmlspecialchars($wa_label); ?> (opens WhatsApp)">
    <span class="wa-float-label"><?php echo htmlspecialchars($wa_label); ?></span>
    <span class="wa-float-icon"><i class="fab fa-whatsapp" aria-hidden="true"></i></span>
</a>
