<?php
/**
 * Input sanitizers for content saved through the live editor.
 */

/** Allowed framing for images / videos (object-fit, object-position). */
const MEDIA_FITS = ['cover', 'contain', 'fill', 'none'];
const MEDIA_POSITIONS = ['center', 'top', 'bottom', 'left', 'right', 'left top', 'right top', 'left bottom', 'right bottom'];
/** Service page section images: where the image sits and its preset size (capped in site.css). */
const SERVICE_IMG_POSITIONS = ['top', 'left', 'right'];
const SERVICE_IMG_SIZES = ['sm', 'md', 'lg', 'xl'];

/** Inline style for a stored image / video value ('' when it has no framing set). */
function media_style($value): string
{
    if (!is_array($value)) {
        return '';
    }
    $css = '';
    if (in_array($value['fit'] ?? '', MEDIA_FITS, true)) {
        $css .= 'object-fit:' . $value['fit'] . ';';
    }
    if (in_array($value['pos'] ?? '', MEDIA_POSITIONS, true)) {
        $css .= 'object-position:' . $value['pos'] . ';';
    }
    return $css;
}

/** True when a media address points at a video file. */
function is_video_src(string $src): bool
{
    return (bool)preg_match('/\.(mp4|webm)(\?|#|$)/i', $src);
}

/**
 * The 11-character video id of a YouTube link ('' when it isn't one). Accepts
 * watch?v=, youtu.be/, shorts/, embed/, live/ links and a bare id.
 * Mirrored in assets/js/editor.js (youtubeId).
 */
function youtube_id(string $url): string
{
    $url = trim($url);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }
    $re = '~^(?:https?://)?(?:www\.|m\.|music\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be)/'
        . '(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)?([A-Za-z0-9_-]{11})(?:[?&#/]|$)~i';
    return preg_match($re, $url, $m) ? $m[1] : '';
}

/** Allow only safe URL schemes. Returns '' for anything dangerous. */
function clean_url($value): string
{
    $url = trim((string)$value);
    if ($url === '') {
        return '';
    }
    // Strip control characters / whitespace that browsers ignore inside schemes.
    $probe = strtolower(preg_replace('/[\x00-\x20]+/', '', $url));
    if (preg_match('/^(javascript|vbscript|data|file):/', $probe)) {
        return '';
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/', $probe) && !preg_match('/^(https?|mailto|tel|sms|whatsapp):/', $probe)) {
        return '';
    }
    return mb_substr($url, 0, 2000);
}

/** Plain text (no HTML). Keeps newlines for textarea fields. */
function clean_text($value, int $max = 20000): string
{
    $text = (string)$value;
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    return mb_substr((string)$text, 0, $max);
}

/**
 * Rich text: a tiny allow-list (bold/italic/underline/links/line breaks/lists).
 * Everything else is unwrapped to text; attributes other than href are dropped.
 */
function clean_rich($html): string
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }
    $allowed = ['strong', 'b', 'em', 'i', 'u', 'a', 'br', 'p', 'ul', 'ol', 'li', 'span'];

    if (!class_exists('DOMDocument')) {
        $html = strip_tags($html, '<' . implode('><', $allowed) . '>');
        // Remove all attributes except href, then vet the href.
        $html = preg_replace('/<(\w+)[^>]*?(\shref\s*=\s*"[^"]*")?[^>]*>/i', '<$1$2>', $html);
        return preg_replace_callback('/href\s*=\s*"([^"]*)"/i', function ($m) {
            return 'href="' . e(clean_url(html_entity_decode($m[1]))) . '"';
        }, (string)$html);
    }

    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) {
        return e(strip_tags($html));
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMElement) {
                $walk($child);
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                if (!in_array($tag, $allowed, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $href = $tag === 'a' ? clean_url($child->getAttribute('href')) : '';
                $newTab = $tag === 'a' && $child->getAttribute('target') === '_blank';
                while ($child->attributes->length) {
                    $child->removeAttribute($child->attributes->item(0)->nodeName);
                }
                if ($tag === 'a') {
                    $child->setAttribute('href', $href !== '' ? $href : '#');
                    if ($newTab) {
                        $child->setAttribute('target', '_blank');
                        $child->setAttribute('rel', 'noopener noreferrer');
                    }
                }
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);

    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return trim($out);
}

/**
 * Custom HTML written by an admin (popups, "Custom HTML" sections).
 * Keeps the design (tags, classes, inline styles, images, iframes for maps/videos)
 * but removes anything that runs code: <script>, on* event handlers,
 * javascript:/data: links, <object>/<embed>, and non-https iframes.
 */
function clean_html_trusted($html): string
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }
    if (!class_exists('DOMDocument')) {
        $html = preg_replace('#<(script|object|embed|base|meta|link|applet)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|object|embed|base|meta|link|applet)\b[^>]*/?>#i', '', (string)$html);
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', (string)$html);
        return preg_replace('#(href|src|action|formaction)\s*=\s*(["\']?)\s*(javascript|vbscript|data):[^"\'\s>]*\2#i', '$1="#"', (string)$html);
    }
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) {
        return '';
    }
    $blocked = ['script', 'object', 'embed', 'base', 'meta', 'link', 'applet', 'frame', 'frameset', 'noscript'];
    $walk = function (DOMNode $node) use (&$walk, $blocked) {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, $blocked, true)) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'iframe' && !preg_match('#^https://#i', $child->getAttribute('src'))) {
                $node->removeChild($child);
                continue;
            }
            for ($a = $child->attributes->length - 1; $a >= 0; $a--) {
                $attr = $child->attributes->item($a);
                $name = strtolower($attr->nodeName);
                if (str_starts_with($name, 'on') || $name === 'srcdoc') {
                    $child->removeAttribute($attr->nodeName);
                } elseif (in_array($name, ['href', 'src', 'action', 'formaction', 'poster', 'data', 'xlink:href'], true)) {
                    $url = clean_url($attr->nodeValue);
                    $child->setAttribute($attr->nodeName, $url === '' ? '#' : $url);
                } elseif ($name === 'style' && preg_match('/expression\s*\(|javascript:|behaviou?r\s*:/i', $attr->nodeValue)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            $walk($child);
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return trim($out);
}

/** CSS written by an admin: can't break out of its <style> tag or load scripts. */
function clean_css($css): string
{
    $css = str_replace(["\r\n", "\r"], "\n", (string)$css);
    $css = preg_replace('#</?\s*style#i', '', $css);
    $css = preg_replace('/expression\s*\(|javascript:|behaviou?r\s*:|-moz-binding/i', '', (string)$css);
    return mb_substr(trim((string)$css), 0, 50000);
}

/**
 * Clean a value coming from the editor according to its field type.
 * Arrays (lists / image objects) are cleaned recursively.
 */
function clean_value($value, string $type)
{
    switch ($type) {
        case 'rich':
            return clean_rich($value);
        case 'html':
            return clean_html_trusted($value);
        case 'css':
            return clean_css($value);
        case 'url':
            return clean_url($value);
        case 'number':
            return is_numeric($value) ? $value + 0 : 0;
        case 'bool':
            return (bool)$value && $value !== 'false' && $value !== '0';
        case 'lines':
            $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value);
            $out = [];
            foreach ($lines as $l) {
                $l = trim(clean_text($l, 2000));
                if ($l !== '') {
                    $out[] = $l;
                }
            }
            return $out;
        case 'image':
        case 'video':
            if (is_array($value)) {
                $out = [
                    'src' => clean_url($value['src'] ?? ''),
                    'alt' => trim(clean_text($value['alt'] ?? '', 300)),
                ];
                // Optional framing: object-fit + object-position (see media_style()).
                $fit = (string)($value['fit'] ?? '');
                $pos = (string)($value['pos'] ?? '');
                if (in_array($fit, MEDIA_FITS, true)) {
                    $out['fit'] = $fit;
                }
                if (in_array($pos, MEDIA_POSITIONS, true)) {
                    $out['pos'] = $pos;
                }
                return $out;
            }
            return clean_url($value);
        case 'color':
            return preg_match('/^#[0-9a-fA-F]{3,8}$/', (string)$value) ? (string)$value : '';
        default: // text, textarea, select, icon
            return clean_text($value);
    }
}

/**
 * Clean an arbitrary JSON structure from an untyped source (revisions import,
 * list duplication). Strings are text-cleaned; nothing executable survives
 * because all output is escaped at render time except rich fields, which
 * are re-sanitized on output too.
 */
function clean_tree($value)
{
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = clean_tree($v);
        }
        return $out;
    }
    if (is_string($value)) {
        return clean_text($value);
    }
    if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
        return $value;
    }
    return '';
}
