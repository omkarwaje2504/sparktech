<?php
/** About page (ported from src/app/about/page.tsx). */
$d = Content::doc('about');
$h = $d->at('hero');
$in = $d->at('intro');
$jr = $d->at('journey');
$cp = $d->at('capabilities');
$ind = $d->at('industries');
$epc = $d->at('epc');
$fac = $d->at('facility');
$q = $d->at('quality');
$vm = $d->at('vision');
$ld = $d->at('leadership');
$num = $d->at('numbers');
$sky = '#3CAAE5';
$lime = '#99C71E';
$spanOptions = [['', 'Normal tile'], ['col-span-2', 'Wide tile'], ['col-span-2 row-span-2', 'Large tile']];
$gradOptions = [['from-sky-500 to-sky-400', 'Sky'], ['from-cyan-500 to-teal-400', 'Cyan / teal'], ['from-lime-500 to-green-400', 'Lime / green'], ['from-lime-400 to-emerald-500', 'Lime / emerald']];
?>

<?php if ($d->shown('hero')): ?>
<section id="about-hero" class="relative w-full overflow-hidden bg-gray-950 text-white"<?= $d->section('hero', 'Hero banner') ?>>
  <div class="absolute inset-0">
    <?= $h->img('image', 'absolute inset-0 h-full w-full object-cover', 'SPARKTECH industrial project', ['loading' => 'eager', 'fetchpriority' => 'high'], 'Banner background') ?>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/80 to-gray-950/40"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/70 to-transparent"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(60,170,229,0.18),transparent_45%)]"></div>
  </div>
  <div class="pointer-events-none absolute -left-20 top-1/3 h-72 w-72 rounded-full bg-sky-500/15 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-16 bottom-0 h-72 w-72 rounded-full bg-lime-400/10 blur-[120px]"></div>

  <div class="container relative z-10 mx-auto flex items-center px-4 pb-10 pt-32 lg:pt-36 sm:px-6 lg:px-8">
    <div class="grid w-full items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
      <div data-reveal-group>
        <span data-reveal class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[9px] lg:text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-200 backdrop-blur-sm">
          <?= ping_dot() ?><?= $h->t('eyebrow', 'Eyebrow label') ?>
        </span>
        <h1 data-reveal class="text-3xl font-bold leading-[1.08] md:text-4xl lg:text-5xl 2xl:text-6xl">
          <?= $h->t('title', 'Title') ?>
          <span class="block bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $h->t('highlight', 'Highlighted line') ?></span>
        </h1>
        <p data-reveal class="mt-2 lg:mt-6 max-w-3xl text-sm leading-relaxed text-gray-300 lg:text-md 2xl:text-lg"><?= $h->ta('subtitle', 'Sub-heading') ?></p>
        <div data-reveal class="mt-5 lg:mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
          <a href="<?= $h->url('primary.href') ?>"<?= link_edit($h, 'primary') ?>
             class="group inline-flex items-center justify-center gap-3 rounded-full bg-sky-500 px-7 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">
            <?= $h->v('primary.label') ?><?= arrow_icon('h-4 w-4 transition-transform duration-300 group-hover:translate-x-1') ?>
          </a>
          <a href="<?= $h->url('secondary.href') ?>"<?= link_edit($h, 'secondary') ?>
             class="inline-flex items-center justify-center gap-3 rounded-full border border-white/25 bg-white/5 px-7 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white backdrop-blur-md transition hover:border-white/50 hover:bg-white/10">
            <?= $h->v('secondary.label') ?>
          </a>
        </div>
      </div>

      <div class="relative hidden lg:block" data-reveal="right" data-delay="300">
        <div class="relative overflow-hidden rounded-[2rem] border border-white/10 shadow-[0_40px_100px_rgba(0,0,0,0.5)] [animation:float_6s_ease-in-out_infinite]">
          <?= $h->img('side_image', 'lg:h-[23rem] 2xl:h-[30rem] w-full object-cover', 'SPARKTECH manufacturing plant', ['loading' => 'eager', 'width' => '900', 'height' => '1100'], 'Side image') ?>
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-950/60 via-transparent to-transparent"></div>
          <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-gradient-to-r from-sky-400 to-lime-400"></div>
        </div>
        <div data-reveal data-delay="700" class="absolute -left-6 bottom-10 rounded-2xl border border-white/10 bg-gray-900/80 px-5 py-4 backdrop-blur-md"<?= $h->edit(['stat_value' => ['text', 'Number'], 'stat_label' => ['text', 'Label']], 'Stat badge') ?>>
          <p class="bg-gradient-to-r from-sky-300 to-lime-300 bg-clip-text text-3xl font-bold text-transparent"><?= $h->v('stat_value') ?></p>
          <p class="text-xs uppercase tracking-wide text-gray-400"><?= $h->v('stat_label') ?></p>
        </div>
      </div>
    </div>
  </div>
  <?= scroll_cue() ?>
</section>
<?php endif; ?>

<?php if ($d->shown('intro')): ?>
<section id="about-SPARKTECH" class="relative overflow-hidden bg-gray-50 py-10"<?= $d->section('intro', 'About SPARKTECH') ?>>
  <div class="pointer-events-none absolute -right-24 top-10 h-80 w-80 rounded-full bg-sky-100 blur-3xl"></div>
  <div class="pointer-events-none absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-lime-100 blur-3xl"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-6 md:mb-12 max-w-3xl text-center">
      <span class="mb-1 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-600"><?= $in->t('eyebrow', 'Eyebrow label') ?></span>
      <h2 class="flex-1 text-2xl font-bold leading-7 text-gray-800 md:text-4xl md:leading-8 lg:leading-none">
        <?= $in->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-transparent"><?= $in->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="grid items-stretch gap-8 lg:grid-cols-12">
      <div data-reveal="left" class="relative lg:col-span-7">
        <div class="relative md:h-[15rem] lg:h-[23rem] 2xl:h-[24rem] h-[15rem] overflow-hidden rounded-[2rem] border border-gray-100 shadow-[0_30px_80px_rgba(15,23,42,0.12)] lg:h-full">
          <?= $in->img('image', 'absolute inset-0 h-full w-full object-cover', 'SPARKTECH industrial manufacturing plant') ?>
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-950/50 via-transparent to-transparent"></div>
        </div>
      </div>
      <div data-reveal="right" class="flex flex-col justify-center lg:col-span-5">
        <p class="text-base leading-relaxed text-gray-600"><?= $in->ta('lead', 'Intro paragraph') ?></p>
        <div class="mt-3 space-y-3">
          <div class="border-l-2 border-sky-400 pl-4"><p class="text-sm leading-relaxed text-gray-600"><?= $in->ta('quote_1', 'Paragraph 2') ?></p></div>
          <div class="border-l-2 border-lime-400 pl-4"><p class="text-sm leading-relaxed text-gray-600"><?= $in->ta('quote_2', 'Paragraph 3') ?></p></div>
        </div>
      </div>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-gray-100 bg-gray-100 md:grid-cols-4" data-reveal-group<?= $in->lst('stats', 'stat') ?>>
      <?php foreach ($in->each('stats') as $i => $s): ?>
      <div data-reveal class="bg-white px-6 py-8 text-center"<?= $in->item($i) ?>>
        <p class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-3xl font-bold text-transparent lg:text-4xl"><?= $s->t('value', 'Number') ?></p>
        <p class="mt-1 text-sm text-gray-500"><?= $s->t('label', 'Label') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('journey')): ?>
<section id="our-journey" class="relative overflow-hidden bg-white py-10"<?= $d->section('journey', 'Our journey timeline') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-10 max-w-3xl text-center">
      <span class="mb-1 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-600"><?= $jr->t('eyebrow', 'Eyebrow label') ?></span>
      <h2 class="flex-1 text-2xl font-bold leading-7 text-gray-800 md:text-4xl md:leading-8 lg:leading-none">
        <?= $jr->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-transparent"><?= $jr->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>

    <!-- Desktop: curved rising path; positions computed in assets/js/site.js -->
    <div class="relative hidden lg:block my-16 xl:my-20 lg:py-10">
      <div class="relative h-64 xl:h-72 2xl:h-80" data-journey>
        <svg class="pointer-events-none absolute inset-0 h-full w-full overflow-visible" fill="none" data-journey-svg aria-hidden="true">
          <defs>
            <linearGradient id="journeyLineGradient" x1="0" y1="0" x2="1000" y2="0" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#3CAAE5"/><stop offset="55%" stop-color="#3CAAE5"/><stop offset="100%" stop-color="#99C71E"/>
            </linearGradient>
            <marker id="journeyArrowhead" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
              <path d="M0 0L10 5L0 10Z" fill="#99C71E"/>
            </marker>
          </defs>
          <path data-journey-path stroke="url(#journeyLineGradient)" stroke-width="2.5" stroke-linecap="round" marker-end="url(#journeyArrowhead)"/>
        </svg>
        <div class="absolute inset-0" data-reveal-group<?= $jr->lst('items', 'milestone') ?>>
          <?php foreach ($jr->each('items') as $i => $it): $isTop = $i % 2 === 0; $color = $isTop ? $sky : $lime; ?>
          <div data-journey-point data-reveal class="absolute flex -translate-x-1/2 -translate-y-1/2 flex-col items-center" style="left:0;top:0"<?= $jr->item($i) ?>>
            <div class="absolute bottom-full mb-2 flex justify-center">
              <?php if ($it->bool('growth')): ?>
              <span class="flex items-center gap-1 whitespace-nowrap rounded-full border border-lime-300 bg-lime-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-lime-600 shadow-sm"><?= growth_icon() ?><?= $jr->v('growth_label') ?></span>
              <?php endif; ?>
            </div>
            <span class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full border-4 border-white shadow-md" style="background: <?= $color ?>"><span class="h-2 w-2 rounded-full bg-white"></span></span>
            <div class="absolute w-40 xl:w-48 <?= $isTop ? 'bottom-full mb-6 xl:mb-8' : 'top-full mt-6 xl:mt-8' ?>">
              <div class="w-full rounded-2xl border border-gray-200 bg-white p-4 xl:p-5 text-center shadow-lg transition-transform duration-300 <?= $isTop ? 'hover:-translate-y-1' : 'hover:translate-y-1' ?>"<?= $it->edit(['year' => 'text', 'title' => 'text', 'growth' => ['bool', 'Show "Growth" badge']], 'Milestone') ?>>
                <p class="text-xl xl:text-2xl font-bold" style="color: <?= $color ?>"><?= $it->v('year') ?></p>
                <p class="mt-1 text-sm font-semibold text-gray-800"><?= $it->v('title') ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Mobile timeline -->
    <div class="relative lg:hidden">
      <div class="absolute bottom-0 left-4 top-0 w-0.5 bg-gradient-to-b from-[#3CAAE5] to-[#99C71E]"></div>
      <div class="relative" data-reveal-group>
        <?php foreach ($jr->each('items') as $i => $it): $color = $i % 2 === 0 ? $sky : $lime; ?>
        <div data-reveal class="relative pl-8 md:pl-12 mb-4">
          <span class="absolute left-[9px] top-2 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white shadow" style="background: <?= $color ?>"></span>
          <div class="rounded-2xl border border-gray-200 bg-white p-2 pl-4 md:p-5 shadow-md">
            <div class="flex items-center gap-2">
              <p class="text-xl font-bold" style="color: <?= $color ?>"><?= $it->v('year') ?></p>
              <?php if ($it->bool('growth')): ?>
              <span class="inline-flex items-center gap-1 rounded-full border border-lime-300 bg-lime-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-lime-600"><?= growth_icon() ?><?= $jr->v('growth_label') ?></span>
              <?php endif; ?>
            </div>
            <p class="md:mt-1 text-sm md:text-lg font-semibold text-gray-800"><?= $it->v('title') ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('capabilities')): ?>
<section id="engineering-excellence" class="relative overflow-hidden bg-gray-950 py-10 text-white" data-parallax<?= $d->section('capabilities', 'Integrated capabilities (dark)') ?>>
  <div class="pointer-events-none absolute inset-x-0 -top-[12%] h-[124%] z-0" data-parallax-layer>
    <img src="<?= $cp->url('background') ?>" alt="" class="h-full w-full object-cover" loading="lazy">
  </div>
  <div class="pointer-events-none absolute inset-0 bg-gray-950/60"></div>
  <div class="pointer-events-none absolute -left-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(60,170,229,0.08),transparent_50%)]"></div>
  <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-8 max-w-3xl text-center"<?= $cp->edit(['background' => ['image', 'Parallax background image']], 'Section background') ?>>
      <h2 class="text-2xl font-bold leading-7 text-white md:text-4xl md:leading-8 lg:leading-none">
        <?= $cp->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $cp->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-reveal-group<?= $cp->lst('cards', 'card') ?>>
      <?php foreach ($cp->each('cards') as $i => $card): $grad = $card->str('gradient', 'from-sky-500 to-sky-400'); ?>
      <div data-reveal class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 px-6 py-4 backdrop-blur-md transition-all duration-500 hover:-translate-y-2 hover:border-white/20 hover:bg-white/10"<?= $cp->item($i) ?>>
        <span class="absolute -right-2 -top-3 text-7xl font-black text-white/10 transition-colors duration-500 group-hover:text-white/10"><?= pad2($i + 1) ?></span>
        <div class="relative mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br <?= e($grad) ?> shadow-lg transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3"<?= $card->edit(['glyph' => ['icon', 'Icon'], 'gradient' => ['select', 'Colour', $gradOptions]], 'Card icon') ?>>
          <?= glyph($card->str('glyph'), 'h-7 w-7 text-white', 1.8) ?>
        </div>
        <h3 class="relative text-xl font-bold text-white"><?= $card->t('title', 'Card title') ?></h3>
        <div class="relative mt-1 h-0.5 w-10 rounded-full bg-gradient-to-r <?= e($grad) ?> transition-all duration-500 group-hover:w-16"></div>
        <ul class="relative mt-2 space-y-1"<?= $card->edit(['points' => ['lines', 'Bullet points (one per line)']], 'Bullet points') ?>>
          <?php foreach ($card->lines('points') as $pt): ?>
          <li class="flex items-center gap-3 text-md text-gray-300"><?= check_icon('h-4 w-4 shrink-0 text-sky-400') ?><?= e($pt) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('industries')): ?>
<section id="industries" class="bg-white py-10 overflow-hidden"<?= $d->section('industries', 'Industries we serve') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-8 max-w-3xl text-center">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 md:text-4xl md:leading-8 lg:leading-none">
        <?= $ind->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-transparent"><?= $ind->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="grid grid-cols-2 gap-1 sm:grid-cols-3" data-reveal-group<?= $ind->lst('items', 'industry', 'image') ?>>
      <?php foreach ($ind->each('items') as $i => $it): ?>
      <div data-reveal class="group relative aspect-[5/5] md:h-[20rem] md:w-full overflow-hidden rounded-2xl border border-gray-200 shadow-md"<?= $ind->item($i) ?>>
        <?= $it->img('image', 'absolute inset-0 h-full w-full object-cover transition-all duration-700 group-hover:scale-110', $it->str('title')) ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('epc')): $steps = (array)$epc->get('steps', []); $nSteps = count($steps); ?>
<section id="epc-process" class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('epc', 'EPC delivery process') ?>>
  <div class="pointer-events-none absolute -right-24 top-10 h-80 w-80 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute inset-0 opacity-[0.04] st-grid-48"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-8 max-w-3xl text-center">
      <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-200 backdrop-blur-sm"><?= $epc->t('eyebrow', 'Eyebrow label') ?></span>
      <h2 class="flex-1 text-2xl font-bold leading-7 text-white md:text-4xl md:leading-8 lg:leading-none">
        <?= $epc->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $epc->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="relative">
      <div class="absolute left-0 right-0 top-[52px] hidden h-px bg-gradient-to-r from-sky-500/40 via-white/20 to-lime-400/40 lg:block"></div>
      <div class="flex gap-4 overflow-x-auto pb-4 lg:grid lg:grid-cols-8 lg:gap-3 lg:overflow-visible" data-reveal-group<?= $epc->lst('steps', 'step') ?>>
        <?php foreach (array_keys($steps) as $i):
            $isSky = $i % 2 === 0;
            $accent = $isSky ? $sky : $lime;
            $ring = $isSky ? 'group-hover:border-sky-400/60 group-hover:shadow-sky-500/25' : 'group-hover:border-lime-400/60 group-hover:shadow-lime-400/25'; ?>
        <div data-reveal class="group relative flex w-36 shrink-0 flex-col items-center text-center lg:w-auto"<?= $epc->item($i) ?>>
          <div class="relative z-10 flex h-[104px] w-[104px] items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm shadow-lg transition-all duration-500 group-hover:-translate-y-1.5 group-hover:bg-white/10 <?= $ring ?>">
            <span class="absolute inset-1 rounded-xl border border-dashed opacity-0 transition-opacity duration-500 group-hover:opacity-40 group-hover:[animation:spin_8s_linear_infinite]" style="border-color: <?= $accent ?>"></span>
            <?= step_glyph($i, $accent, 'h-10 w-10 transition-transform duration-500 group-hover:scale-110') ?>
          </div>
          <span class="mt-5 text-xs font-bold tracking-[0.2em]" style="color: <?= $accent ?>"><?= $epc->v('step_label') ?> <?= pad2($i + 1) ?></span>
          <h3 class="mt-1 px-1 text-lg font-semibold leading-tight text-white"><?= $epc->t("steps.$i", 'Step name') ?></h3>
          <?php if ($i < $nSteps - 1): ?>
          <svg class="absolute -right-3 top-[46px] h-4 w-4 text-white/20 lg:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('facility')): ?>
<section id="manufacturing-facility" class="relative overflow-hidden py-10 text-white"<?= $d->section('facility', 'In-house facilities') ?>>
  <div class="pointer-events-none absolute -left-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-14 max-w-3xl text-center">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 md:text-4xl md:leading-8 lg:leading-none">
        <?= $fac->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-[#3CAAE5] to-[#99C71E] bg-clip-text text-transparent"><?= $fac->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="grid auto-rows-[180px] grid-cols-2 gap-3 md:grid-cols-4" data-reveal-group<?= $fac->lst('items', 'tile', 'image') ?>>
      <?php foreach ($fac->each('items') as $i => $it): ?>
      <div data-reveal="scale" class="group relative overflow-hidden rounded-2xl border border-white/10 <?= e($it->str('span')) ?>"<?= $fac->item($i) ?>>
        <?= $it->img('image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110', $it->str('title')) ?>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent transition-colors duration-500 group-hover:from-black/60"></div>
        <div class="absolute inset-x-0 bottom-0 p-4"<?= $it->edit(['title' => 'text', 'span' => ['select', 'Tile size', $spanOptions]], 'Facility tile') ?>>
          <span class="mb-1.5 block h-0.5 w-6 rounded-full bg-[#99C71E] transition-all duration-500 group-hover:w-10"></span>
          <h3 class="text-sm font-bold text-white lg:text-base"><?= $it->v('title') ?></h3>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('quality')): ?>
<section id="quality-safety" class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('quality', 'Quality & safety') ?>>
  <div class="pointer-events-none absolute -right-24 top-0 h-96 w-96 rounded-full bg-sky-500/10 blur-[120px]"></div>
  <div class="pointer-events-none absolute -left-24 bottom-0 h-96 w-96 rounded-full bg-lime-400/10 blur-[120px]"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto mb-14 max-w-3xl text-center">
      <h2 class="flex-1 text-2xl font-bold leading-7 text-white md:text-4xl md:leading-8 lg:leading-none">
        <?= $q->t('title', 'Heading') ?>
        <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $q->t('highlight', 'Highlighted words') ?></span>
      </h2>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6" data-reveal-group<?= $q->lst('items', 'card') ?>>
      <?php foreach ($q->each('items') as $i => $it): $accent = $i % 2 === 0 ? $sky : $lime; ?>
      <div data-reveal class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm transition-all duration-500 hover:-translate-y-2 hover:border-white/20 hover:bg-white/10"<?= $q->item($i) ?>>
        <div class="mb-2 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/5 transition-transform duration-500 group-hover:scale-110"><?= qs_glyph($i, $accent) ?></div>
        <h3 class="text-lg font-bold text-white"><?= $it->t('title', 'Card title') ?></h3>
        <span class="mt-3 block h-0.5 w-8 rounded-full transition-all duration-500 group-hover:w-44" style="background: <?= $accent ?>"></span>
        <p class="mt-3 text-sm leading-relaxed text-gray-400"><?= $it->ta('description', 'Card text') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('vision')): ?>
<section id="vision-mission" class="relative overflow-hidden bg-white py-10"<?= $d->section('vision', 'Vision & mission') ?>>
  <div class="pointer-events-none absolute -left-24 top-10 h-80 w-80 rounded-full bg-sky-100 blur-3xl"></div>
  <div class="pointer-events-none absolute -right-24 bottom-0 h-80 w-80 rounded-full bg-lime-100 blur-3xl"></div>
  <div class="container relative mx-auto px-6 lg:px-8">
    <div class="grid gap-6 md:grid-cols-2 lg:gap-8" data-reveal-group<?= $vm->lst('items', 'card') ?>>
      <?php foreach ($vm->each('items') as $i => $it): $accent = $i % 2 === 0 ? $sky : $lime; $wash = $i % 2 === 0 ? 'from-sky-50' : 'from-lime-50'; ?>
      <div data-reveal class="group relative overflow-hidden rounded-[2rem] border border-gray-200 bg-white p-3 shadow-[0_20px_50px_rgba(15,23,42,0.06)] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_40px_80px_rgba(15,23,42,0.1)] lg:p-5 lg:px-6"<?= $vm->item($i) ?>>
        <span class="absolute left-0 top-0 h-full w-1.5 transition-all duration-500 group-hover:w-2.5" style="background: <?= $accent ?>"></span>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br <?= $wash ?> to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
        <span class="pointer-events-none absolute -right-4 -top-6 select-none text-8xl font-black text-gray-900/[0.1] transition-colors duration-500 group-hover:text-gray-900/[0.2]"><?= $it->v('label') ?></span>
        <div class="relative">
          <h3 class="text-3xl font-bold text-gray-900">Our <?= $it->t('label', 'Label') ?></h3>
          <span class="mt-1 block h-0.5 w-10 rounded-full transition-all duration-500 group-hover:w-16" style="background: <?= $accent ?>"></span>
          <p class="mt-5 text-lg font-light leading-relaxed text-gray-600"><?= $it->ta('text', 'Text') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('leadership')): ?>
<section id="leadership" class="bg-white py-10 overflow-hidden"<?= $d->section('leadership', 'Founder message') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid items-center gap-12 lg:grid-cols-[0.75fr_1.45fr] lg:gap-1">
      <div data-reveal="left" class="relative mx-auto max-w-sm lg:mx-0">
        <div class="absolute -bottom-6 -right-6 h-40 w-40 rounded-full bg-lime-100 blur-3xl"></div>
        <div class="relative overflow-hidden rounded-[2rem] border border-gray-100 shadow-[0_30px_80px_rgba(15,23,42,0.15)]">
          <?= $ld->img('image', 'h-[27rem] w-full object-cover', 'Amit Waghmare, Managing Director', ['width' => '600', 'height' => '720'], 'Photo') ?>
          <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-6">
            <p class="text-lg font-bold text-white"><?= $ld->t('name', 'Name') ?></p>
            <p class="text-sm text-sky-300"><?= $ld->t('role', 'Role') ?></p>
          </div>
        </div>
      </div>
      <div data-reveal="right">
        <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-600"><?= $ld->t('eyebrow', 'Eyebrow label') ?></span>
        <svg class="mb-4 h-10 w-10 text-sky-200" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
        <p class="text-xl font-light leading-relaxed text-gray-700 lg:text-2xl"><?= $ld->ta('quote', 'Quote') ?></p>
        <p class="mt-6 text-base leading-relaxed text-gray-600"><?= $ld->ta('message', 'Message') ?></p>
        <div class="mt-8">
          <p class="font-bold text-gray-900"><?= $ld->v('name') ?></p>
          <p class="text-sm text-gray-500"><?= $ld->t('signature_role', 'Signature line') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('numbers')): $stats = $num->each('stats'); $first = $stats[0] ?? null; ?>
<section id="company-numbers" class="relative overflow-hidden bg-gray-950 py-10 text-white"<?= $d->section('numbers', 'By the numbers') ?>>
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(60,170,229,0.1),transparent_60%)]"></div>
  <div class="pointer-events-none absolute inset-0 opacity-[0.04] st-grid-56"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid items-center gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
      <div data-reveal="left">
        <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-200 backdrop-blur-sm"><?= $num->t('eyebrow', 'Eyebrow label') ?></span>
        <h2 class="text-2xl font-bold leading-tight text-white md:text-4xl">
          <?= $num->t('title', 'Heading') ?>
          <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $num->t('highlight', 'Highlighted words') ?></span>
        </h2>
        <?php if ($first): ?>
        <div class="mt-8 flex items-end gap-4"<?= $first->edit(['value' => ['number', 'Number'], 'suffix' => ['text', 'Suffix (e.g. +)'], 'label' => 'text'], 'Headline stat') ?>>
          <p class="text-7xl font-black leading-none lg:text-8xl">
            <span class="bg-gradient-to-r from-sky-300 to-lime-300 bg-clip-text text-transparent" data-counter="<?= e((string)$first->num('value')) ?>" data-suffix="<?= $first->e('suffix') ?>"><?= e((string)$first->num('value')) ?><?= $first->e('suffix') ?></span>
          </p>
          <div class="mb-2">
            <span class="block h-0.5 w-10 rounded-full bg-gradient-to-r from-sky-400 to-lime-400"></span>
            <p class="mt-2 text-sm font-medium uppercase tracking-wide text-gray-400"><?= $first->v('label') ?></p>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div data-reveal="right" class="grid grid-cols-2 divide-x divide-y divide-white/10 overflow-hidden rounded-3xl border border-white/10 bg-white/5 backdrop-blur-sm sm:grid-cols-3"<?= $num->lst('stats', 'stat') ?>>
        <?php foreach ($stats as $i => $s): if ($i === 0) continue; $accent = ($i - 1) % 2 === 0 ? $sky : $lime; ?>
        <div class="group relative p-7 transition-colors duration-300 hover:bg-white/[0.06]"<?= $num->item($i) ?><?= $s->edit(['value' => ['number', 'Number'], 'suffix' => ['text', 'Suffix (e.g. +)'], 'label' => 'text'], 'Stat') ?>>
          <span class="absolute left-7 top-0 h-0.5 w-0 rounded-full transition-all duration-500 group-hover:w-8" style="background: <?= $accent ?>"></span>
          <p class="text-4xl font-bold lg:text-5xl">
            <span class="bg-gradient-to-r from-sky-300 to-lime-300 bg-clip-text text-transparent" data-counter="<?= e((string)$s->num('value')) ?>" data-suffix="<?= $s->e('suffix') ?>"><?= e((string)$s->num('value')) ?><?= $s->e('suffix') ?></span>
          </p>
          <p class="mt-2 text-xs font-medium uppercase tracking-wide text-gray-400"><?= $s->v('label') ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta'), 'cta', true); ?>
