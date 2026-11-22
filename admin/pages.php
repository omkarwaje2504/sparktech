<?php
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_page') {
        $title = trim(clean_text($_POST['title'] ?? '', 120));
        $slug = slugify((string)($_POST['slug'] ?? '') ?: $title);
        if ($title === '' || $slug === '') {
            flash('Enter a page title.', 'error');
        } elseif (in_array($slug, Content::RESERVED, true) || Content::pageExists($slug, true)) {
            flash('The address /' . $slug . '/ is already used. Choose another one.', 'error');
        } else {
            $data = Content::blankPage($title);
            Content::saveDraft('page:' . $slug, $data, (int)$user['id']);
            flash('Page created as a draft. Add your content, then press Publish to make it live.');
            redirect('/admin/edit.php?on=1&_csrf=' . csrf_token() . '&return=' . rawurlencode('/' . $slug . '/'));
        }
    } elseif ($action === 'delete_page') {
        $slug = (string)($_POST['slug'] ?? '');
        if (Content::pageExists($slug, true)) {
            $name = 'page:' . $slug;
            $row = DB::row('SELECT * FROM {content_docs} WHERE doc = ?', [$name]);
            if ($row && $row['published'] !== null) {
                DB::insert('revisions', ['doc' => $name, 'data' => $row['published'], 'note' => 'Before delete', 'user_id' => (int)$user['id'], 'created_at' => now()]);
            }
            $tomb = json_encode(['_deleted' => true]);
            if ($row) {
                DB::update('content_docs', ['draft' => $tomb, 'published' => $tomb, 'updated_at' => now(), 'updated_by' => (int)$user['id']], 'doc = ?', [$name]);
            } else {
                DB::insert('content_docs', ['doc' => $name, 'draft' => $tomb, 'published' => $tomb, 'updated_at' => now(), 'updated_by' => (int)$user['id']]);
            }
            flash('Page deleted. (It can be restored from History.)');
        }
    } elseif ($action === 'add_service') {
        $title = trim(clean_text($_POST['title'] ?? '', 120));
        $slug = slugify((string)($_POST['slug'] ?? '') ?: $title);
        $data = Content::draftForEdit('services');
        $exists = false;
        foreach ($data['items'] as $it) {
            if (($it['slug'] ?? '') === $slug) $exists = true;
        }
        if ($title === '' || $slug === '') {
            flash('Enter a service name.', 'error');
        } elseif ($exists) {
            flash('A service with the address /services/' . $slug . '/ already exists.', 'error');
        } else {
            $template = $data['items'][0] ?? [];
            $data['items'][] = array_merge($template, [
                'slug' => $slug, 'visible' => true, 'show_card' => true, 'in_menu' => true,
                'menu_title' => $title, 'form_name' => 'Other', 'card_title' => $title,
                'card_desc' => 'Short description shown on the service card.',
                'title' => $title, 'subtitle' => 'Subtitle', 'description' => 'Describe this service here.',
                'process' => [], 'features' => ['First feature'], 'applications' => ['First application'],
                'sections' => [['heading' => 'Overview', 'paragraphs' => ['Write about this service here.']]],
                'seo' => ['title' => '', 'description' => ''],
            ]);
            Content::saveDraft('services', $data, (int)$user['id']);
            flash('Service added as a draft. Edit its text and images, then press Publish.');
            redirect('/admin/edit.php?on=1&_csrf=' . csrf_token() . '&return=' . rawurlencode('/services/' . $slug . '/'));
        }
    }
    redirect('/admin/pages.php');
}

$pending = Content::pending();
$builtin = [
    ['home', '/', 'Home'], ['about', '/about/', 'About'], ['services', '/services/', 'Services'],
    ['technologies', '/technologies/', 'Technologies'], ['capabilities', '/capabilities/', 'Capabilities'],
    ['gallery', '/gallery/', 'Gallery'], ['contact', '/contact/', 'Contact'],
];
$status = function (string $doc) use ($pending) {
    return in_array($doc, $pending, true) ? '<span class="adm-pill adm-pill-amber">Draft changes</span>' : '<span class="adm-pill adm-pill-green">Live</span>';
};

admin_header('Pages', 'pages');
?>
<div class="adm-card">
  <h3>Main pages</h3>
  <table class="adm-table">
    <thead><tr><th>Page</th><th>Address</th><th>Status</th><th></th></tr></thead>
    <?php foreach ($builtin as [$doc, $path, $label]): ?>
    <tr>
      <td><b><?= e($label) ?></b></td>
      <td><code><?= e($path) ?></code></td>
      <td><?= $status($doc) ?></td>
      <td class="adm-right"><a class="adm-btn adm-btn-sm" href="<?= e(live_edit_url($path)) ?>">✎ Edit live</a> <a class="adm-btn adm-btn-sm adm-btn-ghost" href="<?= e(url($path)) ?>" target="_blank">View</a></td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td><b>Header, footer & contact details</b><br><small class="adm-muted">Shared by every page</small></td>
      <td>—</td>
      <td><?= $status('site') ?></td>
      <td class="adm-right"><a class="adm-btn adm-btn-sm" href="<?= e(live_edit_url('/')) ?>">✎ Edit live</a></td>
    </tr>
  </table>
</div>

<div class="adm-card">
  <div class="adm-card-row">
    <h3>Service pages</h3>
  </div>
  <table class="adm-table">
    <thead><tr><th>Service</th><th>Address</th><th>Shown</th><th></th></tr></thead>
    <?php foreach (services_list(true) as $s): $path = '/services/' . $s->str('slug') . '/'; ?>
    <tr>
      <td><b><?= $s->e('title') ?></b></td>
      <td><code><?= e($path) ?></code></td>
      <td>
        <?= $s->bool('visible', true) ? '<span class="adm-pill adm-pill-green">Published</span>' : '<span class="adm-pill">Hidden</span>' ?>
        <?= $s->bool('show_card', true) ? '<span class="adm-pill">Card</span>' : '' ?>
        <?= $s->bool('in_menu', true) ? '<span class="adm-pill">Menu</span>' : '' ?>
      </td>
      <td class="adm-right"><a class="adm-btn adm-btn-sm" href="<?= e(live_edit_url($path)) ?>">✎ Edit live</a> <a class="adm-btn adm-btn-sm adm-btn-ghost" href="<?= e(url($path)) ?>" target="_blank">View</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p class="adm-muted adm-small">Re-order, duplicate or delete services on the live <a href="<?= e(live_edit_url('/services/')) ?>">Services page</a> (hover a card → use the arrows / copy / trash buttons). Hide a service with “Service settings” on its page.</p>
  <form method="post" class="adm-form adm-inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="add_service">
    <label>New service name <input name="title" required placeholder="e.g. Biodiesel Plant"></label>
    <label>Address <span class="adm-prefix">/services/<input name="slug" placeholder="biodiesel-plant"></span></label>
    <button class="adm-btn adm-btn-primary" type="submit">Add service</button>
  </form>
</div>

<div class="adm-card">
  <h3>Other pages</h3>
  <?php $custom = Content::customPages(true); ?>
  <?php if ($custom): ?>
  <table class="adm-table">
    <thead><tr><th>Page</th><th>Address</th><th>Status</th><th></th></tr></thead>
    <?php foreach ($custom as $slug => $title): $doc = 'page:' . $slug; $live = Content::pageExists($slug, false); ?>
    <tr>
      <td><b><?= e($title) ?></b></td>
      <td><code>/<?= e($slug) ?>/</code></td>
      <td><?= $live ? $status($doc) : '<span class="adm-pill adm-pill-amber">Not published yet</span>' ?></td>
      <td class="adm-right">
        <a class="adm-btn adm-btn-sm" href="<?= e(live_edit_url('/' . $slug . '/')) ?>">✎ Edit live</a>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete_page"><input type="hidden" name="slug" value="<?= e($slug) ?>">
          <button class="adm-btn adm-btn-sm adm-btn-danger" data-confirm="Delete the page “<?= e($title) ?>”?">Delete</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
  <form method="post" class="adm-form adm-inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="create_page">
    <label>New page title <input name="title" required placeholder="e.g. Careers"></label>
    <label>Address <span class="adm-prefix">/<input name="slug" placeholder="careers"></span></label>
    <button class="adm-btn adm-btn-primary" type="submit">Create page</button>
  </form>
  <p class="adm-muted adm-small">Tip: link to a new page from the footer or menu by editing a link and entering its address, e.g. <code>/careers/</code>.</p>
</div>
<?php admin_footer();
