<?php
/**
 * SPARKTECH CMS — public front controller.
 * .htaccess sends every request that is not a real file here.
 */
require __DIR__ . '/app/bootstrap.php';

// ---- Resolve the request path (relative to the install folder) --------------
$uri = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rawurldecode($uri);
$base = base_path();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . ltrim($uri, '/');
$uri = preg_replace('~/+~', '/', $uri);

// Old static-export URLs: /about.html, /about/index.html → /about/
if (preg_match('~^(.*?)/?(index)?\.html$~', $uri, $m)) {
    redirect(rtrim($m[1], '/') . '/', 301);
}

// Trailing slash for page URLs (not for files like sitemap.xml).
if ($uri !== '/' && substr($uri, -1) !== '/' && !preg_match('~\.[a-z0-9]{2,5}$~i', $uri) && !in_array($uri, ['/contact/submit', '/collect'], true)) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    redirect($uri . '/' . ($qs !== '' ? '?' . $qs : ''), 301);
}
$GLOBALS['ST_PATH'] = $uri;
$segments = array_values(array_filter(explode('/', trim($uri, '/')), 'strlen'));

// ---- Special endpoints ---------------------------------------------------------
if ($uri === '/contact/submit' || $uri === '/contact/submit/') {
    require ST_APP . '/handlers/contact-submit.php';
    exit;
}
if ($uri === '/collect') {
    require ST_APP . '/handlers/collect.php';
    exit;
}
if ($uri === '/sitemap.xml') {
    require ST_APP . '/handlers/sitemap.php';
    exit;
}
if ($uri === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /app/\nDisallow: /storage/\nDisallow: /install.php\n"
        . "Disallow: /contact/submit\nDisallow: /collect\nDisallow: /*?sent=\nDisallow: /*?error=\n"
        . "\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    exit;
}

// ---- Page routes -----------------------------------------------------------------
// path => [view, content doc, schema.org page type]
$builtin = [
    '' => ['home', 'home', 'WebPage'],
    'about' => ['about', 'about', 'AboutPage'],
    'services' => ['services', 'services', 'CollectionPage'],
    'technologies' => ['technologies', 'technologies', 'WebPage'],
    'capabilities' => ['capabilities', 'capabilities', 'WebPage'],
    'gallery' => ['gallery', 'gallery', 'CollectionPage'],
    'contact' => ['contact', 'contact', 'ContactPage'],
];

$first = $segments[0] ?? '';
$count = count($segments);

if ($count <= 1 && isset($builtin[$first])) {
    [$view, $docName, $type] = $builtin[$first];
    $doc = Content::doc($docName);
    $path = $first === '' ? '/' : '/' . $first . '/';
    $meta = [
        'title' => $doc->str('seo.title'),
        'description' => $doc->str('seo.description'),
        'keywords' => $doc->str('seo.keywords'),
        'image' => $doc->str('seo.image'),
        'doc' => $docName,
        'type' => $type,
        'crumbs' => $first === '' ? [] : seo_crumbs($doc->str('hero.crumb') ?: $doc->str('seo.title'), $path),
    ];
    if ($docName === 'services') {
        $meta['list'] = array_values(array_map(function (Doc $s) {
            return [$s->str('title'), '/services/' . $s->str('slug') . '/'];
        }, services_list()));
    }
    render_page($view, [], $meta);
    exit;
}

// /services/{slug}/
if ($count === 2 && $first === 'services') {
    $slug = $segments[1];
    foreach (services_list(edit_mode()) as $index => $s) {
        if ($s->str('slug') === $slug) {
            render_page('service-detail', ['index' => $index], [
                'title' => $s->str('seo.title') ?: $s->str('title'),
                'description' => $s->str('seo.description') ?: $s->str('description'),
                'keywords' => $s->str('seo.keywords'),
                'image' => $s->str('seo.image') ?: $s->image('image')['src'],
                'image_alt' => $s->str('title'),
                'doc' => 'services',
                'seo' => 'items.' . $index . '.seo',
                'type' => 'WebPage',
                'crumbs' => seo_crumbs($s->str('title'), '/services/' . $slug . '/',
                    [[Content::doc('services')->str('hero.crumb') ?: 'Services', '/services/']]),
                'service' => [
                    'name' => $s->str('title'),
                    'description' => $s->str('description'),
                    'image' => $s->image('image')['src'],
                    'keywords' => $s->str('seo.keywords'),
                ],
            ]);
            exit;
        }
    }
}

// /{custom-page}/
if ($count === 1 && Content::pageExists($first, edit_mode())) {
    $doc = Content::doc('page:' . $first);
    $label = trim($doc->str('hero.title') . ' ' . $doc->str('hero.highlight'));
    render_page('page', ['slug' => $first], [
        'title' => $doc->str('seo.title') ?: $label,
        'description' => $doc->str('seo.description'),
        'keywords' => $doc->str('seo.keywords'),
        'image' => $doc->str('seo.image'),
        'doc' => 'page:' . $first,
        'type' => 'WebPage',
        'crumbs' => seo_crumbs($doc->str('hero.crumb') ?: $label, '/' . $first . '/'),
    ]);
    exit;
}

render_page('404', [], ['title' => 'Page not found', 'status' => 404, 'noindex' => true]);
