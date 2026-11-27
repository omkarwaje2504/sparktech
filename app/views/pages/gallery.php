<?php
/** Gallery (ported from src/app/gallery/*). Filters are derived from the projects. */
$d = Content::doc('gallery');
$lb = $d->at('labels');
$projects = $d->each('projects');
$categories = [];
$locations = [];
foreach ($projects as $p) {
    $categories[$p->str('category')] = true;
    $locations[$p->str('location')] = true;
}
$categories = array_keys(array_filter($categories, function ($k) { return $k !== ''; }, ARRAY_FILTER_USE_KEY));
$locations = array_keys(array_filter($locations, function ($k) { return $k !== ''; }, ARRAY_FILTER_USE_KEY));
?>
<?php if ($d->shown('hero')) echo page_hero($d->at('hero'), '/gallery/'); ?>

<?php if ($d->shown('grid')): ?>
<div data-gallery<?= $d->section('grid', 'Projects grid') ?>>
  <section class="border-b border-gray-200 bg-gray-50 py-10">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div data-reveal class="flex flex-col items-center justify-between gap-6 md:flex-row">
        <div class="flex flex-wrap justify-center gap-2" role="group" aria-label="Filter by category">
          <button type="button" data-filter-cat="" aria-pressed="true" class="rounded-full px-5 py-2 text-sm font-semibold transition bg-sky-500 text-white shadow-lg shadow-sky-500/25"<?= $lb->edit(['all_categories' => ['text', '"All" button'], 'all_locations' => ['text', '"All locations" option'], 'empty' => ['text', 'No results message']], 'Filter labels') ?>><?= $lb->v('all_categories') ?></button>
          <?php foreach ($categories as $cat): ?>
          <button type="button" data-filter-cat="<?= e($cat) ?>" aria-pressed="false" class="rounded-full px-5 py-2 text-sm font-semibold transition bg-white text-gray-700 hover:bg-gray-100"><?= e($cat) ?></button>
          <?php endforeach; ?>
        </div>
        <select data-filter-loc aria-label="Filter by location" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-sky-400">
          <option value=""><?= $lb->e('all_locations') ?></option>
          <?php foreach ($locations as $loc): ?><option value="<?= e($loc) ?>"><?= e($loc) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </section>

  <section class="bg-gray-100 py-10">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-reveal-group data-gallery-grid<?= $d->lst('projects', 'project', 'image', 'category', 'youtube') ?>>
        <?php foreach ($projects as $i => $p):
            $img = $p->image('image', $p->str('title'));
            // A YouTube link turns the card into a video: it opens a player pop-up, and
            // shows the video's own thumbnail until a photo is chosen.
            $yt = youtube_id($p->str('youtube'));
            $imgAttrs = $yt !== '' && $img['src'] === '' ? ['src' => 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg'] : [];
        ?>
        <button type="button" data-reveal data-gallery-item
                data-cat="<?= $p->e('category') ?>" data-loc="<?= $p->e('location') ?>"
                data-title="<?= $p->e('title') ?>" data-year="<?= $p->e('year') ?>" data-desc="<?= $p->e('description') ?>" data-img="<?= $yt !== '' ? '' : e(url($img['src'])) ?>"<?= $yt !== '' ? ' data-yt="' . e($yt) . '"' : '' ?>
                class="group relative block overflow-hidden rounded-2xl border border-gray-200 bg-white text-left shadow-md transition-all duration-300 hover:border-sky-300 hover:shadow-xl"<?= $d->item($i) ?><?= $p->edit([
                    'title' => 'text', 'category' => ['text', 'Category (creates a filter button)'], 'location' => ['text', 'Location (creates a filter option)'],
                    'year' => 'text', 'description' => ['textarea', 'Description'],
                    'youtube' => ['text', 'YouTube link (optional: the card then plays this video in a pop-up)'],
                ], 'Project') ?>>
          <div class="relative aspect-[16/11] overflow-hidden">
            <?= $p->img('image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110', $p->str('title'), $imgAttrs) ?>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/10 to-transparent"></div>
            <?php if ($yt !== ''): ?>
            <span class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
              <span class="flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg transition-transform duration-300 group-hover:scale-110" style="background:#ff0000">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" style="margin-left:3px"><path d="M8 5.14v13.72a1 1 0 001.52.85l10.9-6.86a1 1 0 000-1.7L9.52 4.29A1 1 0 008 5.14z"/></svg>
              </span>
            </span>
            <span class="sr-only">Play video</span>
            <?php endif; ?>
            <span class="absolute left-3 top-3 inline-flex items-center rounded-lg border border-white/15 bg-black/30 px-3 py-1 text-[11px] font-semibold uppercase text-sky-200 backdrop-blur-sm"><?= $p->v('category') ?></span>
          </div>
          <div class="p-4">
            <p class="text-[11px] font-medium text-gray-400"><?= $p->v('year') ?></p>
            <h3 class="mt-1 text-sm font-bold leading-snug text-gray-900 transition-colors group-hover:text-sky-600"><?= $p->v('title') ?></h3>
            <p class="mt-2 text-xs text-gray-500"><?= $p->v('location') ?></p>
          </div>
        </button>
        <?php endforeach; ?>
      </div>
      <p data-gallery-empty class="hidden py-16 text-center text-lg text-gray-500"><?= $lb->e('empty') ?></p>
    </div>
  </section>

  <?php /* Pop-up slideshow: every photo of the clicked project's category, one per second, non-stop. */ ?>
  <div data-lightbox class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/80 p-3" role="dialog" aria-modal="true" aria-label="Project photos">
    <div data-lightbox-panel style="height:88vh" class="relative w-full max-w-7xl overflow-hidden rounded-2xl bg-black shadow-2xl">
      <img data-lb-img src="" alt="" class="absolute inset-0 h-full w-full object-contain transition-opacity duration-300">
      <img data-lb-img src="" alt="" class="absolute inset-0 h-full w-full object-contain opacity-0 transition-opacity duration-300">
      <button type="button" data-lightbox-close aria-label="Close" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg transition hover:bg-gray-100"><?= close_icon() ?></button>
      <div data-lb-dots class="absolute inset-x-0 bottom-4 z-10 flex flex-wrap justify-center gap-2 px-4" aria-hidden="true"></div>
    </div>
  </div>

  <?php /* Pop-up player for YouTube projects: the iframe is created on open and removed on close (stops playback). */ ?>
  <div data-yt-modal class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/80 p-3" role="dialog" aria-modal="true" aria-label="Project video">
    <div data-yt-panel class="relative w-full max-w-5xl" style="max-width:min(64rem,calc((100vh - 7rem) * 16 / 9))">
      <div class="mb-3 flex items-center justify-between gap-4">
        <p data-yt-title class="truncate text-sm font-semibold text-white"></p>
        <button type="button" data-yt-close aria-label="Close" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg transition hover:bg-gray-100"><?= close_icon() ?></button>
      </div>
      <div data-yt-frame class="relative aspect-video w-full overflow-hidden rounded-2xl bg-black shadow-2xl"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?= render_blocks($d) ?>

<?php if ($d->shown('cta')) echo cta_band($d->at('cta')); ?>
