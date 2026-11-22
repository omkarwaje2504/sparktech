<?php
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post() && csrf_valid()) {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'publish') {
        $n = Content::publish(Content::pending(), (int)$user['id'], 'Published from dashboard');
        flash($n ? "Published $n page" . ($n > 1 ? 's' : '') . '. Your changes are now live.' : 'Nothing to publish.');
    } elseif ($action === 'discard') {
        $n = Content::discard(Content::pending());
        flash($n ? 'Unpublished changes were discarded.' : 'Nothing to discard.', 'info');
    }
    redirect('/admin/');
}

$pending = Content::pending();
$leadsTotal = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE spam = 0');
$leadsUnread = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE is_read = 0 AND spam = 0');
$leadsWeek = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE spam = 0 AND created_at > ?', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
$weekAgo = date('Y-m-d 00:00:00', strtotime('-6 days'));
$traffic = DB::row("SELECT COUNT(DISTINCT vid) AS visitors, COUNT(DISTINCT CASE WHEN is_new = 1 THEN vid END) AS new_visitors,
        COUNT(DISTINCT CASE WHEN engaged = 1 THEN vid END) AS genuine
    FROM {an_sessions} WHERE is_bot = 0 AND started_at >= ?", [$weekAgo]) ?: [];
$visitorsToday = (int)DB::value('SELECT COUNT(DISTINCT vid) FROM {an_sessions} WHERE is_bot = 0 AND started_at >= ?', [date('Y-m-d 00:00:00')]);
$liveNow = (int)DB::value('SELECT COUNT(DISTINCT vid) FROM {an_sessions} WHERE is_bot = 0 AND last_at >= ?', [date('Y-m-d H:i:s', time() - 300)]);
$mediaCount = (int)DB::value('SELECT COUNT(*) FROM {media}');
$recentLeads = DB::all('SELECT id, name, email, service, is_read, created_at FROM {leads} WHERE spam = 0 ORDER BY id DESC LIMIT 6');
$recentEdits = DB::all('SELECT doc, updated_at, published_at FROM {content_docs} ORDER BY updated_at DESC LIMIT 6');

$quick = [
    ['/', 'Home'], ['/about/', 'About'], ['/services/', 'Services'], ['/technologies/', 'Technologies'],
    ['/capabilities/', 'Capabilities'], ['/gallery/', 'Gallery'], ['/contact/', 'Contact'],
];

admin_header('Dashboard', 'dashboard');
?>
<div class="adm-hero">
  <div>
    <h2>Hello<?= $user['name'] ? ', ' . e($user['name']) : '' ?> 👋</h2>
    <p>Click <b>Edit website</b>, hover over any text or image and press the ✎ pencil to change it. Changes are saved as a draft until you press <b>Publish</b>.</p>
  </div>
  <a class="adm-btn adm-btn-primary adm-btn-lg" href="<?= e(live_edit_url('/')) ?>">✎ Edit website</a>
</div>

<?php if ($pending): ?>
<div class="adm-card adm-card-amber">
  <div class="adm-card-row">
    <div>
      <h3>You have unpublished changes</h3>
      <p class="adm-muted">Visitors still see the old version of: <?= e(implode(', ', array_map('doc_label', $pending))) ?>.</p>
    </div>
    <form method="post" class="adm-inline">
      <?= csrf_field() ?>
      <button class="adm-btn adm-btn-primary" name="action" value="publish">Publish all</button>
      <button class="adm-btn adm-btn-ghost" name="action" value="discard" data-confirm="Throw away all unpublished changes? This cannot be undone.">Discard</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="adm-stats">
  <a class="adm-stat" href="<?= e(url('/admin/analytics.php?range=today')) ?>"><b><?= $visitorsToday ?></b><span>Visitors today<?= $liveNow ? ' · ' . $liveNow . ' on the site now' : '' ?></span></a>
  <a class="adm-stat" href="<?= e(url('/admin/analytics.php?range=7d')) ?>"><b><?= (int)($traffic['visitors'] ?? 0) ?></b><span>Visitors this week</span></a>
  <a class="adm-stat" href="<?= e(url('/admin/analytics.php?range=7d&who=new#visitors')) ?>"><b><?= (int)($traffic['new_visitors'] ?? 0) ?></b><span>New visitors this week</span></a>
  <a class="adm-stat" href="<?= e(url('/admin/analytics.php?range=7d&only=genuine')) ?>"><b><?= (int)($traffic['genuine'] ?? 0) ?></b><span>Genuine visitors this week</span></a>
</div>
<div class="adm-stats">
  <a class="adm-stat" href="<?= e(url('/admin/leads.php')) ?>"><b><?= $leadsUnread ?></b><span>New enquiries</span></a>
  <a class="adm-stat" href="<?= e(url('/admin/leads.php')) ?>"><b><?= $leadsWeek ?></b><span>Enquiries this week</span></a>
  <a class="adm-stat" href="<?= e(url('/admin/leads.php')) ?>"><b><?= $leadsTotal ?></b><span>Total enquiries</span></a>
  <a class="adm-stat" href="<?= e(url('/admin/media.php')) ?>"><b><?= $mediaCount ?></b><span>Uploaded files</span></a>
</div>

<div class="adm-grid2">
  <div class="adm-card">
    <h3>Edit a page</h3>
    <div class="adm-pagelinks">
      <?php foreach ($quick as [$path, $label]): ?>
      <a href="<?= e(live_edit_url($path)) ?>"><span><?= e($label) ?></span><small>✎ Edit live</small></a>
      <?php endforeach; ?>
      <a href="<?= e(url('/admin/pages.php')) ?>"><span>All pages & services…</span><small>→</small></a>
    </div>
  </div>
  <div class="adm-card">
    <h3>Latest enquiries</h3>
    <?php if (!$recentLeads): ?>
      <p class="adm-muted">No enquiries yet. Messages sent from the contact form appear here.</p>
    <?php else: ?>
    <table class="adm-table">
      <?php foreach ($recentLeads as $l): ?>
      <tr class="<?= $l['is_read'] ? '' : 'is-unread' ?>">
        <td><a href="<?= e(url('/admin/leads.php?id=' . $l['id'])) ?>"><?= e($l['name']) ?></a><br><small class="adm-muted"><?= e($l['service'] ?: $l['email']) ?></small></td>
        <td class="adm-right adm-muted"><small><?= e(time_ago($l['created_at'])) ?></small></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="adm-card">
  <h3>Recently edited</h3>
  <?php if (!$recentEdits): ?>
    <p class="adm-muted">Nothing edited yet — the website shows its original content.</p>
  <?php else: ?>
  <table class="adm-table">
    <thead><tr><th>Content</th><th>Last edit</th><th>Published</th><th></th></tr></thead>
    <?php foreach ($recentEdits as $r): ?>
    <tr>
      <td><?= e(doc_label($r['doc'])) ?></td>
      <td><?= e(time_ago($r['updated_at'])) ?></td>
      <td><?= in_array($r['doc'], $pending, true) ? '<span class="adm-pill adm-pill-amber">Draft changes</span>' : '<span class="adm-pill adm-pill-green">Live</span>' ?></td>
      <td class="adm-right"><a href="<?= e(live_edit_url(doc_url($r['doc']))) ?>">Edit live</a> · <a href="<?= e(url('/admin/history.php?doc=' . rawurlencode($r['doc']))) ?>">History</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php admin_footer();
