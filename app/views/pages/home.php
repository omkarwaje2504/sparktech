<?php
/** Home page (ported from src/app/page.tsx). */
$d = Content::doc('home');
$site = Content::doc('site');
$h = $d->at('hero');
$a = $d->at('about');
$sv = $d->at('services');
$pr = $d->at('process');
$tc = $d->at('technologies');
$g = $d->at('gallery');
$stepColors = ['#B0D10E', '#6EC0EF'];
?>

<?php if ($d->shown('hero')): ?>
<section id="hero-section" class="relative min-h-screen w-full overflow-hidden bg-black text-white"<?= $d->section('hero', 'Hero video banner') ?>>
  <div class="absolute inset-0">
    <video autoplay loop muted playsinline aria-hidden="true" tabindex="-1" poster="<?= $h->url('poster') ?>" class="h-full w-full object-cover"<?= $h->mediaStyle('video') ?><?= $h->bind('video', 'video') ?>
           <?= $h->edit(['video' => ['video', 'Background video (MP4)'], 'poster' => ['image', 'Poster image (shown while loading)']], 'Hero video') ?>>
      <source src="<?= $h->url('video') ?>" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-linear-to-t from-black/90 via-black/10 to-black/0"></div>
    <div class="absolute inset-0 bg-linear-to-r from-black/70 via-transparent to-transparent"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.18),transparent_45%)]"></div>
  </div>

  <div class="relative z-10 mx-auto flex min-h-screen container flex-col justify-between px-6 lg:px-8">
    <div class="pb-16 lg:pb-48 pt-32 md:pt-40 xl:pt-52 2xl:pt-72" data-reveal-group data-stagger="120">
      <span data-reveal class="flex items-center gap-2 mb-3 w-fit rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[10px] font-semibold uppercase text-sky-200 backdrop-blur-sm">
        <?= ping_dot() ?><?= $h->t('eyebrow', 'Eyebrow label') ?>
      </span>
      <h1 data-reveal class="text-4xl font-bold leading-[1.05] md:text-6xl lg:text-6xl 2xl:text-7xl">
        <?= $h->t('title', 'Title') ?>
        <span class="block bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $h->t('highlight', 'Highlighted line') ?></span>
      </h1>
      <p data-reveal class="md:mt-6 lg:mt-4 2xl:mt-6 max-w-xl text-base leading-relaxed text-gray-200 md:text-xl lg:text-lg"><?= $h->ta('subtitle', 'Sub-heading') ?></p>
      <div data-reveal class="mt-8 flex flex-wrap gap-1.5 md:gap-2.5"<?= $h->lst('pills', 'pill') ?>>
        <?php foreach (array_keys((array)$h->get('pills', [])) as $i): ?>
        <span class="inline-flex items-center gap-2 rounded-full bg-[#B0D10E] px-4 py-2 text-sm md:text-xl lg:text-sm font-bold text-black"<?= $h->item($i) ?>>
          <span class="text-sm md:text-xl lg:text-sm font-semibold text-black"><?= pad2($i + 1) ?></span>
          <?= $h->t("pills.$i", 'Pill text') ?>
        </span>
        <?php endforeach; ?>
      </div>
      <div data-reveal class="mt-10 md:mt-20 lg:mt-10 flex flex-col gap-2 md:gap-4 sm:flex-row sm:items-center">
        <a href="<?= $h->url('primary.href') ?>"<?= link_edit($h, 'primary') ?>
           class="inline-flex items-center justify-center gap-3 self-start rounded-full border border-sky-400 px-4 py-2 md:px-6 md:py-3 text-xs md:text-lg lg:text-sm font-semibold uppercase text-sky-600 transition hover:bg-sky-50">
          <?= $h->v('primary.label') ?><?= arrow_icon() ?>
        </a>
        <a href="<?= $h->url('secondary.href') ?>"<?= link_edit($h, 'secondary') ?>
           class="inline-flex w-fit items-center justify-center gap-3 rounded-full border border-white/25 bg-white/5 px-7 py-2 md:py-3 text-xs md:text-lg lg:text-sm font-semibold uppercase text-white backdrop-blur-md transition hover:border-white/50 hover:bg-white/10">
          <?= $h->v('secondary.label') ?>
        </a>
      </div>
    </div>
    <?= scroll_cue() ?>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('about')): ?>
<section id="about-section" class="bg-slate-100 py-10"<?= $d->section('about', 'About / EPC expertise') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)] lg:items-center lg:gap-6 2xl:gap-16">
      <div class="relative" data-reveal="scale">
        <div class="absolute -left-6 top-8 h-40 w-40 rounded-full bg-sky-100 blur-3xl"></div>
        <div class="relative overflow-hidden rounded-3xl border border-gray-100 bg-gray-100 shadow-[0_30px_80px_rgba(15,23,42,0.12)]">
          <?= $a->img('image', 'w-full object-cover md:h-64 lg:h-full', 'SPARKTECH edible oil plant engineering', ['loading' => 'eager', 'width' => '1200', 'height' => '900']) ?>
          <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-gray-950/65 via-gray-950/10 to-transparent"></div>
          <div class="absolute inset-x-0 bottom-0 p-4 md:px-6 2xl:px-8">
            <h2 class="text-2xl font-bold leading-tight text-white md:text-3xl"><?= $a->t('overlay_title', 'Image caption title') ?></h2>
            <p class="text-xs text-gray-100 md:text-sm"><?= $a->ta('overlay_text', 'Image caption text') ?></p>
          </div>
        </div>
      </div>
      <div>
        <div data-reveal>
          <h2 class="text-2xl font-bold leading-7 text-gray-800 md:text-4xl md:leading-8 lg:leading-none">
            <?= $a->t('title', 'Heading') ?><br>
            <span class="text-lg font-light leading-5 md:text-2xl md:leading-none 2xl:text-3xl"><?= $a->t('subtitle', 'Sub-heading') ?></span>
          </h2>
        </div>
        <div class="mt-6 grid gap-3 md:grid-cols-2 lg:mt-8" data-reveal-group<?= $a->lst('items', 'card') ?>>
          <?php foreach ($a->each('items') as $i => $it): ?>
          <div data-reveal class="rounded-3xl border border-gray-200 bg-gray-50 p-4"<?= $a->item($i) ?>>
            <h3 class="text-lg font-bold text-gray-800 lg:text-xl"><?= $it->t('title', 'Card title') ?></h3>
            <p class="text-[13px] leading-relaxed text-gray-600 2xl:text-sm"><?= $it->ta('description', 'Card text') ?></p>
            <a href="<?= $it->url('href') ?>"<?= $d->edit(["about.items.$i.href" => ['url', 'Read more link'], 'about.read_more' => ['text', 'Link text (all cards)']], 'Card link') ?>
               class="inline-flex items-center gap-2 text-sm font-semibold text-sky-500 hover:text-sky-400"><?= $a->v('read_more') ?><?= arrow_icon() ?></a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('services')): ?>
<section id="services-section" class="py-10"<?= $d->section('services', 'Our solutions') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-12 flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-end">
      <h2 class="flex-1 text-2xl font-bold leading-7 text-gray-800 md:text-4xl md:leading-8 lg:leading-none"><?= $sv->t('title', 'Heading') ?></h2>
      <a href="<?= $sv->url('button.href') ?>"<?= link_edit($sv, 'button') ?>
         class="inline-flex items-center gap-3 self-start rounded-full border border-sky-400 px-4 py-2 md:px-6 md:py-3 text-xs md:text-sm font-semibold uppercase text-sky-600 transition hover:bg-sky-50">
        <?= $sv->v('button.label') ?><?= arrow_icon('h-4 w-4') ?>
      </a>
    </div>
    <?php $servicesDoc = Content::doc('services'); ?>
    <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-4" data-reveal-group<?= $servicesDoc->lst('items', 'service') ?>>
      <?php foreach (services_list(edit_mode()) as $i => $s): if (!$s->bool('show_card', true)) continue; ?>
      <div data-reveal<?= $servicesDoc->item($i, "items.$i.visible") ?>><?= service_card($s) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('process')): ?>
<section id="process-section" class="bg-linear-to-r from-[#6EC0EF]/20 to-[#B0D10E]/20 py-10"<?= $d->section('process', 'Process steps') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-12 text-center">
      <h2 class="text-2xl font-bold leading-tight text-gray-800 md:text-4xl"><?= $pr->t('title', 'Heading') ?></h2>
      <p class="mx-auto mt-2 max-w-3xl text-base text-gray-600 sm:text-lg"><?= $pr->ta('subtitle', 'Sub-heading') ?></p>
    </div>
    <div class="flex gap-4 overflow-x-auto pb-4 lg:grid lg:grid-cols-5 2xl:gap-16 lg:overflow-visible" data-reveal-group<?= $pr->lst('steps', 'step') ?>>
      <?php $steps = $pr->each('steps'); $n = count($steps); foreach ($steps as $i => $st): $color = $stepColors[$i % 2]; ?>
      <div data-reveal class="group relative flex w-64 shrink-0 flex-col items-center text-center lg:w-auto"<?= $pr->item($i) ?>>
        <div class="relative flex w-full items-center justify-center">
          <?php if ($i < $n - 1): ?><span class="absolute left-1/2 top-1/2 hidden h-0.5 w-full -translate-y-1/2 border-t-2 border-dashed border-gray-300 lg:block"></span><?php endif; ?>
          <span class="relative z-10 flex h-11 w-11 items-center justify-center rounded-full text-xs font-bold text-white shadow-md transition-transform duration-300 group-hover:scale-110" style="background: <?= $color ?>"><?= pad2($i + 1) ?></span>
        </div>
        <div class="mt-6 flex h-28 w-28 md:h-40 md:w-40 items-center justify-center rounded-full transition-all duration-300">
          <?= $st->img('image', '', 'Step ' . ($i + 1) . ' icon', ['width' => '400', 'height' => '400'], 'Step icon') ?>
        </div>
        <h3 class="text-xl md:text-xl lg:text-2xl font-semibold leading-tight text-gray-900"><?= $st->t('title', 'Step title') ?></h3>
        <p class="mt-2 px-1 text-sm md:text-md leading-relaxed text-gray-600"><?= $st->ta('description', 'Step text') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('technologies')): ?>
<section id="technologies-section" class="relative overflow-hidden bg-slate-800 pt-10 pb-16"<?= $d->section('technologies', 'Technologies') ?>>
  <div class="pointer-events-none absolute -right-20 top-0 h-72 w-72 rounded-full bg-sky-500/15 blur-3xl"></div>
  <div class="pointer-events-none absolute -left-16 bottom-0 h-72 w-72 rounded-full bg-lime-400/10 blur-3xl"></div>
  <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-6">
      <h2 class="text-2xl font-bold leading-tight text-white md:text-4xl"><?= $tc->t('title', 'Heading') ?></h2>
      <p class="mt-2 max-w-4xl text-base text-gray-300 sm:text-lg"><?= $tc->ta('subtitle', 'Sub-heading') ?></p>
    </div>

    <?php $techs = $tc->each('items'); ?>
    <div class="hidden gap-3 md:flex" data-reveal-group data-tech-accordion<?= $tc->lst('items', 'technology', 'image') ?>>
      <?php foreach ($techs as $i => $t): $active = $i === 0; ?>
      <div data-reveal data-tech-panel class="flex <?= $active ? 'flex-[6] lg:flex-[5]' : 'flex-[1]' ?> transition-all duration-500 ease-in-out min-w-0"<?= $tc->item($i) ?>>
        <div class="group relative h-full w-full overflow-hidden rounded-2xl border border-white/10 shadow-md"<?= $t->edit(['title' => 'text', 'href' => ['url', 'Learn more link'], 'image' => ['image', 'Image']], 'Technology') ?>>
          <div class="relative h-[16rem] w-full overflow-hidden bg-gray-800">
            <?= $t->img('image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-700 ' . ($active ? 'scale-105' : 'scale-100'), $t->str('title'), ['data-tech-img' => '1']) ?>
            <a href="<?= $t->url('href') ?>" aria-label="<?= $t->e('title') ?>" class="absolute inset-0 bg-linear-to-t from-black/90 via-black/35 to-transparent"></a>
            <span class="absolute left-3 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#B0D10E] text-xs font-bold text-gray-950 shadow-lg"><?= pad2($i + 1) ?></span>
            <span data-tech-bar class="absolute left-0 top-0 h-full w-1 bg-[#B0D10E] transition-opacity duration-300 <?= $active ? 'opacity-100' : 'opacity-0' ?>"></span>
          </div>
          <div data-tech-collapsed class="pointer-events-none absolute inset-0 flex items-end justify-center pb-6 transition-opacity duration-300 <?= $active ? 'opacity-0' : 'opacity-100' ?>">
            <h3 class="whitespace-nowrap text-base font-bold uppercase tracking-wide text-white [writing-mode:vertical-rl] rotate-180"><?= $t->v('title') ?></h3>
          </div>
          <div data-tech-expanded class="absolute inset-x-0 bottom-0 p-6 transition-opacity duration-500 <?= $active ? 'opacity-100 delay-150' : 'pointer-events-none opacity-0' ?>">
            <h3 class="text-xl font-bold leading-tight text-white lg:text-2xl"><?= $t->v('title') ?></h3>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <a href="<?= e(url('/contact/?interest=' . rawurlencode($t->str('title')))) ?>"
                 class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-5 py-2.5 text-xs font-semibold uppercase text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">
                <?= $tc->t('enquire_label', 'Enquiry button text (all cards)') ?><?= arrow_icon('h-4 w-4') ?>
              </a>
              <a href="<?= $t->url('href') ?>" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/5 px-5 py-2.5 text-xs font-semibold uppercase text-white backdrop-blur-md transition hover:border-white/50 hover:bg-white/10">
                <?= $tc->t('learn_label', 'Learn more text (all cards)') ?>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:hidden" data-reveal-group>
      <?php foreach ($techs as $i => $t): ?>
      <div data-reveal class="group relative h-32 overflow-hidden rounded-2xl border border-white/10 shadow-md">
        <?= $t->img('image', 'absolute inset-0 h-full w-full object-cover', $t->str('title')) ?>
        <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/90 via-black/35 to-transparent"></div>
        <div class="absolute bottom-0 p-2">
          <div class="flex items-center gap-1">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#6EC0EF] text-xs font-bold text-white shadow-lg"><?= pad2($i + 1) ?></span>
            <h3 class="text-lg font-bold text-white"><?= $t->t('title', 'Technology title') ?></h3>
          </div>
          <a href="<?= e(url('/contact/?interest=' . rawurlencode($t->str('title')))) ?>" class="mt-1 inline-flex items-center gap-2 rounded-full bg-sky-500 px-3 py-1.5 text-xs font-semibold uppercase text-white">
            <?= $tc->v('enquire_label') ?><?= arrow_icon('h-4 w-4') ?>
          </a>
          <a href="<?= $t->url('href') ?>" class="ml-1 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-md transition hover:border-white/50 hover:bg-white/10"><?= $tc->v('learn_label') ?></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($d->shown('gallery')): ?>
<section id="gallery-section" class="bg-gray-100 py-10"<?= $d->section('gallery', 'Projects carousel') ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-bold leading-7 text-gray-800 md:text-4xl md:leading-8 lg:leading-none"><?= $g->t('title', 'Heading') ?></h2>
        <p class="mt-2 text-base text-gray-600 sm:text-lg"><?= $g->ta('subtitle', 'Sub-heading') ?></p>
      </div>
      <a href="<?= $g->url('button.href') ?>"<?= link_edit($g, 'button') ?>
         class="inline-flex items-center gap-3 self-start rounded-full border border-sky-400 px-4 py-2 md:px-6 md:py-3 text-xs md:text-sm font-semibold uppercase text-sky-600 transition hover:bg-sky-50">
        <?= $g->v('button.label') ?><?= arrow_icon() ?>
      </a>
    </div>

    <div class="relative mt-3" data-reveal="fade" data-carousel data-autoplay="3500">
      <button type="button" data-carousel-prev aria-label="Previous"
              class="absolute left-0 top-1/2 z-20 flex h-8 w-8 md:h-12 md:w-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-lg transition hover:border-sky-500 hover:bg-sky-500 hover:text-white">
        <?= arrow_icon('h-5 w-5 rotate-180') ?>
      </button>
      <button type="button" data-carousel-next aria-label="Next"
              class="absolute right-0 top-1/2 z-20 flex h-8 w-8 md:h-12 md:w-12 -translate-y-1/2 translate-x-1/2 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-lg transition hover:border-sky-500 hover:bg-sky-500 hover:text-white">
        <?= arrow_icon('h-5 w-5') ?>
      </button>
      <div class="overflow-hidden" data-carousel-viewport>
        <div class="flex touch-pan-y select-none cursor-grab transition-transform duration-500 ease-in-out" data-carousel-track<?= $g->lst('projects', 'project', 'image') ?>>
          <?php foreach ($g->each('projects') as $i => $p): ?>
          <div class="shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3" data-carousel-slide<?= $g->item($i) ?>>
            <div class="group relative block overflow-hidden rounded-[2rem] border border-white/10 bg-white/5"<?= $p->edit(['proof' => ['text', 'Badge'], 'title' => 'text', 'location' => 'text', 'image' => ['image', 'Image']], 'Project') ?>>
              <div class="relative aspect-[16/10] overflow-hidden">
                <?= $p->img('image', 'absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105', $p->str('title') . ' in ' . $p->str('location'), ['draggable' => 'false']) ?>
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/10 to-transparent"></div>
              </div>
              <div class="absolute inset-x-0 bottom-0 p-6 lg:p-7">
                <div class="inline-flex items-center rounded-xl border border-white/15 bg-black/25 px-3 py-1 text-[11px] font-semibold uppercase text-sky-200 backdrop-blur-sm"><?= $p->v('proof') ?></div>
                <h3 class="mt-3 text-xl font-semibold text-white lg:text-2xl"><?= $p->v('title') ?></h3>
                <p class="mt-2 text-sm leading-6 text-gray-200"><?= $p->v('location') ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="mt-3 flex justify-center gap-2" data-carousel-dots></div>
    </div>
  </div>
</section>
<?php endif; ?>

<?= render_blocks($d) ?>
