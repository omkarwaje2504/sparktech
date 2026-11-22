<?php
/** Services listing (ported from src/app/services/page.tsx + ServicesView.tsx). */
$d = Content::doc('services');
$why = $d->at('why');
$gradOptions = [['from-sky-500 to-sky-400', 'Sky'], ['from-cyan-500 to-teal-400', 'Cyan / teal'], ['from-lime-500 to-green-400', 'Lime / green'], ['from-lime-400 to-emerald-500', 'Lime / emerald']];
?>
<?php if ($d->shown('hero')) echo page_hero($d->at('hero'), '/services/'); ?>

<?php if ($d->shown('grid')): ?>
<section class="bg-white py-10"<?= $d->section('grid', 'Service cards') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-12"><?= section_heading($d->at('grid')) ?></div>
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-4" data-reveal-group<?= $d->lst('items', 'service') ?>>
      <?php foreach (services_list(edit_mode()) as $i => $s): if (!$s->bool('show_card', true) && !edit_mode()) continue; ?>
      <div data-reveal<?= $d->item($i, "items.$i.visible") ?><?= (!$s->bool('show_card', true) && edit_mode()) ? ' data-cms-off="1"' : '' ?>><?= service_card($s) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('why')): ?>
<section class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('why', 'Why choose us') ?>>
  <div class="pointer-events-none absolute -left-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-8"><?= section_heading($why, 'dark') ?></div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-reveal-group<?= $why->lst('cards', 'card') ?>>
      <?php foreach ($why->each('cards') as $i => $card): $grad = $card->str('gradient', 'from-sky-500 to-sky-400'); ?>
      <div data-reveal class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 px-6 py-4 backdrop-blur-md transition-all duration-500 hover:-translate-y-2 hover:border-white/20 hover:bg-white/10"<?= $why->item($i) ?>>
        <span class="absolute -right-2 -top-3 text-7xl font-black text-white/10"><?= pad2($i + 1) ?></span>
        <div class="relative mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br <?= e($grad) ?> shadow-lg transition-transform duration-500 group-hover:rotate-3 group-hover:scale-110"<?= $card->edit(['glyph' => ['icon', 'Icon'], 'gradient' => ['select', 'Colour', $gradOptions]], 'Card icon') ?>>
          <?= glyph($card->str('glyph'), 'h-7 w-7 text-white') ?>
        </div>
        <h3 class="relative text-xl font-bold text-white"><?= $card->t('title', 'Card title') ?></h3>
        <div class="relative mt-1 h-0.5 w-10 rounded-full bg-gradient-to-r <?= e($grad) ?> transition-all duration-500 group-hover:w-16"></div>
        <ul class="relative mt-2 space-y-1"<?= $card->edit(['points' => ['lines', 'Bullet points (one per line)']], 'Bullet points') ?>>
          <?php foreach ($card->lines('points') as $pt): ?>
          <li class="flex items-center gap-3 text-sm text-gray-300"><?= check_icon('h-4 w-4 shrink-0 text-sky-400') ?><?= e($pt) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta')); ?>
