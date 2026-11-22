<?php
/**
 * SEO for public pages, printed into <head> by layout.php via seo_head():
 * title, description, keywords, robots, canonical + hreflang, Open Graph and
 * Twitter cards, local geo tags, search-console verification, and one JSON-LD
 * @graph (Organization/LocalBusiness, WebSite, WebPage, BreadcrumbList, plus
 * Service or ItemList where the page has one).
 *
 * index.php describes each page in $meta:
 *   title, description, keywords, image, image_alt, doc, noindex,
 *   type     schema.org page type (WebPage, AboutPage, ContactPage, CollectionPage)
 *   crumbs   [[label, path], ...] from Home to the current page
 *   service  ['name', 'description', 'image', 'keywords'] on service pages
 *   list     [[name, path], ...] items the page lists (services overview)
 */

/** One-line plain text for meta tags. */
function seo_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('/\s+/u', ' ', $text));
}

/** Merge comma-separated keyword lists (most specific first), de-duplicated. */
function seo_keywords(string ...$lists): string
{
    $seen = [];
    $out = [];
    foreach ($lists as $list) {
        foreach (preg_split('/\s*[,;\n]+\s*/u', $list) as $k) {
            $k = seo_text($k);
            $key = mb_strtolower($k);
            if ($k === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $k;
        }
    }
    return implode(', ', array_slice($out, 0, 30));
}

/** Absolute URL for a stored image or page path. */
function seo_abs(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    return preg_match('~^https?://~i', $src) ? $src : abs_url('/' . ltrim($src, '/'));
}

/** [width, height] of a local image (for og:image:width / height). */
function seo_image_size(string $src): ?array
{
    if ($src === '' || preg_match('~^(https?:)?//~i', $src)) {
        return null;
    }
    $file = ST_ROOT . '/' . ltrim((string)parse_url($src, PHP_URL_PATH), '/');
    $info = is_file($file) ? @getimagesize($file) : false;
    return $info ? [(int)$info[0], (int)$info[1]] : null;
}

/** When a content doc was last published (ISO 8601), or ''. */
function seo_modified(?string $doc): string
{
    if (!$doc) {
        return '';
    }
    $row = Content::row($doc);
    $at = $row['published_at'] ?? ($row['updated_at'] ?? null);
    return $at ? date('c', strtotime((string)$at)) : '';
}

/** Breadcrumb trail for a page: Home → … → $label. */
function seo_crumbs(string $label, string $path, array $middle = []): array
{
    return array_merge([['Home', '/']], $middle, [[$label, $path]]);
}

/** Social profile URLs from the header and footer (deduplicated). */
function seo_same_as(Doc $site): array
{
    $urls = [];
    foreach (array_merge($site->each('social'), $site->each('footer.social')) as $s) {
        $href = $s->str('href');
        if (preg_match('~^https?://~i', $href)) {
            $urls[strtolower(rtrim($href, '/'))] = $href;
        }
    }
    return array_values($urls);
}

/** "@handle" from the Twitter / X profile link, or ''. */
function seo_twitter_handle(Doc $site): string
{
    foreach ($site->each('social') as $s) {
        if (in_array(strtolower($s->str('icon')), ['twitter', 'x'], true)
            && preg_match('~(?:twitter|x)\.com/@?([A-Za-z0-9_]{1,15})/?$~i', $s->str('href'), $m)) {
            return '@' . $m[1];
        }
    }
    return '';
}

/** Everything that goes in <head> for search engines and link previews. */
function seo_head(array $meta, Doc $site): string
{
    $editing = edit_mode();
    $title = page_title(seo_text((string)($meta['title'] ?? '')));
    $description = seo_text((string)($meta['description'] ?? '')) ?: seo_text($site->str('seo.description'));
    $keywords = seo_keywords((string)($meta['keywords'] ?? ''), $site->str('seo.keywords'));
    $canonical = abs_url(current_path());
    $imageSrc = (string)($meta['image'] ?? '') ?: $site->str('seo.og_image');
    $image = seo_abs($imageSrc);
    $imageAlt = seo_text((string)($meta['image_alt'] ?? '')) ?: ($site->str('seo.og_image_alt') ?: $title);
    $brand = $site->str('brand.name');
    $noindex = !empty($meta['noindex']) || $editing;

    $tags = [];
    $tags[] = '<title>' . e($title) . '</title>';
    $tags[] = '<meta name="description" content="' . e($description) . '">';
    if ($keywords !== '') {
        $tags[] = '<meta name="keywords" content="' . e($keywords) . '">';
    }
    $robots = $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $tags[] = '<meta name="robots" content="' . $robots . '">';
    $tags[] = '<meta name="googlebot" content="' . $robots . '">';
    $tags[] = '<meta name="author" content="' . e($brand) . '">';
    if (!$noindex) {
        $tags[] = '<link rel="canonical" href="' . e($canonical) . '">';
        $tags[] = '<link rel="alternate" hreflang="en" href="' . e($canonical) . '">';
        $tags[] = '<link rel="alternate" hreflang="x-default" href="' . e($canonical) . '">';
    }

    // Search console ownership (Admin → Settings → SEO).
    foreach (['google-site-verification' => 'verify_google', 'msvalidate.01' => 'verify_bing', 'yandex-verification' => 'verify_yandex'] as $name => $key) {
        $code = trim((string)Settings::get($key, ''));
        if ($code !== '' && preg_match('/^[A-Za-z0-9_\-=.]{6,200}$/', $code)) {
            $tags[] = '<meta name="' . $name . '" content="' . e($code) . '">';
        }
    }

    // Local business signals.
    if ($site->has('seo.geo_region')) {
        $tags[] = '<meta name="geo.region" content="' . $site->e('seo.geo_region') . '">';
    }
    if ($site->has('seo.geo_placename')) {
        $tags[] = '<meta name="geo.placename" content="' . $site->e('seo.geo_placename') . '">';
    }
    if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*[;,]\s*(-?\d+(?:\.\d+)?)\s*$/', $site->str('seo.geo_position'), $geo)) {
        $tags[] = '<meta name="geo.position" content="' . e($geo[1] . ';' . $geo[2]) . '">';
        $tags[] = '<meta name="ICBM" content="' . e($geo[1] . ', ' . $geo[2]) . '">';
    }

    // Open Graph (WhatsApp, LinkedIn, Facebook) + Twitter / X.
    $tags[] = '<meta property="og:type" content="' . (!empty($meta['service']) ? 'article' : 'website') . '">';
    $tags[] = '<meta property="og:site_name" content="' . e($brand) . '">';
    $tags[] = '<meta property="og:locale" content="' . e($site->str('seo.locale') ?: 'en_IN') . '">';
    $tags[] = '<meta property="og:title" content="' . e($title) . '">';
    $tags[] = '<meta property="og:description" content="' . e($description) . '">';
    $tags[] = '<meta property="og:url" content="' . e($canonical) . '">';
    if ($image !== '') {
        $tags[] = '<meta property="og:image" content="' . e($image) . '">';
        if (str_starts_with($image, 'https://')) {
            $tags[] = '<meta property="og:image:secure_url" content="' . e($image) . '">';
        }
        if ($size = seo_image_size($imageSrc)) {
            $tags[] = '<meta property="og:image:width" content="' . $size[0] . '">';
            $tags[] = '<meta property="og:image:height" content="' . $size[1] . '">';
        }
        $tags[] = '<meta property="og:image:alt" content="' . e($imageAlt) . '">';
    }
    $modified = seo_modified($meta['doc'] ?? null);
    if ($modified !== '') {
        $tags[] = '<meta property="og:updated_time" content="' . e($modified) . '">';
    }
    $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $tags[] = '<meta name="twitter:title" content="' . e($title) . '">';
    $tags[] = '<meta name="twitter:description" content="' . e($description) . '">';
    if ($image !== '') {
        $tags[] = '<meta name="twitter:image" content="' . e($image) . '">';
        $tags[] = '<meta name="twitter:image:alt" content="' . e($imageAlt) . '">';
    }
    if ($handle = seo_twitter_handle($site)) {
        $tags[] = '<meta name="twitter:site" content="' . e($handle) . '">';
    }

    if (!$noindex) {
        $graph = seo_graph($meta, $site, $canonical, $title, $description, $image, $modified);
        $tags[] = '<script type="application/ld+json">'
            . json_encode(['@context' => 'https://schema.org', '@graph' => $graph],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
            . '</script>';
    }
    return "  " . implode("\n  ", $tags) . "\n";
}

/** schema.org structured data for the current page. */
function seo_graph(array $meta, Doc $site, string $canonical, string $title, string $description, string $image, string $modified): array
{
    $home = abs_url('/');
    $orgId = $home . '#organization';
    $c = $site->at('contact');
    $phone = preg_replace('/[^0-9+]/', '', $c->str('phone_href'));
    $email = preg_replace('/^mailto:/i', '', $c->str('email_href')) ?: $c->str('email_display');
    $areas = array_values(array_filter(array_map('trim', explode(',', $site->str('seo.area_served')))));
    $areaServed = array_map(function ($name) {
        return ['@type' => 'Country', 'name' => $name];
    }, $areas);
    $services = array_values(array_map(function (Doc $s) {
        return $s->str('title');
    }, services_list()));

    $address = array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => trim($c->str('address_title') . ', ' . implode(' ', $c->lines('address_lines')), " ,"),
        'addressLocality' => $c->str('city'),
        'addressRegion' => $c->str('state'),
        'postalCode' => $c->str('postal_code'),
        'addressCountry' => $c->str('country_code'),
    ]);

    $org = [
        '@type' => ['Organization', 'LocalBusiness'],
        '@id' => $orgId,
        'name' => $site->str('brand.name'),
        'legalName' => $site->str('brand.name'),
        'alternateName' => $site->str('footer.wordmark') ?: 'SPARKTECH',
        'url' => $home,
        'logo' => ['@type' => 'ImageObject', 'url' => seo_abs($site->image('brand.logo')['src'])],
        'image' => seo_abs($site->str('seo.og_image')),
        'description' => seo_text($site->str('seo.description')),
        'telephone' => $phone,
        'email' => $email,
        'address' => $address,
        'foundingDate' => $site->str('seo.founding_year'),
        'openingHours' => $site->str('seo.opening_hours'),
        'areaServed' => $areaServed,
        'knowsAbout' => $services,
        'sameAs' => seo_same_as($site),
        'contactPoint' => [array_filter([
            '@type' => 'ContactPoint',
            'telephone' => $phone,
            'email' => $email,
            'contactType' => 'sales',
            'areaServed' => $areas,
            'availableLanguage' => ['English'],
        ])],
    ];
    if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*[;,]\s*(-?\d+(?:\.\d+)?)\s*$/', $site->str('seo.geo_position'), $geo)) {
        $org['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$geo[1], 'longitude' => (float)$geo[2]];
        $org['hasMap'] = 'https://www.google.com/maps?q=' . $geo[1] . ',' . $geo[2];
    }
    $org = array_filter($org, function ($v) {
        return $v !== '' && $v !== [] && $v !== null;
    });

    $graph = [$org, [
        '@type' => 'WebSite',
        '@id' => $home . '#website',
        'url' => $home,
        'name' => $site->str('brand.name'),
        'alternateName' => $org['alternateName'],
        'description' => seo_text($site->str('seo.description')),
        'publisher' => ['@id' => $orgId],
        'inLanguage' => 'en-IN',
    ]];

    $page = [
        '@type' => $meta['type'] ?? 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $title,
        'description' => $description,
        'isPartOf' => ['@id' => $home . '#website'],
        'about' => ['@id' => $orgId],
        'inLanguage' => 'en-IN',
    ];
    $keywords = seo_text((string)($meta['keywords'] ?? ''));
    if ($keywords !== '') {
        $page['keywords'] = $keywords;
    }
    if ($image !== '') {
        $page['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $image];
    }
    if ($modified !== '') {
        $page['dateModified'] = $modified;
    }

    $crumbs = $meta['crumbs'] ?? [];
    if (count($crumbs) > 1) {
        $page['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        $items = [];
        foreach (array_values($crumbs) as $i => $cr) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => seo_text((string)$cr[0]), 'item' => abs_url((string)$cr[1])];
        }
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
    }

    if (!empty($meta['service'])) {
        $sv = $meta['service'];
        $service = array_filter([
            '@type' => 'Service',
            '@id' => $canonical . '#service',
            'name' => seo_text((string)$sv['name']),
            'serviceType' => seo_text((string)$sv['name']),
            'description' => seo_text((string)($sv['description'] ?? '')),
            'url' => $canonical,
            'image' => seo_abs((string)($sv['image'] ?? '')),
            'provider' => ['@id' => $orgId],
            'areaServed' => $areaServed,
            'category' => 'Edible oil processing plant',
            'keywords' => seo_text((string)($sv['keywords'] ?? '')),
        ]);
        $graph[] = $service;
        $page['mainEntity'] = ['@id' => $canonical . '#service'];
    }

    if (!empty($meta['list'])) {
        $items = [];
        foreach (array_values($meta['list']) as $i => $it) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => seo_text((string)$it[0]), 'url' => abs_url((string)$it[1])];
        }
        $graph[] = ['@type' => 'ItemList', '@id' => $canonical . '#list', 'numberOfItems' => count($items), 'itemListElement' => $items];
        $page['mainEntity'] = ['@id' => $canonical . '#list'];
    }

    $graph[] = $page;
    return $graph;
}
