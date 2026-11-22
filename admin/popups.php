<?php
/**
 * Popups: small designed windows (HTML + CSS) that open when a visitor clicks
 * any link pointing to "#popup-{id}". Stored in the global "site" doc.
 */
require __DIR__ . '/_init.php';
$user = require_login();

$data = Content::draftForEdit('site');
$popups = is_array($data['popups'] ?? null) ? array_values($data['popups']) : [];
$find = function (string $id) use ($popups) {
    foreach ($popups as $i => $p) {
        if (($p['id'] ?? '') === $id) {
            return $i;
        }
    }
    return null;
};

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'create') {
        $title = trim(clean_text($_POST['title'] ?? '', 120));
        $id = slugify((string)($_POST['id'] ?? '') ?: $title);
        if ($title === '' || $id === '') {
            flash('Enter a name for the popup.', 'error');
            redirect('/admin/popups.php');
        }
        if ($find($id) !== null) {
            flash('A popup called "' . $id . '" already exists.', 'error');
            redirect('/admin/popups.php');
        }
        $popups[] = [
            'id' => $id, 'title' => $title, 'width' => 'md',
            'html' => "<div class=\"p-8 sm:p-10\">\n  <h2 class=\"text-3xl font-bold text-gray-900\">" . e($title) . "</h2>\n  <p class=\"mt-3 text-gray-600\">Write your content here.</p>\n  <a href=\"/contact/\" class=\"mt-6 inline-block rounded-full bg-sky-500 px-6 py-3 font-semibold text-white hover:bg-sky-400\">Send Your Enquiry</a>\n</div>",
            'css' => '',
        ];
        $data['popups'] = $popups;
        Content::saveDraft('site', $data, (int)$user['id']);
        flash('Popup created. Design it below, then Save.');
        redirect('/admin/popups.php?id=' . rawurlencode($id));
    }
    if ($action === 'save' || $action === 'save_publish') {
        $orig = (string)($_POST['orig_id'] ?? '');
        $i = $find($orig);
        if ($i === null) {
            flash('Popup not found.', 'error');
            redirect('/admin/popups.php');
        }
        $id = slugify((string)($_POST['id'] ?? '')) ?: $orig;
        if ($id !== $orig && $find($id) !== null) {
            flash('Another popup already uses the name "' . $id . '".', 'error');
            redirect('/admin/popups.php?id=' . rawurlencode($orig));
        }
        $popups[$i] = [
            'id' => $id,
            'title' => trim(clean_text($_POST['title'] ?? '', 120)) ?: $id,
            'width' => in_array($_POST['width'] ?? '', ['sm', 'md', 'lg', 'xl'], true) ? $_POST['width'] : 'md',
            'html' => clean_html_trusted($_POST['html'] ?? ''),
            'css' => clean_css($_POST['css'] ?? ''),
        ];
        $data['popups'] = $popups;
        Content::saveDraft('site', $data, (int)$user['id']);
        if ($action === 'save_publish') {
            Content::publish(['site'], (int)$user['id'], 'Popup "' . $id . '" published');
            flash('Popup saved and published — it is live.');
        } else {
            flash('Popup saved as a draft. Press "Save & publish" (or Publish in the editor) to make it live.');
        }
        if ($id !== $orig) {
            flash('Popup renamed — update links from #popup-' . $orig . ' to #popup-' . $id . '.', 'info');
        }
        redirect('/admin/popups.php?id=' . rawurlencode($id));
    }
    if ($action === 'delete') {
        $i = $find((string)($_POST['id'] ?? ''));
        if ($i !== null) {
            array_splice($popups, $i, 1);
            $data['popups'] = $popups;
            Content::saveDraft('site', $data, (int)$user['id']);
            flash('Popup deleted (draft). Publish to remove it from the live site.', 'info');
        }
        redirect('/admin/popups.php');
    }
}

$editId = (string)($_GET['id'] ?? '');
$current = $editId !== '' && $find($editId) !== null ? $popups[$find($editId)] : null;

admin_header($current ? 'Popup: ' . ($current['title'] ?? $editId) : 'Popups', 'popups');

if ($current): ?>
<p><a href="<?= e(url('/admin/popups.php')) ?>">← All popups</a></p>
<form method="post" class="adm-form adm-popup-editor" id="popup-form">
  <?= csrf_field() ?>
  <input type="hidden" name="orig_id" value="<?= e($current['id']) ?>">
  <div class="adm-card">
    <div class="adm-grid3">
      <label>Name <input name="title" value="<?= e($current['title'] ?? '') ?>" required></label>
      <label>Link name (used in <code>#popup-…</code>) <input name="id" value="<?= e($current['id']) ?>" pattern="[a-z0-9-]+" required></label>
      <label>Width
        <select name="width" data-preview-width>
          <?php foreach (['sm' => 'Small (420px)', 'md' => 'Medium (640px)', 'lg' => 'Large (900px)', 'xl' => 'Extra large (1200px)'] as $k => $lbl): ?>
          <option value="<?= $k ?>"<?= ($current['width'] ?? 'md') === $k ? ' selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <p class="adm-small adm-muted" style="margin:0">To open this popup, make any button or link on the website point to
      <code>#popup-<?= e($current['id']) ?></code> — pick it in the link list while editing. Add <code>data-popup-close</code> to a button inside to close the popup.
      <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" data-copy="#popup-<?= e($current['id']) ?>">Copy link</button></p>
  </div>
  <div class="adm-split">
    <div class="adm-card">
      <div class="adm-card-row"><h3>HTML</h3>
        <select data-template class="adm-small">
          <option value="">Insert a starter design…</option>
          <option value="contact">Contact options</option>
          <option value="imagetext">Image + text</option>
          <option value="video">YouTube video</option>
          <option value="list">Checklist + button</option>
        </select>
      </div>
      <textarea name="html" class="adm-code" rows="18" spellcheck="false" wrap="off"><?= e($current['html'] ?? '') ?></textarea>
      <h3 style="margin-top:16px">CSS <small class="adm-muted">(only affects this popup)</small></h3>
      <textarea name="css" class="adm-code" rows="8" spellcheck="false" wrap="off"><?= e($current['css'] ?? '') ?></textarea>
      <p class="adm-small adm-muted">Scripts are removed for safety. Classes used on the website (and common ones like <code>p-6 rounded-2xl bg-sky-500 text-white grid md:grid-cols-2</code>) work, or write your own CSS above.</p>
    </div>
    <div class="adm-card adm-preview-card">
      <div class="adm-card-row"><h3>Live preview</h3><span class="adm-small adm-muted">Updates as you type</span></div>
      <iframe class="adm-preview" title="Popup preview" data-preview
              data-css="<?= e(asset('assets/css/site.css')) ?>"
              data-fonts="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap"></iframe>
    </div>
  </div>
  <div class="adm-actions">
    <button class="adm-btn adm-btn-primary" name="action" value="save_publish">Save &amp; publish</button>
    <button class="adm-btn" name="action" value="save">Save as draft</button>
    <a class="adm-btn adm-btn-ghost" href="<?= e(url('/#popup-' . $current['id'])) ?>" target="_blank">Open on website ↗</a>
  </div>
</form>
<script>
(function () {
  var form = document.getElementById('popup-form');
  var frame = form.querySelector('[data-preview]');
  var html = form.querySelector('[name=html]'), css = form.querySelector('[name=css]'), width = form.querySelector('[name=width]');
  var widths = { sm: 420, md: 640, lg: 900, xl: 1200 };
  var timer;
  function render() {
    var doc = '<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="' + frame.dataset.fonts + '">' +
      '<link rel="stylesheet" href="' + new URL(frame.dataset.css, location.href).href + '"><style>body{margin:0;padding:24px;background:#334155;min-height:100vh;box-sizing:border-box;font-family:Inter,system-ui,sans-serif}' +
      '.wrap{margin:0 auto;max-width:' + widths[width.value] + 'px;background:#fff;border-radius:28px;overflow:hidden;box-shadow:0 40px 100px rgba(0,0,0,.45)}' +
      '.st-popup-body{' + css.value.replace(/<\/?style/gi, '') + '}</style></head><body><div class="wrap"><div class="st-popup-body">' +
      html.value.replace(/<script[\s\S]*?<\/script>/gi, '') + '</div></div></body></html>';
    frame.setAttribute('sandbox', 'allow-same-origin'); // styles load, scripts never run
    frame.srcdoc = doc;
  }
  [html, css].forEach(function (t) {
    t.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(render, 250); });
    t.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      e.preventDefault();
      var s = t.selectionStart; t.value = t.value.slice(0, s) + '  ' + t.value.slice(t.selectionEnd); t.selectionStart = t.selectionEnd = s + 2;
    });
  });
  width.addEventListener('change', render);
  var templates = {
    contact: '<div class="p-8 text-center sm:p-10">\n  <h2 class="text-3xl font-bold text-gray-900">How can we help?</h2>\n  <p class="mt-3 text-gray-600">Choose how you would like to reach our engineers.</p>\n  <div class="mt-8 grid gap-3 sm:grid-cols-3">\n    <a href="tel:+918850058985" class="rounded-2xl border border-gray-200 px-4 py-4 font-semibold text-gray-800 hover:border-sky-400">Call us</a>\n    <a href="https://wa.me/919820513136" class="rounded-2xl border border-gray-200 px-4 py-4 font-semibold text-gray-800 hover:border-emerald-400">WhatsApp</a>\n    <a href="/contact/" class="rounded-2xl bg-sky-500 px-4 py-4 font-semibold text-white hover:bg-sky-400">Enquiry form</a>\n  </div>\n</div>',
    imagetext: '<div class="grid md:grid-cols-2">\n  <img src="/images/about-us/who-we-are.webp" alt="" class="h-full w-full object-cover">\n  <div class="p-8">\n    <h2 class="text-2xl font-bold text-gray-900">Title</h2>\n    <p class="mt-3 text-gray-600">Describe this item.</p>\n    <a href="/contact/" class="mt-6 inline-block rounded-full bg-sky-500 px-6 py-3 font-semibold text-white hover:bg-sky-400">Get a quote</a>\n  </div>\n</div>',
    video: '<div class="aspect-video">\n  <iframe class="h-full w-full" src="https://www.youtube.com/embed/VIDEO_ID" title="Video" allowfullscreen></iframe>\n</div>',
    list: '<div class="p-8 sm:p-10">\n  <h2 class="text-2xl font-bold text-gray-900">Why choose us</h2>\n  <ul class="mt-5 grid gap-3">\n    <li class="rounded-xl bg-sky-50 px-4 py-3 text-gray-800">✓ One EPC partner from concept to commissioning</li>\n    <li class="rounded-xl bg-sky-50 px-4 py-3 text-gray-800">✓ Projects in 15+ countries</li>\n    <li class="rounded-xl bg-sky-50 px-4 py-3 text-gray-800">✓ Commissioning &amp; operator training</li>\n  </ul>\n  <button type="button" data-popup-close class="mt-6 rounded-full bg-gray-900 px-6 py-3 font-semibold text-white">Close</button>\n</div>'
  };
  form.querySelector('[data-template]').addEventListener('change', function () {
    if (!this.value) return;
    if (html.value.trim() && !confirm('Replace the current HTML with this starter design?')) { this.value = ''; return; }
    html.value = templates[this.value]; this.value = ''; render();
  });
  render();
})();
</script>
<?php else: ?>
<div class="adm-card">
  <p class="adm-muted" style="margin-top:0">A popup is a small window you design with HTML. It opens when a visitor clicks any button or link that points to <code>#popup-name</code> — choose “Open a popup” in the link list while editing the website.</p>
  <?php if (!$popups): ?>
  <p>No popups yet.</p>
  <?php else: ?>
  <table class="adm-table">
    <thead><tr><th>Popup</th><th>Link</th><th>Width</th><th></th></tr></thead>
    <?php foreach ($popups as $p): $pid = (string)($p['id'] ?? ''); ?>
    <tr>
      <td><b><?= e($p['title'] ?? $pid) ?></b></td>
      <td><code>#popup-<?= e($pid) ?></code> <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" data-copy="#popup-<?= e($pid) ?>">Copy</button></td>
      <td><?= e(strtoupper($p['width'] ?? 'md')) ?></td>
      <td class="adm-right">
        <a class="adm-btn adm-btn-sm" href="<?= e(url('/admin/popups.php?id=' . rawurlencode($pid))) ?>">Edit design</a>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($pid) ?>">
          <button class="adm-btn adm-btn-sm adm-btn-danger" data-confirm="Delete this popup? Links to it will stop working.">Delete</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <form method="post" class="adm-form adm-inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <label>New popup name <input name="title" required placeholder="e.g. Brochure download"></label>
    <label>Link name <span class="adm-prefix">#popup-<input name="id" placeholder="brochure"></span></label>
    <button class="adm-btn adm-btn-primary" type="submit">Create popup</button>
  </form>
</div>
<?php endif;
admin_footer();
