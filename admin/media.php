<?php
require __DIR__ . '/_init.php';
$user = require_login();

if (is_post()) {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'upload' && !empty($_FILES['files'])) {
        $files = $_FILES['files'];
        $ok = 0;
        $done = [];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            try {
                $row = Media::upload([
                    'name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ], (int)$user['id']);
                $done[] = Media::summary($row);
                $ok++;
            } catch (RuntimeException $e) {
                flash($files['name'][$i] . ': ' . $e->getMessage(), 'error');
            }
        }
        if ($ok) flash("Uploaded $ok file" . ($ok > 1 ? 's' : '') . ': ' . implode(' | ', $done));
    } elseif ($action === 'delete') {
        Media::delete((int)($_POST['id'] ?? 0));
        flash('File deleted. (If it was used on a page, replace it there.)', 'info');
    }
    redirect('/admin/media.php' . (isset($_GET['tab']) ? '?tab=' . rawurlencode((string)$_GET['tab']) : ''));
}

$tab = ($_GET['tab'] ?? 'uploads') === 'bundled' ? 'bundled' : 'uploads';
$items = $tab === 'bundled' ? Media::bundled() : Media::uploads();

admin_header('Media library', 'media');
?>
<div class="adm-card">
  <form method="post" enctype="multipart/form-data" class="adm-dropzone" data-dropzone>
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <input type="file" name="files[]" multiple accept="image/*,video/mp4,video/webm,application/pdf" id="adm-files">
    <label for="adm-files">
      <b>Drop files here or click to upload</b>
      <span>Images (JPG, PNG, GIF, WebP, AVIF, BMP) are automatically converted to compressed WebP and resized to max <?= Media::settings()[1] ?> px (change in <a href="<?= e(url('/admin/settings.php')) ?>" style="pointer-events:auto;position:relative;z-index:2">Settings</a>). Videos: MP4, WebM · documents: PDF · max <?= e(ini_get('upload_max_filesize')) ?> per file.</span>
    </label>
    <button type="submit" class="adm-btn adm-btn-primary">Upload</button>
  </form>
</div>

<div class="adm-tabs">
  <a href="?tab=uploads" class="<?= $tab === 'uploads' ? 'is-active' : '' ?>">Your uploads</a>
  <a href="?tab=bundled" class="<?= $tab === 'bundled' ? 'is-active' : '' ?>">Website images (<?= count(Media::bundled()) ?>)</a>
</div>

<?php if (!$items): ?>
  <div class="adm-card"><p class="adm-muted">No uploads yet. Upload files above, or replace any image directly while editing the website.</p></div>
<?php else: ?>
<div class="adm-media-grid">
  <?php foreach ($items as $m): $isImg = str_starts_with($m['mime'], 'image/'); $isVid = str_starts_with($m['mime'], 'video/'); ?>
  <div class="adm-media">
    <div class="adm-media-thumb">
      <?php if ($isImg): ?><img src="<?= e(url($m['url'])) ?>" alt="" loading="lazy">
      <?php elseif ($isVid): ?><video src="<?= e(url($m['url'])) ?>" muted preload="metadata"></video>
      <?php else: ?><span class="adm-media-file"><?= e(strtoupper(pathinfo($m['url'], PATHINFO_EXTENSION))) ?></span><?php endif; ?>
    </div>
    <div class="adm-media-info">
      <b title="<?= e($m['name']) ?>"><?= e($m['name']) ?></b>
      <small><?= e(human_bytes((int)$m['size'])) ?><?= $m['w'] ? ' · ' . (int)$m['w'] . '×' . (int)$m['h'] : '' ?></small>
      <div class="adm-media-actions">
        <button type="button" class="adm-btn adm-btn-sm adm-btn-ghost" data-copy="<?= e(abs_url($m['url'])) ?>">Copy link</button>
        <?php if ($m['id']): ?>
        <form method="post" class="adm-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
          <button class="adm-btn adm-btn-sm adm-btn-danger" data-confirm="Delete this file permanently? Pages using it will show a broken image.">Delete</button></form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php admin_footer();
