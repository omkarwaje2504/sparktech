<?php
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        DB::exec('DELETE FROM {leads} WHERE id = ?', [$id]);
        flash('Enquiry deleted.', 'info');
        redirect('/admin/leads.php');
    } elseif ($action === 'unread' && $id) {
        DB::update('leads', ['is_read' => 0], 'id = ?', [$id]);
        redirect('/admin/leads.php');
    } elseif ($action === 'not_spam' && $id) {
        DB::update('leads', ['spam' => 0, 'is_read' => 0], 'id = ?', [$id]);
        $lead = DB::row('SELECT visitor_id, session_id, page FROM {leads} WHERE id = ?', [$id]);
        if ($lead && $lead['visitor_id'] !== '') {
            Analytics::attachLead($id, $lead['visitor_id'], $lead['session_id'], $lead['page']);
        }
        flash('Moved to the inbox.');
        redirect('/admin/leads.php?id=' . $id);
    } elseif ($action === 'spam' && $id) {
        DB::update('leads', ['spam' => 1, 'is_read' => 1, 'spam_reasons' => 'marked as spam by ' . ($user['username'] ?? 'admin')], 'id = ?', [$id]);
        flash('Moved to Filtered.', 'info');
        redirect('/admin/leads.php');
    } elseif ($action === 'read_all') {
        DB::exec('UPDATE {leads} SET is_read = 1');
        flash('All enquiries marked as read.');
        redirect('/admin/leads.php');
    }
}

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Date', 'Name', 'E-mail', 'Phone', 'Company', 'Service', 'Message', 'Page', 'Found us via', 'Campaign', 'Location', 'Filtered as spam']);
    $visits = [];
    foreach (DB::all('SELECT sid, source, medium, campaign, city, region, country FROM {an_sessions} WHERE lead_id IS NOT NULL') as $s) {
        $visits[$s['sid']] = $s;
    }
    foreach (DB::all('SELECT * FROM {leads} ORDER BY id DESC') as $l) {
        $s = $visits[$l['session_id']] ?? null;
        $row = [$l['id'], $l['created_at'], $l['name'], $l['email'], $l['phone'], $l['company'], $l['service'], $l['message'], $l['page'],
            $s ? Analytics::sourceLabel($s['source'], $s['medium']) : '', $s['campaign'] ?? '',
            $s ? Analytics::placeLabel($s['city'], $s['region'], $s['country']) : '', $l['spam'] ? 'yes: ' . $l['spam_reasons'] : ''];
        // Neutralise spreadsheet formula injection.
        $row = array_map(function ($v) { return preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v; }, $row);
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $lead = DB::row('SELECT * FROM {leads} WHERE id = ?', [$id]);
    if (!$lead) {
        redirect('/admin/leads.php');
    }
    if (!$lead['is_read']) {
        DB::update('leads', ['is_read' => 1], 'id = ?', [$id]);
    }
    $visit = $lead['session_id'] !== '' ? Analytics::session($lead['session_id']) : null;
    $visitor = $lead['visitor_id'] !== '' ? DB::row('SELECT * FROM {an_visitors} WHERE vid = ?', [$lead['visitor_id']]) : null;
    $journey = $visit ? DB::all("SELECT type, path, title, label, seconds FROM {an_events} WHERE sid = ? AND type IN ('view', 'lead') ORDER BY id", [$visit['sid']]) : [];
    admin_header('Enquiry from ' . $lead['name'], 'leads');
    ?>
    <p><a href="<?= e(url('/admin/leads.php' . ($lead['spam'] ? '?box=filtered' : ''))) ?>">← All enquiries</a></p>
    <?php if ($lead['spam']): ?>
    <div class="adm-card adm-card-amber">
      <div class="adm-card-row">
        <div><h3>Filtered as possible spam</h3><p class="adm-muted">Why: <?= e($lead['spam_reasons'] ?: 'marked by an admin') ?>. No notification e-mail was sent.</p></div>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="adm-btn adm-btn-primary" name="action" value="not_spam">Not spam — move to inbox</button></form>
      </div>
    </div>
    <?php endif; ?>
    <div class="adm-card">
      <dl class="adm-dl">
        <dt>Received</dt><dd><?= e(date('d M Y, H:i', strtotime($lead['created_at']))) ?></dd>
        <dt>Name</dt><dd><?= e($lead['name']) ?></dd>
        <dt>E-mail</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd>
        <dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $lead['phone'])) ?>"><?= e($lead['phone']) ?></a></dd>
        <dt>Company</dt><dd><?= e($lead['company'] ?: '—') ?></dd>
        <dt>Service</dt><dd><?= e($lead['service'] ?: '—') ?></dd>
        <dt>Sent from</dt><dd><?= e($lead['page'] ?: '—') ?></dd>
        <dt>Message</dt><dd class="adm-message"><?= nl2br(e($lead['message'])) ?></dd>
      </dl>
      <?php if ($visit || $visitor): ?>
      <h3 class="an-lead-journey">How they found you</h3>
      <dl class="adm-dl">
        <?php if ($visit): ?>
        <dt>Came from</dt><dd><?= e(Analytics::sourceLabel($visit['source'], $visit['medium'])) ?><?= $visit['campaign'] !== '' ? ' · campaign “' . e($visit['campaign']) . '”' : '' ?></dd>
        <dt>Location</dt><dd><?= Analytics::countryBadge((string)$visit['country']) ?> <?= e(Analytics::placeLabel($visit['city'], $visit['region'], $visit['country']) ?: 'Unknown') ?></dd>
        <dt>Device</dt><dd><?= e($visit['device'] . ' · ' . $visit['os'] . ' · ' . $visit['browser']) ?></dd>
        <?php endif; ?>
        <?php if ($visitor): ?>
        <dt>Visitor history</dt><dd><?= (int)$visitor['sessions'] ?> visit<?= (int)$visitor['sessions'] === 1 ? '' : 's' ?> since <?= e(date('d M Y', strtotime($visitor['first_seen']))) ?> · <?= (int)$visitor['pageviews'] ?> pages viewed</dd>
        <?php endif; ?>
        <?php if ($journey): ?>
        <dt>Pages this visit</dt><dd><ol class="an-mini-path">
          <?php foreach ($journey as $j): ?>
          <li><?= $j['type'] === 'lead' ? '<b>✉ Sent this enquiry</b>' : e(preg_replace('/\s*\|.*$/', '', $j['title']) ?: $j['path']) . ((int)$j['seconds'] > 0 ? ' <small class="adm-muted">(' . (int)$j['seconds'] . 's)</small>' : '') ?></li>
          <?php endforeach; ?>
        </ol></dd>
        <?php endif; ?>
      </dl>
      <p><a class="adm-btn adm-btn-ghost adm-btn-sm" href="<?= e(url('/admin/analytics.php?vid=' . rawurlencode($lead['visitor_id']))) ?>">Full click-by-click journey →</a></p>
      <?php endif; ?>
      <div class="adm-actions">
        <a class="adm-btn adm-btn-primary" href="mailto:<?= e($lead['email']) ?>?subject=<?= rawurlencode('Re: Your enquiry' . ($lead['service'] ? ' — ' . $lead['service'] : '')) ?>">Reply by e-mail</a>
        <?php $wa = preg_replace('/[^0-9]/', '', $lead['phone']); if (strlen($wa) >= 10): ?>
        <a class="adm-btn adm-btn-ghost" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
        <?php endif; ?>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <button class="adm-btn adm-btn-ghost" name="action" value="unread">Mark unread</button>
          <?php if (!$lead['spam']): ?><button class="adm-btn adm-btn-ghost" name="action" value="spam">Spam</button><?php endif; ?>
          <button class="adm-btn adm-btn-danger" name="action" value="delete" data-confirm="Delete this enquiry permanently?">Delete</button>
        </form>
      </div>
    </div>
    <?php
    admin_footer();
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$box = ($_GET['box'] ?? '') === 'filtered' ? 'filtered' : 'inbox';
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 25;
$where = $box === 'filtered' ? 'spam = 1' : 'spam = 0';
$params = [];
if ($q !== '') {
    $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR service LIKE ? OR message LIKE ?)';
    $params = array_fill(0, 6, '%' . $q . '%');
}
$filteredCount = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE spam = 1');
$total = (int)DB::value("SELECT COUNT(*) FROM {leads} WHERE $where", $params);
$leads = DB::all("SELECT * FROM {leads} WHERE $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
$pages = max(1, (int)ceil($total / $per));

admin_header('Enquiries', 'leads');
?>
<div class="adm-tabs">
  <a href="<?= e(url('/admin/leads.php')) ?>" class="<?= $box === 'inbox' ? 'is-active' : '' ?>">Inbox</a>
  <a href="<?= e(url('/admin/leads.php?box=filtered')) ?>" class="<?= $box === 'filtered' ? 'is-active' : '' ?>">Filtered<?= $filteredCount ? ' (' . $filteredCount . ')' : '' ?></a>
</div>
<?php if ($box === 'filtered'): ?>
<p class="adm-muted adm-small">Enquiries that passed the form checks but look like spam (spam words, links, gibberish, filled in seconds, no human activity on the page). They are kept here without an e-mail notification — open one and press <b>Not spam</b> if it is genuine.</p>
<?php endif; ?>
<div class="adm-toolbar">
  <form method="get" class="adm-search"><?php if ($box === 'filtered'): ?><input type="hidden" name="box" value="filtered"><?php endif; ?><input name="q" value="<?= e($q) ?>" placeholder="Search name, e-mail, message…"><button class="adm-btn">Search</button></form>
  <div class="adm-inline">
    <form method="post" class="adm-inline"><?= csrf_field() ?><button class="adm-btn adm-btn-ghost" name="action" value="read_all">Mark all read</button></form>
    <a class="adm-btn adm-btn-ghost" href="?export=csv">Download CSV</a>
  </div>
</div>
<div class="adm-card adm-card-flush">
  <?php if (!$leads): ?>
    <p class="adm-muted adm-pad">No enquiries<?= $q !== '' ? ' match your search' : ' yet' ?>.</p>
  <?php else: ?>
  <table class="adm-table adm-table-click">
    <thead><tr><th>Name</th><th>Service</th><th>Contact</th><th><?= $box === 'filtered' ? 'Why filtered' : 'Received' ?></th></tr></thead>
    <?php foreach ($leads as $l): ?>
    <tr class="<?= $l['is_read'] ? '' : 'is-unread' ?>" data-href="<?= e(url('/admin/leads.php?id=' . $l['id'])) ?>">
      <td><a href="<?= e(url('/admin/leads.php?id=' . $l['id'])) ?>"><?= e($l['name']) ?></a><?= $l['company'] ? '<br><small class="adm-muted">' . e($l['company']) . '</small>' : '' ?></td>
      <td><?= e($l['service'] ?: '—') ?><br><small class="adm-muted"><?= e(mb_strimwidth($l['message'], 0, 70, '…')) ?></small></td>
      <td><small><?= e($l['email']) ?><br><?= e($l['phone']) ?></small></td>
      <td><small><?= e(date('d M Y H:i', strtotime($l['created_at']))) ?></small><?= $box === 'filtered' ? '<br><small class="adm-muted">' . e($l['spam_reasons']) . '</small>' : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php if ($pages > 1): ?>
<div class="adm-pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
  <a href="?p=<?= $i ?>&q=<?= rawurlencode($q) ?><?= $box === 'filtered' ? '&box=filtered' : '' ?>" class="<?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php admin_footer();
