<?php
/** Site footer (ported from src/components/Footer.tsx). @var Doc $site */
$c = $site->at('contact');
$f = $site->at('footer');
?>
<footer class="relative overflow-hidden bg-gray-950 text-white">
  <div class="pointer-events-none absolute inset-0" aria-hidden="true">
    <div class="absolute -top-24 left-1/4 h-96 w-96 rounded-full bg-sky-500/20 blur-[120px]"></div>
    <div class="absolute top-1/3 right-0 h-80 w-80 rounded-full bg-lime-400/15 blur-[120px]"></div>
    <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-indigo-500/10 blur-[120px]"></div>
  </div>
  <div class="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-white/15 to-transparent" aria-hidden="true"></div>

  <div class="relative mx-auto container px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-10 border-b border-white/10 lg:flex-row lg:items-center lg:justify-between py-6">
      <div class="max-w-2xl">
        <a href="<?= e(url('/')) ?>" rel="home">
          <?= $site->img('brand.footer_logo', 'w-auto transition-all duration-500 group-hover:scale-105 -ml-5', 'SPARKTECH Processes LLP', ['width' => '1000', 'height' => '1000'], 'Footer logo') ?>
        </a>
      </div>
      <a href="<?= $f->url('cta.href', '/contact/') ?>"<?= link_edit($f, 'cta', 'Footer button') ?>
         class="group inline-flex shrink-0 items-center gap-4 rounded-full bg-white px-5 py-4 text-sm font-bold uppercase text-gray-950 transition-all duration-300 hover:bg-sky-400 hover:text-white">
        <?= $f->v('cta.label') ?>
        <?= arrow_icon('h-5 w-5 transition-transform duration-300 group-hover:translate-x-1', 2.5) ?>
      </a>
    </div>

    <div class="grid gap-12 pt-6 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr] lg:gap-16"<?= $site->lst('footer.columns', 'footer column') ?>>
      <div class="space-y-8">
        <address class="st-address space-y-3 text-sm font-light text-gray-400">
          <p class="leading-relaxed"<?= $site->edit(['contact.address_title' => ['text', 'Address first line'], 'contact.address_lines' => ['lines', 'Address lines (one per line)']], 'Address') ?>>
            <?= $c->e('address_title') ?>, <?= e(implode(' ', $c->lines('address_lines'))) ?>
          </p>
          <a href="<?= $c->url('phone_href') ?>" class="block transition-colors hover:text-white"<?= $site->edit(['contact.phone_display' => ['text', 'Phone (shown)'], 'contact.phone_href' => ['url', 'Phone link (tel:+91...)']], 'Phone') ?>><?= $c->v('phone_display') ?></a>
          <a href="<?= $c->url('email_href') ?>" class="block transition-colors hover:text-white"<?= $site->edit(['contact.email_display' => ['text', 'E-mail (shown)'], 'contact.email_href' => ['url', 'E-mail link (mailto:...)']], 'E-mail') ?>><?= $c->v('email_display') ?></a>
        </address>
        <div class="flex items-center gap-3"<?= $site->lst('footer.social', 'social link') ?>>
          <?php foreach ($f->each('social') as $i => $s): ?>
          <a href="<?= $s->url('href') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $s->e('name') ?>"<?= $site->item($i) ?><?= $s->edit(['name' => 'text', 'href' => 'url', 'icon' => ['select', 'Icon', array_keys(ST_SOCIAL)]], 'Social link') ?>
             class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 transition-all duration-300 hover:border-sky-400 hover:bg-sky-500">
            <?= social_icon($s->str('icon'), 'h-4 w-4') ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php foreach ($f->each('columns') as $ci => $col): ?>
      <nav aria-label="<?= $col->e('heading') ?>"<?= $site->item($ci) ?><?= $col->edit(['heading' => 'text', 'href' => ['url', 'Default link for this column'], 'items' => ['lines', 'Links — one per line. Give a line its own link like:  Careers | /careers/']], 'Footer column') ?>>
        <h2 class="mb-2 md:mb-6 text-sm font-bold uppercase text-lime-300"><?= $col->v('heading') ?></h2>
        <ul class="space-y-1 md:space-y-2">
          <?php foreach ($col->lines('items') as $item):
              $parts = array_map('trim', explode('|', $item, 2));
              $href = isset($parts[1]) && $parts[1] !== '' ? url(clean_url($parts[1]) ?: '#') : url($col->str('href')); ?>
          <li>
            <a href="<?= e($href) ?>" class="group inline-flex items-center gap-2 text-sm font-light text-gray-400 transition-colors duration-300 hover:text-white">
              <span class="h-px w-0 bg-sky-400 transition-all duration-300 group-hover:w-4" aria-hidden="true"></span>
              <?= e($parts[0]) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endforeach; ?>
    </div>

    <div class="relative select-none overflow-hidden mt-4 md:mt-0" aria-hidden="true">
      <p class="bg-linear-to-b from-white/10 to-white/0 bg-clip-text text-center text-[15vw] font-black leading-none text-transparent lg:text-[13vw] font-display"><?= $f->t('wordmark', 'Big wordmark') ?></p>
    </div>
  </div>

  <div class="relative border-t border-white/10">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-4 md:flex-row lg:px-8">
      <p class="text-xs font-light text-gray-500">&copy; <?= date('Y') ?> <?= $f->t('copyright', 'Copyright text') ?></p>
      <nav class="flex items-center gap-8" aria-label="Legal"<?= $site->lst('footer.bottom_links', 'link') ?>>
        <?php foreach ($f->each('bottom_links') as $i => $l): ?>
        <a href="<?= $l->url('href') ?>" class="text-xs font-light text-gray-500 transition-colors hover:text-white"<?= $site->item($i) ?><?= link_edit($f, "bottom_links.$i", 'Footer link') ?>><?= $l->v('label') ?></a>
        <?php endforeach; ?>
        <span class="text-xs font-light text-gray-500"><?= $f->t('bottom_text', 'Footer text') ?></span>
      </nav>
    </div>
  </div>
</footer>
