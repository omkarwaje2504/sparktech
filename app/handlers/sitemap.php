<?php
/** GET /sitemap.xml — every public page with last-published date and its main image. */
header('Content-Type: application/xml; charset=utf-8');

$heroImage = function (Doc $d): string {
    $img = $d->image('seo.image')['src'] ?: $d->image('hero.image')['src'] ?: $d->image('hero.poster')['src'];
    return (string)$img;
};

// [path, doc, priority, changefreq, image]
$entries = [];
foreach (['/' => 'home', '/about/' => 'about', '/services/' => 'services', '/technologies/' => 'technologies',
    '/capabilities/' => 'capabilities', '/gallery/' => 'gallery', '/contact/' => 'contact'] as $path => $doc) {
    $d = Content::doc($doc);
    $entries[] = [$path, $doc, $path === '/' ? '1.0' : ($doc === 'services' ? '0.9' : '0.8'), $path === '/' ? 'weekly' : 'monthly',
        $heroImage($d) ?: Content::doc('site')->str('seo.og_image')];
}
foreach (services_list() as $s) {
    $entries[] = ['/services/' . $s->str('slug') . '/', 'services', '0.9', 'monthly', $s->image('image')['src']];
}
foreach (array_keys(Content::customPages()) as $slug) {
    $entries[] = ['/' . $slug . '/', 'page:' . $slug, '0.3', 'yearly', ''];
}

$x = function ($v) {
    return htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
};
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
$seen = [];
foreach ($entries as [$path, $doc, $priority, $freq, $image]) {
    if (isset($seen[$path])) {
        continue;
    }
    $seen[$path] = true;
    echo "  <url>\n    <loc>" . $x(abs_url($path)) . "</loc>\n";
    $modified = seo_modified($doc);
    if ($modified !== '') {
        echo '    <lastmod>' . $x($modified) . "</lastmod>\n";
    }
    echo "    <changefreq>$freq</changefreq>\n    <priority>$priority</priority>\n";
    if ($image !== '' && !preg_match('~\.(mp4|webm)$~i', $image)) {
        echo "    <image:image><image:loc>" . $x(seo_abs($image)) . "</image:loc></image:image>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>';
