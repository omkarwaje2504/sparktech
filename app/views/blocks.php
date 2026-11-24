<?php
/**
 * Sections ("blocks") an admin adds with "+ Add a section". Every page has a
 * blocks slot; custom pages are built from blocks entirely.
 * Templates for new sections: app/defaults/blocks.php
 */

const ST_BLOCK_BG = [
    'white' => ['bg-white', 'light'],
    'gray' => ['bg-gray-50', 'light'],
    'dark' => ['bg-gray-950 text-white', 'dark'],
    'brand' => ['bg-linear-to-r from-[#6EC0EF]/20 to-[#B0D10E]/20', 'light'],
];

function block_bg_options(): array
{
    return [['white', 'White'], ['gray', 'Light grey'], ['dark', 'Dark'], ['brand', 'Blue → green tint']];
}

function block_cols_class(string $cols): string
{
    switch ($cols) {
        case '2':
            return 'grid-cols-1 sm:grid-cols-2';
        case '4':
            return 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
        default:
            return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
    }
}

/** All sections in $path (+ the "Add a section" bar in edit mode). */
function render_blocks(Doc $d, string $path = 'blocks'): string
{
    $html = '';
    foreach ($d->each($path) as $i => $b) {
        if (!edit_mode() && !$b->bool('visible', true)) {
            continue;
        }
        $html .= render_block($d, $path, $i, $b);
    }
    $out = '<div' . $d->lst($path, 'section') . '>' . $html . '</div>';
    if (edit_mode()) {
        $out .= '<div class="cms-add-block"><button type="button" data-cms-add-block="'
            . e($d->name . '|' . $d->p($path)) . '">+ Add a section here</button></div>';
    }
    return $out;
}

function render_block(Doc $d, string $path, int $i, Doc $b): string
{
    $type = $b->str('type', 'text');
    [$bgClass, $tone] = ST_BLOCK_BG[$b->str('bg', 'white')] ?? ST_BLOCK_BG['white'];
    $uid = preg_replace('/[^a-z0-9]/i', '', $b->str('uid')) ?: 'b' . $i;
    $anchor = slugify($b->str('anchor'));

    $common = [
        'visible' => ['bool', 'Show this section'],
        'anchor' => ['text', 'Anchor name (link to it with #name)'],
    ];
    $settings = $common + ['bg' => ['select', 'Background', block_bg_options(), 'white']];
    $labels = ['text' => 'Text section', 'image_text' => 'Image + text section', 'gallery' => 'Gallery section',
        'cards' => 'Cards section', 'cta' => 'Call-to-action section', 'html' => 'Custom HTML section'];
    switch ($type) {
        case 'text':
            $settings['align'] = ['select', 'Alignment', [['center', 'Centered'], ['left', 'Left']], 'center'];
            break;
        case 'image_text':
            $settings['image'] = ['image', 'Image'];
            $settings['image_side'] = ['select', 'Image position', [['left', 'Image on the left'], ['right', 'Image on the right']], 'left'];
            break;
        case 'gallery':
        case 'cards':
            $settings['columns'] = ['select', 'Columns', [['2', '2 per row'], ['3', '3 per row'], ['4', '4 per row']], '3'];
            break;
        case 'html':
            $settings = ['html' => ['html', 'HTML'], 'css' => ['css', 'CSS (only affects this section)']]
                + $settings + ['contained' => ['bool', 'Keep inside the page width (with padding)']];
            break;
        case 'cta':
            $settings = $common;
            break;
    }
    $attrs = $d->item($i, "$path.$i.visible") . $b->edit($settings, $labels[$type] ?? 'Section')
        . ' data-blk="' . e($uid) . '"' . ($anchor !== '' ? ' id="' . e($anchor) . '"' : '');

    ob_start();
    switch ($type) {
        case 'image_text':
            $right = $b->str('image_side') === 'right'; ?>
<section class="relative overflow-hidden py-10 <?= $bgClass ?>"<?= $attrs ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
      <div data-reveal="<?= $right ? 'right' : 'left' ?>" class="<?= $right ? 'lg:order-2' : '' ?>">
        <div class="relative overflow-hidden rounded-[2rem] border border-gray-100 shadow-[0_30px_80px_rgba(15,23,42,0.12)]">
          <?= $b->img('image', 'h-72 w-full object-cover md:h-[24rem]', $b->str('title')) ?>
        </div>
      </div>
      <div data-reveal="<?= $right ? 'left' : 'right' ?>">
        <?= section_heading($b, $tone, 'left') ?>
        <div class="st-prose <?= $tone === 'dark' ? 'st-prose-dark text-gray-300' : 'text-gray-600' ?> mt-5 text-base leading-relaxed"><?= $b->rich('body', 'Text', 'div') ?></div>
        <?php if ($b->has('button.label') || edit_mode()): ?>
        <div class="mt-8"><?= cta_button($b, 'button', 'primary') ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php
            break;

        case 'gallery':
            $cols = block_cols_class($b->str('columns', '3')); ?>
<section class="relative overflow-hidden py-10 <?= $bgClass ?>"<?= $attrs ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-10"><?= section_heading($b, $tone) ?></div>
    <div class="grid gap-3 <?= $cols ?>" data-reveal-group data-lightbox-group<?= $b->lst('items', 'photo', 'image') ?>>
      <?php foreach ($b->each('items') as $j => $it): ?>
      <figure data-reveal class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md"<?= $b->item($j) ?>>
        <button type="button" class="block w-full cursor-zoom-in" data-lightbox-open aria-label="View larger photo">
          <span class="block aspect-[4/3] overflow-hidden"><?= $it->img('image', 'h-full w-full object-cover transition-transform duration-500 group-hover:scale-105', $it->str('caption'), [], 'Photo') ?></span>
        </button>
        <?php if (show_field($it, 'caption')): ?>
        <figcaption class="px-4 py-3 text-sm font-semibold text-gray-800" data-lightbox-caption><?= $it->t('caption', 'Caption') ?></figcaption>
        <?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
            break;

        case 'cards':
            $cols = block_cols_class($b->str('columns', '3')); ?>
<section class="relative overflow-hidden py-10 <?= $bgClass ?>"<?= $attrs ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal class="mb-10"><?= section_heading($b, $tone) ?></div>
    <div class="grid gap-4 <?= $cols ?>" data-reveal-group<?= $b->lst('items', 'card', 'image') ?>>
      <?php foreach ($b->each('items') as $j => $it): ?>
      <div data-reveal class="group overflow-hidden rounded-3xl border border-gray-200 bg-white text-left shadow-md transition-all duration-500 hover:-translate-y-1 hover:shadow-xl"<?= $b->item($j) ?>>
        <div class="relative h-48 overflow-hidden">
          <?= $it->img('image', 'absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110', $it->str('title')) ?>
        </div>
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900"><?= $it->t('title', 'Card title') ?></h3>
          <p class="mt-2 text-gray-600"><?= $it->ta('text', 'Card text') ?></p>
          <?php if ($it->has('href') || edit_mode()): ?>
          <a href="<?= $it->url('href') ?>"<?= $b->edit(["items.$j.href" => ['url', 'Card link (leave empty for no link)'], 'link_label' => ['text', 'Link text (all cards)']], 'Card link') ?>
             class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-sky-600 transition hover:text-sky-500"><?= $b->v('link_label') ?><?= arrow_icon('h-4 w-4') ?></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
            break;

        case 'cta':
            echo cta_band($b, '', false, $attrs);
            break;

        case 'html':
            $contained = $b->bool('contained', true); ?>
<section class="relative <?= $bgClass ?> <?= $contained ? 'py-10' : '' ?>"<?= $attrs ?>>
  <?= $b->css('css', '[data-blk="' . $uid . '"]') ?>
  <?php if ($contained): ?><div class="container mx-auto px-4 sm:px-6 lg:px-8"><?php endif; ?>
  <?= $b->html('html') ?>
  <?php if ($contained): ?></div><?php endif; ?>
</section>
<?php
            break;

        default: // text
            $align = $b->str('align', 'center') === 'left' ? 'left' : 'center'; ?>
<section class="relative overflow-hidden py-10 <?= $bgClass ?>"<?= $attrs ?>>
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div data-reveal>
      <?= section_heading($b, $tone, $align) ?>
      <div class="st-prose <?= $align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl' ?> <?= $tone === 'dark' ? 'st-prose-dark text-gray-300' : 'text-gray-700' ?> mt-6 text-base leading-relaxed"><?= $b->rich('body', 'Text', 'div') ?></div>
    </div>
  </div>
</section>
<?php
    }
    return (string)ob_get_clean();
}

/** Site-wide popups (Admin → Popups). Links to "#popup-{id}" open them. */
function render_popups(Doc $site): string
{
    $html = '';
    foreach ($site->each('popups') as $i => $p) {
        $id = slugify($p->str('id'));
        if ($id === '') {
            continue;
        }
        $width = in_array($p->str('width'), ['sm', 'md', 'lg', 'xl'], true) ? $p->str('width') : 'md';
        $html .= '<div class="st-popup" id="popup-' . e($id) . '" data-popup="' . e($id) . '" hidden role="dialog" aria-modal="true" aria-label="' . $p->e('title') . '">'
            . '<div class="st-popup-backdrop" data-popup-close></div>'
            . '<div class="st-popup-panel st-popup-' . $width . '"' . $p->edit([
                'title' => ['text', 'Popup name'],
                'html' => ['html', 'HTML'],
                'css' => ['css', 'CSS (only affects this popup)'],
                'width' => ['select', 'Width', [['sm', 'Small'], ['md', 'Medium'], ['lg', 'Large'], ['xl', 'Extra large']], 'md'],
            ], 'Popup: ' . $p->str('title')) . '>'
            . '<button type="button" class="st-popup-x" data-popup-close aria-label="Close">&times;</button>'
            . $p->css('css', '#popup-' . $id . ' .st-popup-body')
            . '<div class="st-popup-body">' . $p->html('html') . '</div>'
            . '</div></div>';
    }
    return $html;
}
