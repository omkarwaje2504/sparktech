<?php
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_require();
    $rev = DB::row('SELECT * FROM {revisions} WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
    if ($rev && Content::validName($rev['doc'])) {
        $data = json_decode($rev['data'], true);
        if (is_array($data)) {
            Content::saveDraft($rev['doc'], $data, (int)$user['id']);
            flash('Version from ' . date('d M Y H:i', strtotime($rev['created_at'])) . ' restored as a draft. Check it on the website, then Publish.');
            redirect('/admin/edit.php?on=1&_csrf=' . csrf_token() . '&return=' . rawurlencode(doc_url($rev['doc'])));
        }
    }
    flash('Could not restore that version.', 'error');
    redirect('/admin/history.php');
}

$doc = (string)($_GET['doc'] ?? '');
$params = [];
$where = '';
if ($doc !== '' && Content::validName($doc)) {
    $where = 'WHERE r.doc = ?';
    $params[] = $doc;
}
$rows = DB::all("SELECT r.id, r.doc, r.note, r.created_at, u.username, u.name FROM {revisions} r LEFT JOIN {users} u ON u.id = r.user_id $where ORDER BY r.id DESC LIMIT 200", $params);
$docs = DB::all('SELECT DISTINCT doc FROM {revisions} ORDER BY doc');

admin_header('History', 'history');
?>
<div class="adm-card">
  <p class="adm-muted">Every time you publish, the website keeps a copy of the previous version. Restore any version to undo changes — it opens as a draft so you can check it before publishing.</p>
  <form method="get" class="adm-inline">
    <select name="doc" onchange="this.form.submit()">
      <option value="">All content</option>
      <?php foreach ($docs as $d): ?><option value="<?= e($d['doc']) ?>"<?= $d['doc'] === $doc ? ' selected' : '' ?>><?= e(doc_label($d['doc'])) ?></option><?php endforeach; ?>
    </select>
  </form>
</div>
<div class="adm-card adm-card-flush">
  <?php if (!$rows): ?>
    <p class="adm-muted adm-pad">No history yet. Versions appear here after you publish changes.</p>
  <?php else: ?>
  <table class="adm-table">
    <thead><tr><th>When</th><th>Content</th><th>Event</th><th>By</th><th></th></tr></thead>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?><br><small class="adm-muted"><?= e(time_ago($r['created_at'])) ?></small></td>
      <td><?= e(doc_label($r['doc'])) ?></td>
      <td><small><?= e($r['note']) ?></small></td>
      <td><small><?= e($r['name'] ?: ($r['username'] ?: '—')) ?></small></td>
      <td class="adm-right">
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="adm-btn adm-btn-sm" data-confirm="Restore this version as a draft?">Restore</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php admin_footer();
