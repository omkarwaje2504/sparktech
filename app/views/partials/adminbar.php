<?php
/**
 * Floating admin toolbar shown to logged-in admins on the public site.
 * In edit mode assets/js/editor.js wires up the buttons.
 */
$pending = count(Content::pending());
$returnTo = rawurlencode(current_path());
$editing = edit_mode();
?>
<div id="cms-adminbar" class="cms-adminbar<?= $editing ? ' is-editing' : '' ?>" role="toolbar" aria-label="Website editor">
  <a class="cms-ab-brand" href="<?= e(url('/admin/')) ?>" title="Admin dashboard">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
    <span>Admin</span>
  </a>
  <?php if ($editing): ?>
    <span class="cms-ab-status"><i></i>Editing</span>
    <button type="button" class="cms-ab-btn" data-cms-action="pages" title="Go to another page">Pages ▾</button>
    <button type="button" class="cms-ab-btn" data-cms-action="seo" title="Page title, description & share image">Page settings</button>
    <button type="button" class="cms-ab-btn cms-ab-primary" data-cms-action="publish"<?= $pending ? '' : ' disabled' ?>>
      Publish<?= $pending ? ' <b data-cms-pending>' . $pending . '</b>' : ' <b data-cms-pending hidden>0</b>' ?>
    </button>
    <button type="button" class="cms-ab-btn cms-ab-ghost" data-cms-action="discard"<?= $pending ? '' : ' disabled' ?> title="Throw away unpublished changes">Discard</button>
    <a class="cms-ab-btn cms-ab-ghost" href="<?= e(url('/admin/edit.php?off=1&return=' . $returnTo . '&_csrf=' . csrf_token())) ?>">Exit editor</a>
  <?php else: ?>
    <a class="cms-ab-btn cms-ab-primary" href="<?= e(url('/admin/edit.php?on=1&return=' . $returnTo . '&_csrf=' . csrf_token())) ?>">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Edit this page
    </a>
    <?php if ($pending): ?><span class="cms-ab-note"><?= $pending ?> unpublished change<?= $pending > 1 ? 's' : '' ?></span><?php endif; ?>
  <?php endif; ?>
</div>
<?php if (!$editing): ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/editor.css')) ?>">
<?php endif; ?>
