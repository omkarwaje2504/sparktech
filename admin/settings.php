<?php
require __DIR__ . '/_init.php';
$me = require_login();

$keys = ['notify_email', 'mail_from', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'ga_id', 'img_quality', 'img_max',
    'an_enabled', 'an_respect_dnt', 'geo_provider', 'an_exclude_ips', 'an_retention_days'];
// Search-console codes: accept the bare code or the whole <meta …> tag the console shows.
$verifyKeys = ['verify_google', 'verify_bing', 'verify_yandex'];

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'save') {
        foreach ($keys as $k) {
            Settings::set($k, trim(clean_text($_POST[$k] ?? '', 500)));
        }
        if (($_POST['smtp_pass'] ?? '') !== '') {
            Settings::set('smtp_pass', (string)$_POST['smtp_pass']);
        }
        foreach ($verifyKeys as $k) {
            $raw = trim((string)($_POST[$k] ?? ''));
            if (preg_match('/content\s*=\s*["\']([^"\']+)/i', $raw, $m)) {
                $raw = $m[1];
            }
            Settings::set($k, preg_replace('/[^A-Za-z0-9_\-=.]/', '', $raw));
        }
        if (($_POST['geo_token'] ?? '') !== '') {
            Settings::set('geo_token', trim((string)$_POST['geo_token']));
        } elseif (!empty($_POST['geo_token_clear'])) {
            Settings::set('geo_token', '');
        }
        flash('Settings saved.');
    } elseif ($action === 'test') {
        $to = Settings::get('notify_email', '');
        if ($to === '') {
            flash('Enter and save a notification e-mail first.', 'error');
        } elseif (Mailer::send($to, 'Test e-mail from your website', "This is a test message from the website admin.\n\nIf you received it, enquiry notifications are working.")) {
            flash("Test e-mail sent to $to. Check the inbox (and spam folder).");
        } else {
            flash('The test e-mail could not be sent. Check the SMTP settings or ask your host to enable mail().', 'error');
        }
    }
    redirect('/admin/settings.php');
}

$s = function (string $k, string $d = '') {
    return e((string)Settings::get($k, $d));
};
admin_header('Settings', 'settings');
?>
<form method="post" class="adm-form">
  <?= csrf_field() ?>
  <div class="adm-card">
    <h3>Enquiry notifications</h3>
    <p class="adm-muted adm-small">Every contact-form message is saved under <a href="<?= e(url('/admin/leads.php')) ?>">Enquiries</a>. Also e-mail them to:</p>
    <div class="adm-grid2">
      <label>Send enquiries to <input name="notify_email" value="<?= $s('notify_email') ?>" placeholder="sales@yourdomain.com, owner@yourdomain.com"></label>
      <label>“From” address <input name="mail_from" value="<?= $s('mail_from') ?>" placeholder="noreply@yourdomain.com (must be on your domain)"></label>
      <label>“From” name <input name="mail_from_name" value="<?= $s('mail_from_name', 'Website') ?>"></label>
    </div>
    <details class="adm-details" <?= Settings::get('smtp_host', '') !== '' ? 'open' : '' ?>>
      <summary>SMTP (recommended for reliable delivery)</summary>
      <p class="adm-muted adm-small">Create a mailbox in cPanel → Email Accounts, then click “Connect Devices” to see these values. Usually host <code>mail.yourdomain.com</code>, port <code>465</code>, SSL.</p>
      <div class="adm-grid2">
        <label>SMTP host <input name="smtp_host" value="<?= $s('smtp_host') ?>" placeholder="mail.yourdomain.com"></label>
        <label>Port <input name="smtp_port" value="<?= $s('smtp_port', '465') ?>"></label>
        <label>Security
          <select name="smtp_secure">
            <?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'STARTTLS (port 587)', 'none' => 'None'] as $k => $lbl): ?>
            <option value="<?= $k ?>"<?= Settings::get('smtp_secure', 'ssl') === $k ? ' selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Username <input name="smtp_user" value="<?= $s('smtp_user') ?>" autocomplete="off"></label>
        <label>Password <input type="password" name="smtp_pass" placeholder="<?= Settings::get('smtp_pass', '') !== '' ? '•••••••• (saved — leave blank to keep)' : '' ?>" autocomplete="new-password"></label>
      </div>
    </details>
  </div>
  <div class="adm-card">
    <h3>Image compression</h3>
    <p class="adm-muted adm-small">Every uploaded image is converted to <b>WebP</b>, rotated correctly, stripped of camera data and scaled down to the size below. This only affects new uploads.</p>
    <?php [$q, $mx] = Media::settings(); ?>
    <div class="adm-grid2">
      <label>Quality (40–95)
        <input type="number" name="img_quality" min="40" max="95" value="<?= (int)$q ?>">
        <small class="adm-muted">80 = recommended balance. Lower = smaller files, higher = sharper.</small>
      </label>
      <label>Max size (longest side, px)
        <input type="number" name="img_max" min="600" max="6000" step="100" value="<?= (int)$mx ?>">
        <small class="adm-muted">2400 suits full-width banners. Use 1600–1920 for even lighter pages.</small>
      </label>
    </div>
    <p class="adm-small"><b>Converter on this server:</b> <?= e(Media::engineLabel()) ?></p>
  </div>
  <div class="adm-card" id="analytics">
    <h3>Visitor analytics</h3>
    <p class="adm-muted adm-small">The website counts its own visitors, their journeys and clicks — see <a href="<?= e(url('/admin/analytics.php')) ?>">Analytics</a>. No cookies or third-party scripts are used; logged-in admins are never counted.</p>
    <input type="hidden" name="an_enabled" value="0">
    <label class="adm-check"><input type="checkbox" name="an_enabled" value="1"<?= Settings::get('an_enabled', '1') === '1' ? ' checked' : '' ?>> Count visitors (page views, clicks, journeys)</label>
    <input type="hidden" name="an_respect_dnt" value="0">
    <label class="adm-check"><input type="checkbox" name="an_respect_dnt" value="1"<?= Settings::get('an_respect_dnt', '0') === '1' ? ' checked' : '' ?>> Don't count visitors whose browser sends “Do Not Track” / Global Privacy Control</label>
    <div class="adm-grid2">
      <label>Visitor location (city / country)
        <select name="geo_provider">
          <?php foreach (['ipinfo' => 'IPinfo.io (recommended)', 'ipapi' => 'ipapi.co', 'none' => 'Off — country from the browser time zone only'] as $k => $lbl): ?>
          <option value="<?= $k ?>"<?= Settings::get('geo_provider', 'ipinfo') === $k ? ' selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
        <small class="adm-muted">Each network is looked up once and remembered for 30 days. Free without a key up to ~1,000 lookups a day; a free IPinfo account token raises that to 50,000 a month. If the site runs behind Cloudflare, its location headers are used first.</small>
      </label>
      <label>Location service token (optional)
        <input type="password" name="geo_token" autocomplete="off" placeholder="<?= Settings::get('geo_token', '') !== '' ? '•••••••• (saved — leave blank to keep)' : 'e.g. from ipinfo.io/account/token' ?>">
        <?php if (Settings::get('geo_token', '') !== ''): ?><span class="adm-small"><input type="checkbox" name="geo_token_clear" value="1"> remove saved token</span><?php endif; ?>
      </label>
      <label>Don't count these IP addresses
        <textarea name="an_exclude_ips" rows="3" placeholder="One per line. End with * for a range, e.g. 203.0.113.*"><?= $s('an_exclude_ips') ?></textarea>
        <small class="adm-muted">Your current IP address: <code><?= e(request_ip()) ?></code> — add your office network so staff visits are not counted.</small>
      </label>
      <label>Keep analytics data for (days)
        <input type="number" name="an_retention_days" min="30" max="3650" value="<?= (int)Settings::get('an_retention_days', '730') ?>">
        <small class="adm-muted">Older visits are deleted automatically. Enquiries are never deleted by this.</small>
      </label>
    </div>
    <label>Google Analytics measurement ID (optional, in addition) <input name="ga_id" value="<?= $s('ga_id') ?>" placeholder="G-XXXXXXXXXX"></label>
  </div>
  <div class="adm-card" id="seo">
    <h3>Search engines</h3>
    <p class="adm-muted adm-small">Prove you own the website to Google Search Console and Bing Webmaster Tools, then submit <code><?= e(abs_url('/sitemap.xml')) ?></code> there. Paste the code or the whole <code>&lt;meta …&gt;</code> tag they give you (choose the “HTML tag” method).</p>
    <div class="adm-grid2">
      <label>Google Search Console code <input name="verify_google" value="<?= $s('verify_google') ?>" placeholder="e.g. abcDEF123…"></label>
      <label>Bing Webmaster code <input name="verify_bing" value="<?= $s('verify_bing') ?>" placeholder="e.g. 1A2B3C…"></label>
      <label>Yandex Webmaster code <input name="verify_yandex" value="<?= $s('verify_yandex') ?>"></label>
    </div>
    <p class="adm-small">Page titles, descriptions and keywords are edited on each page: <b>Edit website → Page settings (SEO)</b>.</p>
  </div>
  <div class="adm-actions">
    <button class="adm-btn adm-btn-primary" name="action" value="save">Save settings</button>
    <button class="adm-btn adm-btn-ghost" name="action" value="test">Send test e-mail</button>
  </div>
</form>
<div class="adm-card">
  <h3>System</h3>
  <dl class="adm-dl">
    <dt>PHP version</dt><dd><?= e(PHP_VERSION) ?></dd>
    <dt>Database</dt><dd><?= e(DB::driver()) ?></dd>
    <dt>Max upload size</dt><dd><?= e(ini_get('upload_max_filesize')) ?> (change in cPanel → MultiPHP INI Editor)</dd>
    <dt>WebP conversion</dt><dd><?= e(Media::engineLabel()) ?></dd>
    <dt>Installer</dt><dd><?= is_file(ST_ROOT . '/install.php') ? '<b style="color:#b45309">install.php still exists — delete it from the server.</b>' : 'Removed ✓' ?></dd>
  </dl>
</div>
<?php admin_footer();
