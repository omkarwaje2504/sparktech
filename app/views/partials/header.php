<?php
/** Site header (ported from src/components/Header.tsx). @var Doc $site */
$c = $site->at('contact');
$nav = $site->at('nav');
$servicesDoc = Content::doc('services');
$menuServices = array_filter(services_list(edit_mode()), function (Doc $s) {
    return $s->bool('in_menu', true);
});
$linkCls = function (string $href): string {
    return is_active_path($href) ? 'text-sky-600' : 'text-gray-700 hover:text-sky-600';
};
$mLinkCls = function (string $href): string {
    return is_active_path($href) ? 'bg-sky-50 text-sky-600' : 'text-gray-700 hover:bg-gray-50';
};
$current = function (string $href): string {
    return is_active_path($href) ? ' aria-current="page"' : '';
};
$showServices = $site->bool('nav.show_services', true);
$navLinkEdit = function (string $path) use ($site) {
    return $site->edit(["$path.label" => ['text', 'Menu label'], "$path.href" => ['url', 'Link']], 'Menu link');
};
?>
<header id="site-header" data-top-class="bg-white shadow-sm" data-scrolled-class="bg-white/95 backdrop-blur-md shadow-lg"
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 bg-white shadow-sm">
  <div class="w-full bg-slate-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row w-full justify-end lg:items-center lg:gap-4 py-1 lg:py-0">
      <div class="flex justify-end items-center lg:py-1">
        <?= feather_phone('text-slate-700') ?>
        <span class="text-slate-700 ml-2 text-xs lg:text-sm"><?= $c->t('phone_display', 'Phone number (shown)') ?></span>
        <div class="h-4 w-0.5 bg-slate-700 lg:hidden mx-3" aria-hidden="true"></div>
        <div class="flex items-center gap-2 lg:hidden"<?= $site->lst('social', 'social link') ?>>
          <?php foreach ($site->each('social') as $i => $s): ?>
          <a href="<?= $s->url('href') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $s->e('name') ?>"
             class="text-gray-600 hover:text-sky-600 transition-colors"<?= $site->item($i) ?><?= $s->edit(['name' => 'text', 'href' => 'url', 'icon' => ['select', 'Icon', array_keys(ST_SOCIAL)]], 'Social link') ?>>
            <?= social_icon($s->str('icon'), 'h-3 w-3') ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="h-4 w-0.5 bg-slate-700 hidden lg:flex" aria-hidden="true"></div>
      <div class="flex justify-end items-center lg:py-1">
        <?= feather_mail('text-slate-700') ?>
        <span class="text-slate-700 ml-2 text-xs lg:text-sm"><?= $c->t('header_emails', 'Header e-mail line') ?></span>
      </div>
      <div class="h-4 w-0.5 bg-slate-700 hidden lg:flex" aria-hidden="true"></div>
      <div class="items-center gap-4 hidden lg:flex"<?= $site->lst('social', 'social link') ?>>
        <?php foreach ($site->each('social') as $i => $s): ?>
        <a href="<?= $s->url('href') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $s->e('name') ?>"
           class="text-gray-600 hover:text-sky-600 transition-colors"<?= $site->item($i) ?><?= $s->edit(['name' => 'text', 'href' => 'url', 'icon' => ['select', 'Icon', array_keys(ST_SOCIAL)]], 'Social link') ?>>
          <?= social_icon($s->str('icon'), 'h-4 w-4') ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center transition-all duration-500 py-1">
      <a href="<?= e(url('/')) ?>" rel="home" class="flex items-center gap-3 group shrink-0">
        <?= $site->img('brand.logo', 'w-auto transition-all duration-500 group-hover:scale-105 h-14 lg:h-18', 'SPARKTECH Processes LLP',
            ['loading' => 'eager', 'width' => '1000', 'height' => '1000', 'data-top-class' => 'h-14 lg:h-18', 'data-scrolled-class' => 'h-13 lg:h-16', 'id' => 'site-logo'], 'Logo') ?>
      </a>

      <nav class="hidden lg:flex items-center gap-1" aria-label="Main navigation">
        <div class="flex items-center gap-1"<?= $site->lst('nav.before', 'menu link') ?>>
          <?php foreach ($site->each('nav.before') as $i => $l): ?>
          <a href="<?= $l->url('href') ?>" class="text-sm font-medium tracking-wide transition-colors duration-300 py-2 px-3 rounded-lg <?= $linkCls($l->str('href')) ?>"<?= $current($l->str('href')) ?><?= $site->item($i) ?><?= $navLinkEdit("nav.before.$i") ?>><?= $l->v('label') ?></a>
          <?php endforeach; ?>
        </div>

        <?php if ($showServices || edit_mode()): ?>
        <div class="relative" data-dropdown<?= $showServices ? '' : ' data-cms-off="1"' ?><?= $site->edit([
            'nav.services_label' => ['text', 'Menu label'],
            'nav.services_all_label' => ['text', '"View all" link text'],
            'nav.services_mobile_label' => ['text', 'Menu label on phones'],
            'nav.show_services' => ['bool', 'Show the services dropdown in the menu'],
        ], 'Services dropdown') ?>>
          <button type="button" data-dropdown-toggle aria-expanded="false" aria-haspopup="true" aria-controls="services-menu"
                  class="text-sm font-medium tracking-wide transition-colors duration-300 flex items-center gap-1 py-2 px-3 rounded-lg <?= is_active_path('/services/') ? 'text-sky-600' : 'text-gray-700 hover:text-sky-600' ?>">
            <?= $nav->t('services_label', 'Services menu label') ?>
            <?= chevron_down('w-4 h-4 transition-transform duration-300') ?>
          </button>
          <div data-dropdown-menu id="services-menu" class="hidden absolute top-full left-1/2 -translate-x-1/2 pt-3 w-72">
            <div class="bg-white shadow-2xl rounded-2xl border border-gray-100 p-2 overflow-hidden">
              <div class="max-h-[70vh] overflow-y-auto">
                <?php foreach ($menuServices as $i => $s): ?>
                <a href="<?= e(url('/services/' . $s->str('slug') . '/')) ?>" class="block px-4 py-2.5 text-sm text-gray-700 rounded-lg hover:bg-sky-50 hover:text-sky-600 transition-colors"<?= $current('/services/' . $s->str('slug') . '/') ?><?= $s->bool('visible', true) ? '' : ' data-cms-off="1"' ?>><?= $s->t('menu_title', 'Menu label') ?></a>
                <?php endforeach; ?>
              </div>
              <div class="border-t border-gray-100 mt-1 pt-1">
                <a href="<?= e(url('/services/')) ?>" class="flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-sky-600 hover:bg-sky-50 rounded-lg transition-colors group">
                  <?= $nav->t('services_all_label', '"View all" label') ?>
                  <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="flex items-center gap-1"<?= $site->lst('nav.after', 'menu link') ?>>
          <?php foreach ($site->each('nav.after') as $i => $l): ?>
          <a href="<?= $l->url('href') ?>" class="text-sm font-medium tracking-wide transition-colors duration-300 py-2 px-3 rounded-lg <?= $linkCls($l->str('href')) ?>"<?= $current($l->str('href')) ?><?= $site->item($i) ?><?= $navLinkEdit("nav.after.$i") ?>><?= $l->v('label') ?></a>
          <?php endforeach; ?>
        </div>
      </nav>

      <a href="<?= $nav->url('cta_href', '/contact/') ?>"<?= $site->edit(['nav.cta_small' => ['text', 'Small text'], 'contact.phone_display' => ['text', 'Phone number (shown)'], 'nav.cta_href' => ['url', 'Link']], 'Header enquiry button') ?>
         class="hidden lg:flex items-center gap-3 px-6 py-2 bg-gray-900 text-white text-md font-bold rounded-full hover:bg-sky-600 transition-all duration-300 hover:shadow-lg hover:shadow-sky-500/20 whitespace-nowrap">
        <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center" aria-hidden="true"><?= phone_solid_icon('w-4 h-4') ?></span>
        <span class="flex flex-col">
          <span class="text-xs font-light"><?= $nav->v('cta_small') ?></span>
          <?= $c->v('phone_display') ?>
        </span>
      </a>

      <button type="button" id="mobile-toggle" class="lg:hidden p-2 -mr-2 rounded-lg hover:bg-gray-50 transition-colors" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
        <div class="w-6 h-5 relative flex flex-col justify-between" aria-hidden="true">
          <span data-bar="1" class="w-full h-0.5 bg-gray-900 rounded-full transition-all duration-300"></span>
          <span data-bar="2" class="w-full h-0.5 bg-gray-900 rounded-full transition-opacity duration-300"></span>
          <span data-bar="3" class="w-full h-0.5 bg-gray-900 rounded-full transition-all duration-300"></span>
        </div>
      </button>
    </div>

    <div id="mobile-menu" class="lg:hidden overflow-hidden transition-all duration-500 ease-in-out max-h-0 opacity-0">
      <nav class="flex flex-col gap-1 overflow-y-auto max-h-[70vh]" aria-label="Mobile navigation">
        <div class="flex flex-col gap-1">
          <?php foreach ($site->each('nav.before') as $l): ?>
          <a href="<?= $l->url('href') ?>" class="text-base font-medium px-4 py-3 rounded-xl transition-all <?= $mLinkCls($l->str('href')) ?>"<?= $current($l->str('href')) ?>><?= $l->v('label') ?></a>
          <?php endforeach; ?>
        </div>
        <?php if ($showServices): ?>
        <div>
          <button type="button" id="mobile-services-toggle" aria-expanded="false" aria-controls="mobile-services"
                  class="w-full flex items-center justify-between text-base font-medium px-4 py-3 rounded-xl transition-all <?= is_active_path('/services/') ? 'bg-sky-50 text-sky-600' : 'text-gray-700 hover:bg-gray-50' ?>">
            <?= $nav->t('services_mobile_label', 'Services label (mobile)') ?>
            <?= chevron_down('w-5 h-5 transition-transform duration-300') ?>
          </button>
          <div id="mobile-services" class="overflow-hidden transition-all duration-300 max-h-0">
            <div class="pl-4 py-1 flex flex-col gap-0.5">
              <?php foreach ($menuServices as $s): ?>
              <a href="<?= e(url('/services/' . $s->str('slug') . '/')) ?>" class="text-sm text-gray-600 px-4 py-2.5 rounded-lg hover:bg-sky-50 hover:text-sky-600 transition-colors"><?= $s->v('menu_title') ?></a>
              <?php endforeach; ?>
              <a href="<?= e(url('/services/')) ?>" class="text-sm font-semibold text-sky-600 px-4 py-2.5 rounded-lg hover:bg-sky-50 transition-colors"><?= $nav->v('services_all_label') ?> &rarr;</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="flex flex-col gap-1">
          <?php foreach ($site->each('nav.after') as $l): ?>
          <a href="<?= $l->url('href') ?>" class="text-base font-medium px-4 py-3 rounded-xl transition-all <?= $mLinkCls($l->str('href')) ?>"<?= $current($l->str('href')) ?>><?= $l->v('label') ?></a>
          <?php endforeach; ?>
        </div>
        <a href="<?= $c->url('phone_href') ?>" class="flex items-center gap-3 px-4 py-3 mt-2 text-gray-700 font-medium">
          <span class="w-9 h-9 rounded-full bg-sky-50 flex items-center justify-center" aria-hidden="true"><?= phone_solid_icon('w-4 h-4 text-sky-600') ?></span>
          <?= $c->v('phone_display') ?>
        </a>
        <a href="<?= $nav->url('cta_href', '/contact/') ?>" class="mt-2 mx-4 px-6 py-4 bg-gray-900 text-white text-center font-bold rounded-xl shadow-lg active:scale-95 transition-all"><?= $nav->t('mobile_cta', 'Mobile button text') ?></a>
      </nav>
    </div>
  </div>
</header>
