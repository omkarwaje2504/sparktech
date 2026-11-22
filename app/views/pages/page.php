<?php
/**
 * Custom page (privacy, terms and pages created in Admin → Pages).
 * Built from a hero, an optional rich-text body (older pages) and sections added with "+ Add a section".
 * @var string $slug
 */
$d = Content::doc('page:' . $slug);
$h = $d->at('hero');
$hasBlocks = $d->count('blocks') > 0;
?>
<?php if ($d->shown('hero')) echo page_hero($h, '/' . $slug . '/'); ?>
<?php if ($d->has('body') || (edit_mode() && !$hasBlocks)): ?>
<section class="bg-white py-14"<?= $d->section('body', 'Page content') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="st-prose mx-auto max-w-3xl text-base leading-relaxed text-gray-700">
      <?= $d->rich('body', 'Page content', 'div') ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?= render_blocks($d) ?>
