<?php
/** Technologies (ported from src/app/technologies/*). */
$d = Content::doc('technologies');
$sb = $d->at('sidebar');
$items = $d->each('items');
?>
<?php if ($d->shown('hero')) echo page_hero($d->at('hero'), '/technologies/'); ?>

<?php if ($d->shown('list')): ?>
<section class="bg-white py-10"<?= $d->section('list', 'Technologies list') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-12"><?= section_heading($d->at('intro')) ?></div>
    <div class="grid gap-12 lg:grid-cols-[280px_1fr]">
      <aside class="hidden lg:block">
        <div class="sticky top-28 space-y-6">
          <div class="rounded-3xl border border-gray-200 bg-gray-50 p-6">
            <h3 class="mb-4 text-lg font-bold text-gray-900"><?= $sb->t('nav_title', 'Sidebar title') ?></h3>
            <nav class="space-y-1">
              <?php foreach ($items as $i => $t): ?>
              <a href="#<?= $t->e('id') ?>" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-gray-600 transition-all hover:bg-white hover:text-sky-600">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] text-xs font-bold text-white transition-shadow group-hover:shadow-md"><?= pad2($i + 1) ?></span>
                <span class="text-sm font-medium"><?= $t->v('title') ?></span>
              </a>
              <?php endforeach; ?>
            </nav>
          </div>
          <div class="overflow-hidden rounded-3xl border border-white/10 bg-gray-950 text-white">
            <div class="h-1.5 w-full bg-gradient-to-r from-[#6EC0EF] via-sky-400 to-[#B0D10E]"></div>
            <div class="p-6">
              <h3 class="text-lg font-bold"><?= $sb->t('help_title', 'Help box title') ?></h3>
              <p class="mt-2 text-sm text-gray-300"><?= $sb->ta('help_text', 'Help box text') ?></p>
              <a href="<?= $sb->url('help_button.href', '/contact/') ?>"<?= link_edit($sb, 'help_button') ?>
                 class="mt-4 block w-full rounded-full bg-sky-500 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400"><?= $sb->v('help_button.label') ?></a>
            </div>
          </div>
        </div>
      </aside>

      <div class="space-y-16"<?= $d->lst('items', 'technology', 'image') ?>>
        <?php foreach ($items as $i => $t): $flipped = $i % 2 === 1; ?>
        <div data-reveal="<?= $flipped ? 'right' : 'left' ?>"<?= $d->item($i) ?>>
          <div id="<?= $t->e('id') ?>" class="grid scroll-mt-28 items-center gap-10 md:grid-cols-2">
            <div class="<?= $flipped ? 'md:order-2' : '' ?>">
              <div class="relative h-64 overflow-hidden rounded-[2rem] border border-gray-100 shadow-[0_30px_80px_rgba(15,23,42,0.14)] md:h-[24rem]">
                <?= $t->img('image', 'absolute inset-0 h-full w-full object-cover', $t->str('title')) ?>
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-950/55 via-transparent to-transparent"></div>
                <span class="absolute bottom-5 left-5 inline-flex items-center rounded-full bg-[#B0D10E] px-4 py-1.5 text-sm font-bold text-black"><?= pad2($i + 1) ?></span>
              </div>
            </div>
            <div class="<?= $flipped ? 'md:order-1' : '' ?>">
              <h3 class="text-2xl font-bold text-gray-900 md:text-3xl"<?= $t->edit(['title' => 'text', 'id' => ['text', 'Anchor id (used in links like /technologies/#id)']], 'Technology title') ?>><?= $t->v('title') ?></h3>
              <p class="mt-4 text-gray-600"><?= $t->ta('description', 'Description') ?></p>
              <p class="mt-3 text-sm leading-relaxed text-gray-500"><?= $t->ta('details', 'Details') ?></p>
              <ul class="mt-6 space-y-3"<?= $t->edit(['features' => ['lines', 'Features (one per line)']], 'Features') ?>>
                <?php foreach ($t->lines('features') as $f): ?>
                <li class="flex items-center gap-3 text-gray-700"><?= check_icon('h-5 w-5 shrink-0 text-[#99C71E]') ?><?= e($f) ?></li>
                <?php endforeach; ?>
              </ul>
              <div class="mt-8"><?= cta_button($d, 'quote_button', 'outline') ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta')); ?>
