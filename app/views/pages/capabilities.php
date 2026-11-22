<?php
/** Capabilities (ported from src/app/capabilities/*). */
$d = Content::doc('capabilities');
$m = $d->at('main');
$sp = $d->at('support');
$inf = $d->at('infrastructure');
?>
<?php if ($d->shown('hero')) echo page_hero($d->at('hero'), '/capabilities/'); ?>

<?php if ($d->shown('main')): ?>
<section class="bg-white py-10"<?= $d->section('main', 'Capabilities') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-12"><?= section_heading($m) ?></div>
    <div class="grid gap-3 md:grid-cols-2" data-reveal-group<?= $m->lst('items', 'capability', 'image') ?>>
      <?php foreach ($m->each('items') as $i => $cap): ?>
      <div data-reveal class="group overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-md transition-all duration-500 hover:-translate-y-1 hover:shadow-xl"<?= $m->item($i) ?>>
        <div class="relative h-48 overflow-hidden">
          <?= $cap->img('image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110', $cap->str('title')) ?>
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
          <div class="absolute bottom-4 left-4 flex items-center gap-3"<?= $cap->edit(['title' => 'text', 'glyph' => ['icon', 'Icon']], 'Capability title') ?>>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#3CAAE5] to-[#99C71E] text-white"><?= glyph($cap->str('glyph'), 'h-6 w-6') ?></span>
            <h3 class="text-xl font-bold text-white"><?= $cap->v('title') ?></h3>
          </div>
        </div>
        <div class="p-6">
          <p class="text-gray-600"><?= $cap->ta('description', 'Description') ?></p>
          <ul class="mt-5 grid gap-2 sm:grid-cols-2"<?= $cap->edit(['features' => ['lines', 'Features (one per line)']], 'Features') ?>>
            <?php foreach ($cap->lines('features') as $f): ?>
            <li class="flex items-start gap-2 text-sm text-gray-700"><?= check_icon('mt-0.5 h-4 w-4 shrink-0 text-[#99C71E]') ?><?= e($f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('support')): ?>
<section class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('support', 'Support services') ?>>
  <div class="pointer-events-none absolute -right-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -left-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-8"><?= section_heading($sp, 'dark') ?></div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-reveal-group<?= $sp->lst('items', 'service') ?>>
      <?php foreach ($sp->each('items') as $i => $it): $accent = $i % 2 === 0 ? '#3CAAE5' : '#99C71E'; ?>
      <div data-reveal class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm transition-all duration-500 hover:-translate-y-2 hover:border-white/20 hover:bg-white/10"<?= $sp->item($i) ?>>
        <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 transition-transform duration-500 group-hover:scale-110"<?= $it->edit(['glyph' => ['icon', 'Icon']], 'Icon') ?>>
          <?= glyph($it->str('glyph'), 'h-7 w-7', 1.6) ?>
        </div>
        <h3 class="text-lg font-bold text-white"><?= $it->t('title', 'Title') ?></h3>
        <span class="mt-3 block h-0.5 w-8 rounded-full transition-all duration-500 group-hover:w-20" style="background: <?= $accent ?>"></span>
        <p class="mt-3 text-sm leading-relaxed text-gray-400"><?= $it->ta('description', 'Description') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('infrastructure')): ?>
<section class="relative overflow-hidden bg-gray-950 py-10 text-white" data-parallax<?= $d->section('infrastructure', 'Infrastructure (parallax)') ?>>
  <div class="pointer-events-none absolute inset-x-0 -top-[12%] h-[124%] z-0" data-parallax-layer>
    <img src="<?= $inf->url('background') ?>" alt="" class="h-full w-full object-cover" loading="lazy">
  </div>
  <div class="pointer-events-none absolute inset-0 bg-gray-950/70"></div>
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(60,170,229,0.1),transparent_55%)]"></div>
  <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid items-center gap-12 lg:grid-cols-2">
      <div data-reveal="left"<?= $inf->edit(['background' => ['image', 'Parallax background image']], 'Section background') ?>>
        <?= section_heading($inf, 'dark', 'left') ?>
      </div>
      <div class="grid grid-cols-2 gap-4"<?= $inf->lst('stats', 'stat') ?>>
        <?php foreach ($inf->each('stats') as $i => $st): ?>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm"<?= $inf->item($i) ?><?= $st->edit(['value' => ['number', 'Number'], 'suffix' => ['text', 'Suffix (e.g. K+)'], 'label' => 'text'], 'Stat') ?>>
          <p class="text-3xl font-bold lg:text-4xl">
            <span class="bg-gradient-to-r from-sky-300 to-lime-300 bg-clip-text text-transparent" data-counter="<?= e((string)$st->num('value')) ?>" data-suffix="<?= $st->e('suffix') ?>"><?= e((string)$st->num('value')) ?><?= $st->e('suffix') ?></span>
          </p>
          <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400"><?= $st->v('label') ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta')); ?>
