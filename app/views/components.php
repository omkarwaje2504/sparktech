<?php
/**
 * Shared UI components — PHP ports of src/components/ui/*.
 * Every component reads its text from a scoped Doc so it stays editable.
 */

/** Optional field: render when it has content, or always while editing. */
function show_field(Doc $d, string $path): bool
{
    return edit_mode() || $d->has($path);
}

/** Pill / eyebrow chip. */
function eyebrow_chip(string $innerHtml, string $tone = 'light', bool $ping = false, string $class = ''): string
{
    $style = $tone === 'dark'
        ? 'border-white/15 bg-white/5 text-sky-200 backdrop-blur-sm'
        : 'border-sky-200 bg-sky-50 text-sky-600';
    return '<span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.22em] '
        . $style . ($class ? ' ' . $class : '') . '">' . ($ping ? ping_dot() : '') . $innerHtml . '</span>';
}

/**
 * Link editor attributes for an object {label, href}.
 */
function link_edit(Doc $d, string $path, string $name = 'Button'): string
{
    return $d->edit([
        "$path.label" => ['text', 'Button text'],
        "$path.href" => ['url', 'Link (page URL, tel:, mailto: or https://)'],
    ], $name);
}

function is_external_href(string $href): bool
{
    return (bool)preg_match('~^(https?:)?//~i', $href) && !str_contains($href, (string)($_SERVER['HTTP_HOST'] ?? '~'));
}

/** Primary / secondary / outline CTA with trailing arrow. $path → {label, href}. */
function cta_button(Doc $d, string $path, string $variant = 'primary', string $class = ''): string
{
    $base = 'group inline-flex items-center justify-center gap-3 rounded-full px-7 py-4 text-xs font-semibold uppercase tracking-[0.12em] transition';
    if ($variant === 'primary') {
        $style = 'bg-sky-500 text-white shadow-lg shadow-sky-500/25 hover:bg-sky-400';
    } elseif ($variant === 'secondary') {
        $style = 'border border-white/25 bg-white/5 text-white backdrop-blur-md hover:border-white/50 hover:bg-white/10';
    } else {
        $style = 'border border-sky-400 text-sky-600 hover:bg-sky-50';
    }
    $href = $d->str("$path.href");
    $target = is_external_href($href) ? ' target="_blank" rel="noopener noreferrer"' : '';
    return '<a href="' . e(url($href)) . '"' . $target . ' class="' . $base . ' ' . $style . ($class ? ' ' . $class : '') . '"'
        . link_edit($d, $path) . '>' . $d->v("$path.label")
        . arrow_icon('h-4 w-4 transition-transform duration-300 group-hover:translate-x-1') . '</a>';
}

/** Centered section heading with optional eyebrow + gradient tail word. $h → {eyebrow,title,highlight,subtitle}. */
function section_heading(Doc $h, string $tone = 'light', string $align = 'center', string $class = '', bool $ping = false): string
{
    $heading = $tone === 'dark' ? 'text-white' : 'text-gray-800';
    $sub = $tone === 'dark' ? 'text-gray-300' : 'text-gray-600';
    $gradient = $tone === 'dark' ? 'from-sky-300 via-white to-lime-300' : 'from-[#3CAAE5] to-[#99C71E]';
    $wrap = $align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl';

    $html = '<div class="' . $wrap . ($class ? ' ' . $class : '') . '">';
    if (show_field($h, 'eyebrow')) {
        $html .= eyebrow_chip($h->t('eyebrow', 'Eyebrow label'), $tone, $ping, 'mb-3');
    }
    $html .= '<h2 class="text-2xl font-bold leading-7 md:text-4xl md:leading-8 lg:leading-none ' . $heading . '">' . $h->t('title', 'Heading');
    if (show_field($h, 'highlight')) {
        $html .= ' <span class="bg-gradient-to-r ' . $gradient . ' bg-clip-text text-transparent">' . $h->t('highlight', 'Highlighted words') . '</span>';
    }
    $html .= '</h2>';
    if (show_field($h, 'subtitle')) {
        $html .= '<p class="mt-3 text-base leading-relaxed sm:text-lg ' . $sub . '">' . $h->ta('subtitle', 'Sub-heading') . '</p>';
    }
    return $html . '</div>';
}

/** Soft section blobs (decoration only). */
function blob(string $class, string $tone = 'sky'): string
{
    $bg = $tone === 'lime' ? 'bg-lime-400/10' : ($tone === 'indigo' ? 'bg-indigo-500/10' : 'bg-sky-500/15');
    return '<div aria-hidden="true" class="pointer-events-none absolute h-72 w-72 rounded-full blur-[120px] ' . $bg . ' ' . $class . '"></div>';
}

function scroll_cue(): string
{
    return '<div aria-hidden="true" class="st-fade-late pointer-events-none absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-white/50 lg:flex">'
        . '<span class="text-[10px] font-medium uppercase tracking-[0.3em]">Scroll</span>'
        . '<span class="flex h-9 w-5 items-start justify-center rounded-full border border-white/25 p-1"><span class="h-2 w-1 animate-bounce rounded-full bg-white/60"></span></span></div>';
}

/**
 * Interior-page hero. $h → {eyebrow, title, highlight, subtitle, image, crumb, primary{}, secondary{}}
 */
function page_hero(Doc $h, string $crumbHref, string $sectionKey = 'hero'): string
{
    $root = new Doc($h->name);
    ob_start(); ?>
<section class="relative w-full overflow-hidden bg-gray-950 text-white"<?= $root->section($sectionKey, 'Hero banner') ?>>
  <div class="absolute inset-0">
    <?= $h->img('image', 'absolute inset-0 h-full w-full object-cover', '', ['loading' => 'eager', 'fetchpriority' => 'high'], 'Banner background') ?>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/80 to-gray-950/40"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/70 to-transparent"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(60,170,229,0.18),transparent_45%)]"></div>
  </div>
  <div aria-hidden="true" class="pointer-events-none absolute -left-20 top-1/3 h-72 w-72 rounded-full bg-sky-500/15 blur-[120px]"></div>
  <div aria-hidden="true" class="pointer-events-none absolute -right-16 bottom-0 h-72 w-72 rounded-full bg-lime-400/10 blur-[120px]"></div>

  <div class="container relative z-10 mx-auto flex items-center px-4 pb-14 pt-32 sm:px-6 lg:px-8 lg:pb-20 lg:pt-40">
    <div class="grid w-full items-center gap-12">
      <div data-reveal-group data-reveal-now>
        <nav data-reveal aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/60">
          <a href="<?= e(url('/')) ?>" class="transition-colors hover:text-white">Home</a>
          <span class="flex items-center gap-2">
            <span class="h-1 w-1 rounded-full bg-sky-500/60" aria-hidden="true"></span>
            <a href="<?= e(url($crumbHref)) ?>" aria-current="page" class="text-sky-300 transition-colors hover:text-sky-200"><?= $h->t('crumb', 'Breadcrumb label') ?></a>
          </span>
        </nav>
        <?php if (show_field($h, 'eyebrow')): ?>
        <div data-reveal class="mb-5"><?= eyebrow_chip($h->t('eyebrow', 'Eyebrow label'), 'dark', true) ?></div>
        <?php endif; ?>
        <h1 data-reveal class="text-3xl font-bold leading-[1.08] md:text-4xl lg:text-5xl 2xl:text-6xl">
          <?= $h->t('title', 'Title') ?>
          <?php if (show_field($h, 'highlight')): ?>
          <span class="block bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $h->t('highlight', 'Highlighted line') ?></span>
          <?php endif; ?>
        </h1>
        <?php if (show_field($h, 'subtitle')): ?>
        <p data-reveal class="mt-4 max-w-3xl text-sm leading-relaxed text-gray-300 lg:mt-6 lg:text-base 2xl:text-lg"><?= $h->ta('subtitle', 'Sub-heading') ?></p>
        <?php endif; ?>
        <?php if ($h->has('primary.label') || $h->has('secondary.label')): ?>
        <div data-reveal class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center lg:mt-10">
          <?php if ($h->has('primary.label')) echo cta_button($h, 'primary', 'primary'); ?>
          <?php if ($h->has('secondary.label')) echo cta_button($h, 'secondary', 'secondary'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?= scroll_cue() ?>
</section>
<?php
    return (string)ob_get_clean();
}

/**
 * Closing call-to-action band. $c → {eyebrow, title, highlight, subtitle, primary{label,href}}
 */
function cta_band(Doc $c, string $sectionKey = 'cta', bool $tight = false, string $attrs = ''): string
{
    $root = new Doc($c->name);
    $site = Content::doc('site');
    ob_start(); ?>
<section class="overflow-hidden bg-white py-10"<?= $sectionKey !== '' ? $root->section($sectionKey, 'Call to action') : '' ?><?= $attrs ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal="scale" class="relative overflow-hidden rounded-[2.5rem] bg-gray-950 px-8 <?= $tight ? 'py-5' : 'py-10' ?> text-center shadow-[0_40px_100px_rgba(15,23,42,0.25)] lg:px-16">
      <div class="pointer-events-none absolute -left-20 -top-20 h-72 w-72 rounded-full bg-sky-500/30 blur-[100px]"></div>
      <div class="pointer-events-none absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-lime-400/25 blur-[100px]"></div>
      <?php if ($tight): ?><div class="pointer-events-none absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-sky-500/10 to-transparent"></div><?php endif; ?>
      <div class="pointer-events-none absolute inset-0 opacity-[0.05] st-grid-40"></div>
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-sky-400 to-transparent"></div>
      <div class="relative">
        <?php if (show_field($c, 'eyebrow')): ?>
        <?= eyebrow_chip($c->t('eyebrow', 'Eyebrow label'), 'dark', true, $tight ? 'mb-1' : 'mb-4') ?>
        <?php endif; ?>
        <h2 class="text-2xl font-bold leading-7 text-white md:text-4xl md:leading-8 lg:leading-none">
          <?= $c->t('title', 'Heading') ?>
          <span class="bg-gradient-to-r from-sky-300 via-white to-lime-300 bg-clip-text text-transparent"><?= $c->t('highlight', 'Highlighted words') ?></span>
        </h2>
        <?php if (show_field($c, 'subtitle')): ?>
        <p class="mx-auto <?= $tight ? 'mt-1' : 'mt-3' ?> max-w-2xl text-base leading-relaxed text-gray-400 sm:text-lg"><?= $c->ta('subtitle', 'Sub-heading') ?></p>
        <?php endif; ?>
        <div class="<?= $tight ? 'mt-4' : 'mt-6' ?> flex flex-col items-center justify-center gap-3 sm:flex-row">
          <a href="<?= $c->url('primary.href', '/contact/') ?>"<?= link_edit($c, 'primary', 'Main button') ?>
             class="group inline-flex items-center justify-center gap-3 rounded-full bg-gradient-to-r from-sky-500 to-cyan-500 px-8 py-4 text-sm font-bold uppercase tracking-[0.15em] text-white shadow-lg shadow-sky-500/30 transition hover:shadow-xl hover:shadow-sky-500/40">
            <?= $c->v('primary.label') ?>
            <?= arrow_icon('h-4 w-4 transition-transform duration-300 group-hover:translate-x-1') ?>
          </a>
          <a href="<?= $site->url('contact.phone_href') ?>"<?= $site->edit(['cta_phone_label' => ['text', 'Button text (all pages)'], 'contact.phone_href' => ['url', 'Phone link (tel:)']], 'Phone button') ?>
             class="inline-flex items-center justify-center gap-3 rounded-full border border-white/20 bg-white/5 px-8 py-4 text-sm font-bold uppercase tracking-[0.15em] text-white backdrop-blur-md transition hover:border-white/40 hover:bg-white/10">
            <?= $site->v('cta_phone_label') ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
    return (string)ob_get_clean();
}

/** Service card (home + services listing). $s → one services.items entry. */
function service_card(Doc $s): string
{
    $title = $s->str('card_title') ?: $s->str('title');
    $labels = Content::doc('services')->at('labels');
    ob_start(); ?>
<a href="<?= e(url('/services/' . $s->str('slug') . '/')) ?>"<?= $s->edit([
        'card_title' => ['text', 'Card title'],
        'card_desc' => ['textarea', 'Card description'],
        'card_image' => ['image', 'Card image'],
        'menu_title' => ['text', 'Menu label (header dropdown)'],
        'show_card' => ['bool', 'Show card in service grids'],
        'in_menu' => ['bool', 'Show in header menu'],
        'visible' => ['bool', 'Service page is published'],
    ], 'Service card') ?>
   class="group relative block overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md transition-all duration-300 hover:border-sky-300 hover:shadow-xl">
  <div class="relative h-40 w-full overflow-hidden bg-gray-200 md:h-64">
    <?= $s->img('card_image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110', $title, [], 'Card image') ?>
    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 transition-all duration-300 group-hover:opacity-0">
      <h3 class="text-lg font-bold leading-tight text-white lg:text-xl"><?= $s->v('card_title') ?></h3>
    </div>
  </div>
  <div class="absolute inset-0 flex flex-col items-start justify-end bg-gradient-to-t from-black/95 via-black/70 to-black/0 p-6 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
    <p aria-hidden="true" class="mb-1 text-lg font-bold leading-tight text-white lg:text-xl"><?= $s->v('card_title') ?></p>
    <p class="mb-4 text-sm leading-tight text-gray-100"><?= $s->v('card_desc') ?></p>
    <div class="inline-flex items-center gap-2 text-sm font-semibold text-sky-300"><?= $labels->t('learn_more', 'Card link text (all cards)') ?><?= arrow_icon('h-4 w-4') ?></div>
  </div>
</a>
<?php
    return (string)ob_get_clean();
}

/** Visible services (published), in list order, with their list index. */
function services_list(bool $includeHidden = false): array
{
    $doc = Content::doc('services');
    $out = [];
    foreach ($doc->each('items') as $i => $s) {
        if (!$includeHidden && !$s->bool('visible', true)) {
            continue;
        }
        $out[$i] = $s;
    }
    return $out;
}
