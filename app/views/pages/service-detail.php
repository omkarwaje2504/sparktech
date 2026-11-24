<?php
/**
 * Service detail (ported from src/app/services/[slug]/*).
 * @var int $index  index of the service in services.items
 */
$d = Content::doc('services');
$s = $d->at('items.' . $index);
$lb = $d->at('labels');
$site = Content::doc('site');
$c = $site->at('contact');
$slug = $s->str('slug');
$nav = services_list(edit_mode());
// Sidebar order: services with cards first, then detail-only pages.
uasort($nav, function (Doc $a, Doc $b) {
    return (int)!$a->bool('show_card', true) <=> (int)!$b->bool('show_card', true);
});

$enquiry = function () use ($lb, $c, $site) {
    ob_start(); ?>
<div class="overflow-hidden rounded-3xl border border-white/10 bg-gray-950 text-white">
  <div class="p-6">
    <h3 class="text-xl font-bold"><?= $lb->t('enquiry_title', 'Enquiry card title (all services)') ?></h3>
    <p class="mt-3 text-sm leading-relaxed text-gray-300"><?= $lb->ta('enquiry_text', 'Enquiry card text (all services)') ?></p>
    <a href="<?= $lb->url('enquiry_button.href', '/contact/') ?>"<?= link_edit($lb, 'enquiry_button') ?>
       class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-sky-500 py-3.5 text-xs font-semibold uppercase tracking-[0.12em] text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">
      <?= $lb->v('enquiry_button.label') ?><?= arrow_icon('h-4 w-4') ?>
    </a>
    <div class="mt-6 space-y-3 border-t border-white/10 pt-6 text-sm">
      <a href="<?= $c->url('phone_href') ?>" class="flex items-center gap-3 text-gray-300 transition-colors hover:text-white">
        <span class="text-gray-500"><?= $lb->t('call_label', '"Call" label') ?></span><?= $c->v('phone_display') ?>
      </a>
      <a href="<?= $c->url('email_href') ?>" class="flex items-center gap-3 text-gray-300 transition-colors hover:text-white">
        <span class="text-gray-500"><?= $lb->t('email_label', '"Email" label') ?></span><?= $c->v('email_display') ?>
      </a>
    </div>
  </div>
</div>
<?php return (string)ob_get_clean();
};
?>
<section id="service-content" class="bg-gray-50 pb-10 pt-32 lg:pt-36" data-service-form-name="<?= $s->e('form_name') ?>">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <nav class="mb-6 flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400" aria-label="Breadcrumb">
      <a href="<?= e(url('/')) ?>" class="transition-colors hover:text-sky-600">Home</a>
      <span class="h-1 w-1 rounded-full bg-sky-500/60" aria-hidden="true"></span>
      <a href="<?= e(url('/services/')) ?>" class="transition-colors hover:text-sky-600"><?= Content::doc('services')->at('hero')->e('crumb', 'Services') ?></a>
      <span class="h-1 w-1 rounded-full bg-sky-500/60" aria-hidden="true"></span>
      <span class="text-sky-600" aria-current="page"><?= $s->v('title') ?></span>
    </nav>

    <nav class="relative -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-2 lg:hidden" data-active-scroll aria-label="Services">
      <?php foreach ($nav as $item): $active = $item->str('slug') === $slug; ?>
      <a href="<?= e(url('/services/' . $item->str('slug') . '/')) ?>"<?= $active ? ' data-active' : '' ?>
         class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition <?= $active ? 'border-sky-500 bg-sky-500 text-white shadow-md shadow-sky-500/25' : 'border-gray-200 bg-white text-gray-700 hover:border-sky-300 hover:text-sky-600' ?>">
        <?= e($item->str('card_title') ?: $item->str('title')) ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="grid gap-8 lg:grid-cols-[300px_1fr] lg:gap-10">
      <aside class="hidden lg:block">
        <div class="sticky top-28 space-y-6">
          <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white">
            <div class="h-1.5 w-full bg-gradient-to-r from-[#6EC0EF] via-sky-400 to-[#B0D10E]"></div>
            <p class="px-5 pb-2 pt-5 text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400"><?= $lb->t('sidebar_title', 'Sidebar title (all services)') ?></p>
            <nav class="px-3 pb-3" aria-label="All services">
              <?php foreach ($nav as $item): $active = $item->str('slug') === $slug; ?>
              <a href="<?= e(url('/services/' . $item->str('slug') . '/')) ?>"<?= $active ? ' aria-current="page"' : '' ?><?= $item->bool('visible', true) ? '' : ' data-cms-off="1"' ?>
                 class="group relative mb-1 flex items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-colors <?= $active ? 'text-white' : 'text-gray-700 hover:bg-sky-50 hover:text-sky-600' ?>">
                <?php if ($active): ?><span class="absolute inset-0 rounded-xl bg-gradient-to-r from-sky-500 to-sky-400 shadow-lg shadow-sky-500/25"></span><?php endif; ?>
                <span class="relative"><?= e($item->str('card_title') ?: $item->str('title')) ?></span>
                <?= arrow_icon('relative h-4 w-4 shrink-0 transition ' . ($active ? 'opacity-100' : '-translate-x-1 opacity-0 group-hover:translate-x-0 group-hover:opacity-100')) ?>
              </a>
              <?php endforeach; ?>
            </nav>
          </div>
          <?= $enquiry() ?>
        </div>
      </aside>

      <article data-reveal="up-sm" data-reveal-now class="min-w-0 rounded-3xl border border-gray-200 bg-white p-6 md:p-10"<?= $s->bool('visible', true) ? '' : ' data-cms-off="1"' ?>>
        <?php if (edit_mode()): ?>
        <div class="cms-inline-note"<?= $s->edit([
            'slug' => ['text', 'URL slug (changes the page address!)'],
            'visible' => ['bool', 'Page is published'],
            'show_card' => ['bool', 'Show card in service grids'],
            'in_menu' => ['bool', 'Show in header menu'],
            'menu_title' => ['text', 'Menu label'],
            'card_title' => ['text', 'Card / sidebar title'],
            'card_desc' => ['textarea', 'Card description'],
            'card_image' => ['image', 'Card image'],
            'form_name' => ['text', 'Contact-form service name (pre-selected on the enquiry form)'],
            'seo.title' => ['text', 'SEO title (optional)'],
            'seo.description' => ['textarea', 'SEO description (optional)'],
            'seo.keywords' => ['textarea', 'SEO keywords (comma separated)'],
        ], 'Service settings') ?>>⚙ Service settings — slug, menu, card, SEO</div>
        <?php endif; ?>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-sky-600"><?= $s->t('subtitle', 'Subtitle') ?></p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 md:text-3xl"><?= $s->t('title', 'Title') ?></h1>
        <div class="mt-3 h-1 w-16 rounded-full bg-gradient-to-r from-sky-500 to-lime-400" aria-hidden="true"></div>
        <p class="mt-5 leading-relaxed text-gray-600"><?= $s->ta('description', 'Description') ?></p>

        <div class="relative mt-8 aspect-video overflow-hidden rounded-2xl border border-gray-100">
          <?= $s->img('image', 'absolute inset-0 h-full w-full object-cover', $s->str('title'), ['loading' => 'eager'], 'Main image') ?>
        </div>

        <?php if ($s->count('process') > 0 || edit_mode()): ?>
        <div class="mt-10"<?= $s->edit(['process' => ['lines', 'Process steps (one per line — leave empty to hide)']], 'Process flow') ?>>
          <h3 class="mb-4 text-xl font-bold text-gray-900"><?= $lb->v('process_heading') ?></h3>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($s->lines('process') as $pi => $step): ?>
            <div class="flex items-center gap-2 rounded-xl border border-sky-100 bg-sky-50 px-4 py-2">
              <span class="text-xs font-bold text-sky-500"><?= pad2($pi + 1) ?></span>
              <span class="text-sm font-semibold text-gray-800"><?= e($step) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (!$s->count('process')): ?><span class="text-sm text-gray-400">(No process steps — click edit to add some)</span><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <div<?= $s->lst('sections', 'section') ?>>
          <?php foreach ($s->each('sections') as $si => $sec):
            // Optional section image: position + preset size (see .svc-img in site.css).
            $secImg = $sec->image('image', $sec->str('heading'));
            $imgPos = in_array($sec->str('image_position'), SERVICE_IMG_POSITIONS, true) ? $sec->str('image_position') : 'top';
            $imgSize = in_array($sec->str('image_size'), SERVICE_IMG_SIZES, true) ? $sec->str('image_size') : 'md';
            $imgFields = [
                'image' => ['image', 'Image (optional)', ['removable' => true, 'imageOnly' => true]],
                'image_position' => ['select', 'Image position', [['top', 'Above the text'], ['left', 'Left of the text'], ['right', 'Right of the text']], 'top'],
                'image_size' => ['select', 'Image size', [['sm', 'Small'], ['md', 'Medium'], ['lg', 'Large'], ['xl', 'Extra large (maximum)']], 'md'],
            ];
          ?>
          <div class="svc-sec mt-10"<?= $s->item($si) ?>>
            <h3 class="mb-4 border-l-4 border-sky-500 pl-3 text-xl font-bold text-gray-900"><?= $sec->t('heading', 'Section heading') ?></h3>
            <?php if ($secImg['src'] !== '' && !is_video_src($secImg['src'])): ?>
            <figure class="svc-img svc-img--<?= $imgPos ?> svc-img--<?= $imgSize ?>"<?= $sec->edit($imgFields, 'Section image') ?>>
              <img src="<?= e(url($secImg['src'])) ?>" alt="<?= e($secImg['alt']) ?>" loading="lazy" decoding="async"<?= $sec->mediaStyle('image') ?>>
            </figure>
            <?php elseif (edit_mode()): ?>
            <div class="cms-inline-note mb-4"<?= $sec->edit($imgFields, 'Section image') ?>>🖼 Add an image to this section</div>
            <?php endif; ?>
            <div class="space-y-4"<?= $sec->lst('paragraphs', 'paragraph') ?>>
              <?php foreach (array_keys((array)$sec->get('paragraphs', [])) as $pi): ?>
              <p class="leading-relaxed text-gray-600 [&_strong]:font-bold [&_strong]:text-gray-900"<?= $sec->item($pi) ?>><?= $sec->rich("paragraphs.$pi", 'Paragraph') ?></p>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($s->count('features') > 0 || edit_mode()): ?>
        <div class="mt-10"<?= $s->edit(['features' => ['lines', 'Features (one per line)']], 'Features list') ?>>
          <h3 class="mb-4 border-l-4 border-sky-500 pl-3 text-xl font-bold text-gray-900"><?= $lb->v('features_heading') ?></h3>
          <ul class="grid gap-x-6 gap-y-3 md:grid-cols-2">
            <?php foreach ($s->lines('features') as $f): ?>
            <li class="flex items-start gap-3 text-sm text-gray-700"><?= check_icon('mt-0.5 h-5 w-5 shrink-0 text-lime-500') ?><?= e($f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <?php if ($s->count('applications') > 0 || edit_mode()): ?>
        <div class="mt-10"<?= $s->edit(['applications' => ['lines', 'Applications (one per line)']], 'Applications') ?>>
          <h3 class="mb-4 border-l-4 border-sky-500 pl-3 text-xl font-bold text-gray-900"><?= $lb->v('applications_heading') ?></h3>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($s->lines('applications') as $app): ?>
            <span class="rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700"><?= e($app) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (edit_mode()): ?>
        <div class="cms-inline-note mt-10"<?= $d->edit([
            'labels.process_heading' => ['text', '"Process Flow" heading'],
            'labels.features_heading' => ['text', '"Key Features" heading'],
            'labels.applications_heading' => ['text', '"Applications" heading'],
        ], 'Headings shared by all service pages') ?>>⚙ Shared headings (all service pages)</div>
        <?php endif; ?>
      </article>
    </div>

    <div class="mt-8 lg:hidden"><?= $enquiry() ?></div>
  </div>
</section>

<?= render_blocks($s) ?>

<?php if ($d->shown('detail_cta')) echo cta_band($d->at('detail_cta'), 'detail_cta'); ?>

<script>
  // Remember the viewed service so the contact form can pre-select it.
  (function () {
    var name = <?= json_encode($s->str('form_name'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    if (!name) return;
    var expires = new Date(Date.now() + 30 * 864e5).toUTCString();
    document.cookie = 'SPARKTECH_service_interest=' + encodeURIComponent(name) + '; path=/; expires=' + expires + '; SameSite=Lax';
  })();
</script>
