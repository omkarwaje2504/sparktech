<?php
/**
 * Template-side view of a content doc.
 *
 * In public mode every helper prints plain escaped content. In edit mode the
 * same helpers add data-cms-* attributes that the live editor (assets/js/
 * editor.js) uses to draw hover outlines, edit buttons and the side panel.
 *
 *   $d->t('hero.title')            editable inline text
 *   $d->ta('hero.subtitle')        editable multi-line text
 *   $d->rich('body')               editable rich text (bold/italic/links)
 *   $d->v('hero.title')            bound text (updates live, not editable on its own)
 *   $d->img('hero.image', 'class') editable <img>
 *   $d->edit([...fields], 'Name')  makes the current element a multi-field editor
 *   $d->lst('items') / item($i)    list container / list item (move, duplicate, delete)
 *   $d->section('key', 'Name')     section toolbar (hide / show)
 */
final class Doc
{
    /** @var string */
    public $name;
    /** @var string */
    public $prefix;

    public function __construct(string $name, string $prefix = '')
    {
        $this->name = $name;
        $this->prefix = $prefix;
    }

    public function p(string $path = ''): string
    {
        if ($this->prefix === '') {
            return $path;
        }
        return $path === '' ? $this->prefix : $this->prefix . '.' . $path;
    }

    /** Scoped view: $d->at('hero')->t('title') edits "hero.title". */
    public function at(string $path): Doc
    {
        return new Doc($this->name, $this->p($path));
    }

    public function get(string $path = '', $default = null)
    {
        return Content::getPath(Content::data($this->name), $this->p($path), $default);
    }

    public function has(string $path): bool
    {
        $v = $this->get($path);
        if (is_array($v)) {
            return $v !== [];
        }
        return $v !== null && trim((string)$v) !== '';
    }

    public function str(string $path, string $default = ''): string
    {
        $v = $this->get($path, $default);
        if (is_array($v)) {
            return isset($v['src']) ? (string)$v['src'] : $default;
        }
        return $v === null ? $default : (string)$v;
    }

    public function e(string $path, string $default = ''): string
    {
        return e($this->str($path, $default));
    }

    public function bool(string $path, bool $default = false): bool
    {
        $v = $this->get($path);
        return $v === null ? $default : (bool)$v;
    }

    public function num(string $path, $default = 0)
    {
        $v = $this->get($path, $default);
        return is_numeric($v) ? $v + 0 : $default;
    }

    /** @return array<int, Doc> scoped items of a list */
    public function each(string $path): array
    {
        $list = $this->get($path, []);
        $out = [];
        if (is_array($list)) {
            foreach (array_keys($list) as $i) {
                $out[$i] = $this->at($path . '.' . $i);
            }
        }
        return $out;
    }

    /** @return string[] */
    public function lines(string $path): array
    {
        $v = $this->get($path, []);
        if (is_string($v)) {
            $v = preg_split('/\r\n|\r|\n/', $v);
        }
        return array_values(array_filter(array_map('strval', (array)$v), function ($l) {
            return trim($l) !== '';
        }));
    }

    public function count(string $path): int
    {
        $v = $this->get($path, []);
        return is_array($v) ? count($v) : 0;
    }

    /** Resolved + escaped URL for href/src attributes. */
    public function url(string $path, string $default = '#'): string
    {
        $v = $this->str($path);
        return e($v === '' ? $default : url($v));
    }

    // ---- Output helpers ---------------------------------------------------------

    /** Editable inline text. */
    public function t(string $path, ?string $label = null, string $type = 'text'): string
    {
        $value = $this->str($path);
        $html = $type === 'textarea' ? nl2br(e($value)) : e($value);
        if (!edit_mode()) {
            return $html;
        }
        $full = $this->p($path);
        return '<span class="cms-t" data-cms-edit="' . e(cms_spec($this->name, [cms_field($full, $type, $label)])) . '"'
            . ' data-cms-b="' . e($this->name . '|' . $full . '|' . ($type === 'textarea' ? 'ml' : 'text')) . '">'
            . $html . '</span>';
    }

    /** Editable multi-line text. */
    public function ta(string $path, ?string $label = null): string
    {
        return $this->t($path, $label, 'textarea');
    }

    /** Bound text: updates live from a parent editor but has no edit button. */
    public function v(string $path, string $mode = 'text'): string
    {
        $value = $this->str($path);
        $html = $mode === 'ml' ? nl2br(e($value)) : e($value);
        if (!edit_mode()) {
            return $html;
        }
        return '<span data-cms-b="' . e($this->name . '|' . $this->p($path) . '|' . $mode) . '">' . $html . '</span>';
    }

    /** Editable rich text (sanitized HTML). */
    public function rich(string $path, ?string $label = null, string $tag = 'span', string $class = ''): string
    {
        $html = clean_rich($this->str($path));
        $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
        if (!edit_mode()) {
            return $tag === 'span' && $class === '' ? $html : "<$tag$cls>$html</$tag>";
        }
        $full = $this->p($path);
        $cls = ' class="cms-t' . ($class !== '' ? ' ' . e($class) : '') . '"';
        return "<$tag$cls" . ' data-cms-edit="' . e(cms_spec($this->name, [cms_field($full, 'rich', $label)])) . '"'
            . ' data-cms-b="' . e($this->name . '|' . $full . '|html') . '">' . $html . "</$tag>";
    }

    /** Normalised image value: ['src' => ..., 'alt' => ...] */
    public function image(string $path, string $altFallback = ''): array
    {
        $v = $this->get($path);
        if (is_array($v)) {
            $src = (string)($v['src'] ?? '');
            $alt = (string)($v['alt'] ?? '');
        } else {
            $src = (string)$v;
            $alt = '';
        }
        return ['src' => $src, 'alt' => $alt !== '' ? $alt : $altFallback];
    }

    /**
     * Editable <img>. $attrs: extra attributes (string values), e.g.
     * ['loading' => 'eager', 'width' => 900].
     */
    public function img(string $path, string $class = '', string $altFallback = '', array $attrs = [], ?string $label = null): string
    {
        $img = $this->image($path, $altFallback);
        // An image spot may hold a video instead: it plays silently on a loop.
        $video = is_video_src($img['src']);
        $a = ['src' => url($img['src'])];
        if ($video) {
            $a += ['autoplay' => true, 'muted' => true, 'loop' => true, 'playsinline' => true, 'preload' => 'metadata', 'aria-label' => $img['alt']];
            unset($attrs['loading'], $attrs['decoding'], $attrs['fetchpriority'], $attrs['width'], $attrs['height']);
        } else {
            $a['alt'] = $img['alt'];
        }
        if ($class !== '') {
            $a['class'] = $class;
        }
        if (!$video && !isset($attrs['loading'])) {
            $a['loading'] = 'lazy';
        }
        if (!$video) {
            $a['decoding'] = 'async';
        }
        $a = array_merge($a, $attrs);
        $style = media_style($this->get($path));
        if ($style !== '') {
            $own = rtrim((string)($a['style'] ?? ''), '; ');
            $a['style'] = ($own !== '' ? $own . ';' : '') . $style;
        }
        $html = $video ? '<video' : '<img';
        foreach ($a as $k => $val) {
            if ($val === null || $val === false) {
                continue;
            }
            $html .= $val === true ? ' ' . $k : ' ' . $k . '="' . e($val) . '"';
        }
        if (edit_mode()) {
            $full = $this->p($path);
            $html .= ' data-cms-edit="' . e(cms_spec($this->name, [cms_field($full, 'image', $label ?? 'Image')], 'Image')) . '"';
            $html .= ' data-cms-b="' . e($this->name . '|' . $full . '|img') . '"';
        }
        return $html . ($video ? '></video>' : '>');
    }

    /** style="…" with the stored fit / alignment of an image or video ('' when none). */
    public function mediaStyle(string $path): string
    {
        $style = media_style($this->get($path));
        return $style !== '' ? ' style="' . e($style) . '"' : '';
    }

    /** Live-preview binding for a non-<img> element (e.g. a background <video>). */
    public function bind(string $path, string $mode): string
    {
        return edit_mode() ? ' data-cms-b="' . e($this->name . '|' . $this->p($path) . '|' . $mode) . '"' : '';
    }

    /** Attribute string that turns the element into a multi-field editor. */
    public function edit(array $fields, string $label = ''): string
    {
        if (!edit_mode()) {
            return '';
        }
        $list = [];
        foreach ($fields as $path => $def) {
            if (is_string($def)) {
                $def = [$def];
            }
            $list[] = cms_field($this->p($path), $def[0] ?? 'text', $def[1] ?? null, $def[2] ?? null, $def[3] ?? null);
        }
        return ' data-cms-edit="' . e(cms_spec($this->name, $list, $label)) . '"';
    }

    /**
     * List container: enables add / reorder / duplicate / delete on items.
     * $photoField: the image key inside each item — enables "+ Photos" (bulk upload,
     * one new item per photo).
     * $groupField: a text key that groups the items (e.g. a gallery category) — the
     * "Add photos" bar then offers one button per group, plus a new group.
     * $youtubeField: a text key that holds a YouTube link — enables "+ YouTube video"
     * (one new item per pasted link).
     */
    public function lst(string $path, string $label = 'item', string $photoField = '', string $groupField = '', string $youtubeField = ''): string
    {
        if (!edit_mode()) {
            return '';
        }
        return ' data-cms-list="' . e($this->name . '|' . $this->p($path)) . '" data-cms-list-label="' . e($label) . '"'
            . ($photoField !== '' ? ' data-cms-list-photo="' . e($photoField) . '"' : '')
            . ($groupField !== '' ? ' data-cms-list-group="' . e($groupField) . '"' : '')
            . ($youtubeField !== '' ? ' data-cms-list-youtube="' . e($youtubeField) . '"' : '');
    }

    /** Admin-written HTML (popups / custom sections), cleaned of scripts. Live-bound in edit mode. */
    public function html(string $path): string
    {
        $html = clean_html_trusted($this->str($path));
        if (!edit_mode()) {
            return $html;
        }
        return '<div data-cms-b="' . e($this->name . '|' . $this->p($path) . '|rawhtml') . '">' . $html . '</div>';
    }

    /** Admin-written CSS, scoped to $scope (a CSS selector) with native CSS nesting. */
    public function css(string $path, string $scope): string
    {
        $css = clean_css($this->str($path));
        if ($css === '' && !edit_mode()) {
            return '';
        }
        $b = edit_mode() ? ' data-cms-b="' . e($this->name . '|' . $this->p($path) . '|css') . '" data-cms-scope="' . e($scope) . '"' : '';
        return '<style' . $b . '>' . scope_css($css, $scope) . '</style>';
    }

    /** List item marker (index within the closest data-cms-list). */
    public function item(int $i, string $hiddenPath = ''): string
    {
        if (!edit_mode()) {
            return '';
        }
        $attr = ' data-cms-item="' . $i . '"';
        if ($hiddenPath !== '' && !$this->bool($hiddenPath, true)) {
            $attr .= ' data-cms-off="1"';
        }
        return $attr;
    }

    /** Section toolbar marker (show / hide whole section). */
    public function section(string $key, string $label): string
    {
        if (!edit_mode()) {
            return '';
        }
        $hidden = (bool)Content::getPath(Content::data($this->name), '_hidden.' . $key, false);
        return ' data-cms-section="' . e($this->name . '|' . $key . '|' . $label) . '"' . ($hidden ? ' data-cms-off="1"' : '');
    }

    /** Should a section render? (Hidden sections still render, dimmed, in edit mode.) */
    public function shown(string $key): bool
    {
        if (edit_mode()) {
            return true;
        }
        return !Content::getPath(Content::data($this->name), '_hidden.' . $key, false);
    }
}

/**
 * Build one editor field definition. $default: value a select shows when nothing is
 * stored yet (should match the fallback the template renders).
 */
function cms_field(string $path, string $type = 'text', ?string $label = null, $options = null, $default = null): array
{
    if ($label === null) {
        $segs = explode('.', $path);
        $last = end($segs);
        $label = ctype_digit((string)$last) ? 'Item ' . ((int)$last + 1) : humanize((string)$last);
    }
    $f = ['p' => $path, 't' => $type, 'l' => $label];
    if ($options !== null) {
        $f['o'] = $options;
    }
    if ($default !== null) {
        $f['dv'] = $default;
    }
    return $f;
}

/**
 * Wrap admin CSS in "$scope { … }" (native CSS nesting) so it only styles its own
 * section/popup. @keyframes, @font-face and @import stay global (they can't be nested).
 * Mirrored in assets/js/editor.js (scopeCss) for live preview.
 */
function scope_css(string $css, string $scope): string
{
    $global = '';
    $scoped = '';
    $len = strlen($css);
    $i = 0;
    while ($i < $len) {
        $start = $i;
        $depth = 0;
        while ($i < $len) {
            $ch = $css[$i];
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth <= 0) {
                    $i++;
                    break;
                }
            } elseif ($ch === ';' && $depth === 0) {
                $i++;
                break;
            }
            $i++;
        }
        $stmt = substr($css, $start, $i - $start);
        if (preg_match('/^\s*@(-webkit-)?(keyframes|font-face|import|property|charset)\b/i', $stmt)) {
            $global .= trim($stmt) . "\n";
        } else {
            $scoped .= $stmt;
        }
    }
    return $global . ($scoped !== '' && trim($scoped) !== '' ? $scope . '{' . $scoped . '}' : '');
}

function cms_spec(string $doc, array $fields, string $label = ''): string
{
    $spec = ['d' => $doc, 'f' => $fields];
    if ($label !== '') {
        $spec['n'] = $label;
    }
    return json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
