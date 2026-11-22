<?php
/**
 * View rendering: page template → layout.
 */

/** Current request path relative to the base path, e.g. "/about/". */
function current_path(): string
{
    return $GLOBALS['ST_PATH'] ?? '/';
}

function is_active_path(string $href): bool
{
    $path = current_path();
    $href = '/' . trim(parse_url($href, PHP_URL_PATH) ?: '/', '/');
    $href = $href === '/' ? '/' : $href . '/';
    if ($href === '/') {
        return $path === '/';
    }
    return str_starts_with($path, $href);
}

/**
 * Render a page view inside the site layout.
 *  $meta: title (string), description, image, doc (content doc for page settings), status (HTTP code)
 */
function render_page(string $view, array $vars = [], array $meta = []): void
{
    $site = Content::doc('site');
    extract($vars, EXTR_SKIP);
    ob_start();
    require ST_APP . '/views/pages/' . $view . '.php';
    $content = (string)ob_get_clean();

    if (!empty($meta['status'])) {
        http_response_code((int)$meta['status']);
    }
    if (is_admin()) {
        header('Cache-Control: no-store, private');
    }
    require ST_APP . '/views/layout.php';
}

/** JSON handed to the live editor (assets/js/editor.js). */
function cms_editor_payload(array $meta): string
{
    $docs = [];
    foreach (Content::usedDocs() as $name) {
        $docs[$name] = Content::data($name, true);
    }
    $pages = [['/', 'Home'], ['/about/', 'About'], ['/services/', 'Services']];
    foreach (services_list(true) as $s) {
        $pages[] = ['/services/' . $s->str('slug') . '/', '— ' . ($s->str('menu_title') ?: $s->str('title'))];
    }
    $pages[] = ['/technologies/', 'Technologies'];
    $pages[] = ['/capabilities/', 'Capabilities'];
    $pages[] = ['/gallery/', 'Gallery'];
    $pages[] = ['/contact/', 'Contact'];
    foreach (Content::customPages(true) as $slug => $title) {
        $pages[] = ['/' . $slug . '/', $title];
    }
    $payload = [
        'api' => url('/admin/api.php'),
        'admin' => url('/admin/'),
        'base' => base_path(),
        'csrf' => csrf_token(),
        'page' => [
            'doc' => $meta['doc'] ?? null,
            'seo' => $meta['seo'] ?? 'seo',
            'path' => current_path(),
        ],
        'docs' => $docs,
        'pending' => count(Content::pending()),
        'popups' => array_values(array_filter(array_map(function (Doc $p) {
            return ['id' => slugify($p->str('id')), 'title' => $p->str('title')];
        }, Content::doc('site')->each('popups')), function ($p) {
            return $p['id'] !== '';
        })),
        'blockTypes' => Content::blockTypes(),
        'icons' => array_combine(glyph_names(), array_map(function ($n) {
            return glyph($n, '', 1.6);
        }, glyph_names())),
        'pages' => $pages,
        'user' => (current_user()['name'] ?? '') ?: (current_user()['username'] ?? ''),
        'uploadMax' => ini_get('upload_max_filesize'),
    ];
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/** Build the <title> from a page title + site suffix. */
function page_title(string $title): string
{
    $site = Content::doc('site');
    $full = $site->str('seo.title');
    if ($title === '' || $title === $full) {
        return $full;
    }
    if (str_contains($title, '|')) {
        return $title;
    }
    return $title . $site->str('seo.title_suffix');
}
