<?php
/**
 * Content store.
 *
 * Every page's editable content is one JSON "doc" (home, about, site, ...).
 * The code ships defaults (app/defaults/*.php); the database keeps a draft
 * copy (what the live editor writes) and a published copy (what visitors
 * see). Reads merge the stored copy over the defaults, so newly added
 * template keys show up without a migration.
 */
final class Content
{
    /** Built-in docs, in admin display order => label */
    const BUILTIN = [
        'site' => 'Global (header, footer, contact details)',
        'home' => 'Home',
        'about' => 'About',
        'services' => 'Services + service pages',
        'technologies' => 'Technologies',
        'capabilities' => 'Capabilities',
        'gallery' => 'Gallery',
        'contact' => 'Contact',
    ];

    /** Folder names that can never be used as custom page slugs. */
    const RESERVED = ['admin', 'app', 'assets', 'images', 'uploads', 'storage', 'services', 'about',
        'technologies', 'capabilities', 'gallery', 'contact', 'install', 'index', 'sitemap', 'robots', 'api'];

    private static $cache = [];
    private static $rows = [];
    private static $used = [];
    private static $defaults = [];

    public static function validName(string $name): bool
    {
        return (bool)preg_match('/^[a-z0-9_-]{1,60}(:[a-z0-9_-]{1,60})?$/', $name);
    }

    /** Use draft content? (only in edit mode) */
    public static function useDraft(): bool
    {
        return edit_mode();
    }

    public static function defaults(string $name): array
    {
        if (isset(self::$defaults[$name])) {
            return self::$defaults[$name];
        }
        $data = [];
        if (str_starts_with($name, 'page:')) {
            $slug = substr($name, 5);
            $pages = self::loadDefaultsFile('pages');
            $data = $pages[$slug] ?? self::blankPage(ucwords(str_replace('-', ' ', $slug)));
        } else {
            $data = self::loadDefaultsFile($name);
        }
        return self::$defaults[$name] = $data;
    }

    private static function loadDefaultsFile(string $file): array
    {
        $path = ST_APP . '/defaults/' . $file . '.php';
        if (!is_file($path)) {
            return [];
        }
        $data = require $path;
        return is_array($data) ? $data : [];
    }

    public static function blankPage(string $title): array
    {
        return [
            'seo' => ['title' => $title, 'description' => ''],
            'hero' => [
                'eyebrow' => 'SPARKTECH Processes LLP',
                'crumb' => $title,
                'title' => $title,
                'highlight' => '',
                'subtitle' => '',
                'image' => '/images/about-us/banner.webp',
            ],
            'body' => '',
            'blocks' => [self::blockTemplate('text')],
        ];
    }

    /** Section types an admin can add ("+ Add a section"): type => [label, hint]. */
    public static function blockTypes(): array
    {
        $out = [];
        foreach (self::loadDefaultsFile('blocks') as $type => $def) {
            $out[$type] = ['label' => $def['label'], 'hint' => $def['hint']];
        }
        return $out;
    }

    /** Fresh data for a new section of $type (null if unknown). */
    public static function blockTemplate(string $type): ?array
    {
        $all = self::loadDefaultsFile('blocks');
        if (!isset($all[$type])) {
            return null;
        }
        $data = $all[$type]['data'];
        $data['uid'] = substr(random_token(4), 0, 8);
        return $data;
    }

    /** Raw DB row for a doc (cached per request). */
    public static function row(string $name): ?array
    {
        if (!array_key_exists($name, self::$rows)) {
            try {
                self::$rows[$name] = DB::row('SELECT * FROM {content_docs} WHERE doc = ?', [$name]);
            } catch (Throwable $e) {
                self::$rows[$name] = null;
            }
        }
        return self::$rows[$name];
    }

    /** Stored (non-merged) data for the active mode. */
    public static function stored(string $name, ?bool $draft = null): ?array
    {
        $draft = $draft ?? self::useDraft();
        $row = self::row($name);
        if (!$row) {
            return null;
        }
        $json = $draft ? ($row['draft'] ?? $row['published']) : $row['published'];
        if ($json === null || $json === '') {
            return null;
        }
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : null;
    }

    /** Merged content (defaults + stored) for rendering. */
    public static function data(string $name, ?bool $draft = null): array
    {
        $draft = $draft ?? self::useDraft();
        $key = $name . ($draft ? '#d' : '#p');
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = self::upgrade($name, self::merge(self::defaults($name), self::stored($name, $draft) ?? []));
        }
        self::$used[$name] = true;
        return self::$cache[$key];
    }

    public static function doc(string $name): Doc
    {
        self::data($name);
        return new Doc($name);
    }

    public static function usedDocs(): array
    {
        return array_keys(self::$used);
    }

    /**
     * Bring stored content up to the current defaults where the admin never
     * changed it: values still equal to an old default (defaults/legacy.php)
     * get the new default, and services saved before items had SEO fields get
     * them from the default item with the same slug.
     */
    private static function upgrade(string $name, array $data): array
    {
        static $legacy = null;
        if ($legacy === null) {
            $legacy = self::loadDefaultsFile('legacy');
        }
        foreach ($legacy[$name] ?? [] as $path => $oldValues) {
            $cur = self::getPath($data, $path);
            if (is_string($cur) && in_array(trim($cur), array_map('trim', (array)$oldValues), true)) {
                $new = self::getPath(self::defaults($name), $path);
                if (is_string($new)) {
                    self::setPath($data, $path, $new);
                }
            }
        }
        if ($name === 'services' && is_array($data['items'] ?? null)) {
            $bySlug = [];
            foreach (self::defaults('services')['items'] ?? [] as $item) {
                $bySlug[(string)($item['slug'] ?? '')] = $item['seo'] ?? [];
            }
            foreach ($data['items'] as $i => $item) {
                $def = $bySlug[(string)($item['slug'] ?? '')] ?? [];
                if (!is_array($item) || $def === []) {
                    continue;
                }
                $seo = is_array($item['seo'] ?? null) ? $item['seo'] : [];
                foreach ($def as $k => $v) {
                    if (trim((string)($seo[$k] ?? '')) === '') {
                        $seo[$k] = $v;
                    }
                }
                $data['items'][$i]['seo'] = $seo;
            }
        }
        return $data;
    }

    /** Stored data overrides defaults; maps merge, lists are replaced. */
    public static function merge(array $defaults, array $stored): array
    {
        foreach ($stored as $k => $v) {
            if (isset($defaults[$k]) && is_array($defaults[$k]) && is_array($v)
                && !is_list_array($defaults[$k]) && $defaults[$k] !== []) {
                $defaults[$k] = ($v === []) ? $defaults[$k] : self::merge($defaults[$k], $v);
            } else {
                $defaults[$k] = $v;
            }
        }
        return $defaults;
    }

    public static function getPath(array $data, string $path, $default = null)
    {
        if ($path === '') {
            return $data;
        }
        $cur = $data;
        foreach (explode('.', $path) as $seg) {
            if (is_array($cur) && array_key_exists($seg, $cur)) {
                $cur = $cur[$seg];
            } elseif (is_array($cur) && ctype_digit($seg) && array_key_exists((int)$seg, $cur)) {
                $cur = $cur[(int)$seg];
            } else {
                return $default;
            }
        }
        return $cur;
    }

    public static function setPath(array &$data, string $path, $value): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)*$/', $path)) {
            throw new InvalidArgumentException('Invalid content path: ' . $path);
        }
        $segs = explode('.', $path);
        $ref = &$data;
        foreach ($segs as $i => $seg) {
            $key = ctype_digit($seg) ? (int)$seg : $seg;
            if ($i === count($segs) - 1) {
                $ref[$key] = $value;
                return;
            }
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
    }

    /** Current draft (merged with defaults) used as the base for an edit. */
    public static function draftForEdit(string $name): array
    {
        $stored = self::stored($name, true) ?? [];
        return self::upgrade($name, self::merge(self::defaults($name), $stored));
    }

    public static function saveDraft(string $name, array $data, ?int $userId): void
    {
        if (!self::validName($name)) {
            throw new InvalidArgumentException('Invalid document name.');
        }
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $row = DB::row('SELECT doc FROM {content_docs} WHERE doc = ?', [$name]);
        if ($row) {
            DB::update('content_docs', ['draft' => $json, 'updated_at' => now(), 'updated_by' => $userId], 'doc = ?', [$name]);
        } else {
            DB::insert('content_docs', ['doc' => $name, 'draft' => $json, 'published' => null,
                'updated_at' => now(), 'updated_by' => $userId]);
        }
        self::forget($name);
    }

    public static function forget(string $name): void
    {
        unset(self::$rows[$name], self::$cache[$name . '#d'], self::$cache[$name . '#p']);
    }

    /** Docs whose draft differs from what is live. */
    public static function pending(): array
    {
        try {
            $rows = DB::all('SELECT doc, draft, published, updated_at FROM {content_docs} WHERE draft IS NOT NULL');
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            if ($r['draft'] !== $r['published']) {
                $out[] = $r['doc'];
            }
        }
        return $out;
    }

    public static function publish(array $names, ?int $userId, string $note = 'Published'): int
    {
        $n = 0;
        foreach ($names as $name) {
            $row = DB::row('SELECT * FROM {content_docs} WHERE doc = ?', [$name]);
            if (!$row || $row['draft'] === null || $row['draft'] === $row['published']) {
                continue;
            }
            DB::transaction(function () use ($row, $name, $userId, $note) {
                if ($row['published'] !== null) {
                    DB::insert('revisions', ['doc' => $name, 'data' => $row['published'], 'note' => 'Before: ' . $note,
                        'user_id' => $userId, 'created_at' => now()]);
                }
                DB::update('content_docs', ['published' => $row['draft'], 'published_at' => now(), 'updated_by' => $userId],
                    'doc = ?', [$name]);
                DB::insert('revisions', ['doc' => $name, 'data' => $row['draft'], 'note' => $note,
                    'user_id' => $userId, 'created_at' => now()]);
            });
            self::forget($name);
            $n++;
        }
        self::pruneRevisions();
        return $n;
    }

    public static function discard(array $names): int
    {
        $n = 0;
        foreach ($names as $name) {
            $row = DB::row('SELECT * FROM {content_docs} WHERE doc = ?', [$name]);
            if (!$row) {
                continue;
            }
            if ($row['published'] === null) {
                DB::exec('DELETE FROM {content_docs} WHERE doc = ?', [$name]);
            } else {
                DB::update('content_docs', ['draft' => $row['published']], 'doc = ?', [$name]);
            }
            self::forget($name);
            $n++;
        }
        return $n;
    }

    /** Keep the 60 most recent revisions per doc. */
    public static function pruneRevisions(): void
    {
        try {
            foreach (DB::all('SELECT doc, COUNT(*) AS c FROM {revisions} GROUP BY doc') as $r) {
                if ((int)$r['c'] > 60) {
                    $cut = DB::value('SELECT id FROM {revisions} WHERE doc = ? ORDER BY id DESC LIMIT 1 OFFSET 59', [$r['doc']]);
                    if ($cut) {
                        DB::exec('DELETE FROM {revisions} WHERE doc = ? AND id < ?', [$r['doc'], (int)$cut]);
                    }
                }
            }
        } catch (Throwable $e) {
            // non-critical
        }
    }

    // ---- Custom pages -------------------------------------------------------

    /** All custom simple pages: slug => title (published or draft). */
    public static function customPages(bool $includeDrafts = false): array
    {
        $pages = [];
        foreach (array_keys(self::loadDefaultsFile('pages')) as $slug) {
            $pages[$slug] = true;
        }
        try {
            foreach (DB::all("SELECT doc, draft, published FROM {content_docs} WHERE doc LIKE 'page:%'") as $r) {
                $pages[substr($r['doc'], 5)] = true;
            }
        } catch (Throwable $e) {
        }
        $out = [];
        foreach (array_keys($pages) as $slug) {
            if (self::pageExists($slug, $includeDrafts)) {
                $data = self::data('page:' . $slug, $includeDrafts);
                $out[$slug] = (string)($data['hero']['title'] ?? $slug);
            }
        }
        return $out;
    }

    public static function pageExists(string $slug, bool $draft = false): bool
    {
        if (!preg_match('/^[a-z0-9-]{1,60}$/', $slug)) {
            return false;
        }
        $name = 'page:' . $slug;
        $stored = self::stored($name, $draft);
        if ($stored !== null) {
            return empty($stored['_deleted']);
        }
        if ($draft && self::row($name)) {
            return true;
        }
        $defaults = self::loadDefaultsFile('pages');
        return isset($defaults[$slug]);
    }
}
