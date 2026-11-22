<?php
/**
 * First-party visitor analytics — no third-party scripts and no cookies.
 *
 * assets/js/track.js keeps a random visitor ID (the "visitor hash") in the
 * browser's localStorage plus a 30-minute visit ID, and sends page views,
 * clicks and time-on-page to /collect (app/handlers/collect.php). This class
 * checks and stores them:
 *   an_visitors  one row per browser: first/last seen, visits, first source, device, location
 *   an_sessions  one row per visit: source/campaign, landing/exit page, duration, genuine/bot
 *   an_events    page views (time on page, scroll depth) and clicks, in order = the journey
 *   an_geo       IP-network → location cache (one lookup per network per 30 days)
 *
 * "Genuine" = not a bot AND the visitor interacted (scrolled, tapped, typed,
 * clicked) or stayed 10+ seconds. Bots (user agent, automation flags, floods)
 * are kept out of every report except the "bots filtered" count.
 * Admin → Analytics shows the reports (admin/analytics.php).
 */
final class Analytics
{
    const SESSION_TIMEOUT = 1800;
    const MAX_EVENTS_PER_MINUTE = 90;
    /** New visits per hour from one network (/24): above SOFT they count as bots, above HARD they are dropped. */
    const NETWORK_SOFT_LIMIT = 60;
    const NETWORK_HARD_LIMIT = 150;

    /** User agents of crawlers, previews, monitors, scripts and headless browsers. */
    const BOT_UA = '~(?<!cu)bot\b|bot/|crawl|spider|slurp|mediapartners|adsbot|facebookexternalhit|facebookcatalog|embedly|quora link|outbrain|'
        . 'pinterest|vkshare|w3c_validator|whatsapp|telegram|discord|slack|linkedinbot|skypeuripreview|headless|phantomjs|puppeteer|'
        . 'playwright|selenium|webdriver|lighthouse|pagespeed|gtmetrix|pingdom|uptime|statuscake|site24x7|monitor|preview|'
        . 'curl|wget|python|httpclient|java/|go-http|okhttp|axios|node-fetch|undici|scrapy|libwww|perl|ruby|php/|guzzle|'
        . 'ahrefs|semrush|mj12|dotbot|petal|yandex|baidu|bytespider|gptbot|chatgpt-user|oai-searchbot|claudebot|claude-web|anthropic|'
        . 'perplexity|ccbot|applebot|duckduckbot|sogou|exabot|seznam|ia_archiver|archive\.org|bingpreview|google-inspectiontool|'
        . 'googleother|feedfetcher|feed|rss|validator|scan|check|probe~i';

    /** Deferred work (location lookups) run after the beacon has been answered. */
    private static $deferred = [];

    public static function enabled(): bool
    {
        return (string)Settings::get('an_enabled', '1') === '1';
    }

    /** Track this request? Not when disabled, for logged-in admins, or from excluded IPs. */
    public static function trackable(): bool
    {
        return self::enabled() && !is_admin() && !self::ipExcluded(request_ip());
    }

    public static function ipExcluded(string $ip): bool
    {
        foreach (preg_split('/[\s,]+/', (string)Settings::get('an_exclude_ips', ''), -1, PREG_SPLIT_NO_EMPTY) as $rule) {
            if ($rule === $ip || (substr($rule, -1) === '*' && str_starts_with($ip, rtrim($rule, '*')))) {
                return true;
            }
        }
        return false;
    }

    /** Visitor / visit IDs are 16–40 lowercase hex characters. */
    public static function cleanId(string $id): string
    {
        $id = strtolower(trim($id));
        return preg_match('/^[a-f0-9]{16,40}$/', $id) ? $id : '';
    }

    // ---- Collecting ---------------------------------------------------------

    /** Handle one beacon: t = view | click | leave. */
    public static function collect(array $in): void
    {
        $type = (string)($in['t'] ?? '');
        if (!in_array($type, ['view', 'click', 'leave'], true)) {
            return;
        }
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $vid = self::cleanId((string)($in['vid'] ?? ''));
        if ($vid === '') {
            // Browser without localStorage: a daily hash of network + browser stands in.
            $vid = substr(hash('sha256', cfg('app_key', 'st') . '|' . date('Y-m-d') . '|' . request_ip() . '|' . $ua), 0, 32);
        }
        $sid = self::cleanId((string)($in['sid'] ?? '')) ?: substr(hash('sha256', $vid . date('Y-m-d H')), 0, 32);
        $pid = substr(preg_replace('/[^a-f0-9]/', '', strtolower((string)($in['pid'] ?? ''))), 0, 20);
        $path = self::path((string)($in['p'] ?? '/'));
        $now = now();

        $session = self::session($sid);
        if ($session && $session['vid'] !== $vid) {
            return; // visit ID belongs to another browser — ignore
        }
        if (!$session) {
            if ($type !== 'view') {
                return;
            }
            $session = self::startSession($sid, $vid, $in, $ua, $path);
            if (!$session) {
                return;
            }
        }

        if ((int)$session['is_bot'] === 1) {
            if ($type === 'view') {
                DB::exec('UPDATE {an_sessions} SET pageviews = pageviews + 1, last_at = ? WHERE id = ?', [$now, $session['id']]);
            }
            return;
        }
        $recent = (int)DB::value('SELECT COUNT(*) FROM {an_events} WHERE sid = ? AND created_at > ?', [$sid, date('Y-m-d H:i:s', time() - 60)]);
        if ($recent >= self::MAX_EVENTS_PER_MINUTE) {
            self::markBot($session, 'too many requests');
            return;
        }

        if ($type === 'view') {
            $isFirst = (int)$session['pageviews'] === 0;
            DB::insert('an_events', [
                'sid' => $sid, 'vid' => $vid, 'pid' => $pid, 'type' => 'view', 'path' => $path,
                'title' => self::text($in['ti'] ?? '', 190), 'created_at' => $now,
            ]);
            DB::exec('UPDATE {an_sessions} SET pageviews = pageviews + 1, last_at = ?, exit_path = ? WHERE id = ?', [$now, $path, $session['id']]);
            if (!$isFirst) {
                DB::exec('UPDATE {an_visitors} SET pageviews = pageviews + 1, last_seen = ? WHERE vid = ?', [$now, $vid]);
            }
        } elseif ($type === 'click') {
            $target = self::text($in['h'] ?? '', 255);
            DB::insert('an_events', [
                'sid' => $sid, 'vid' => $vid, 'pid' => $pid, 'type' => 'click', 'path' => $path,
                'label' => self::text($in['l'] ?? '', 190) ?: '(no text)', 'target' => $target,
                'kind' => self::linkKind($target, (string)($in['k'] ?? '')), 'created_at' => $now,
            ]);
            DB::exec('UPDATE {an_sessions} SET clicks = clicks + 1, engaged = 1, last_at = ? WHERE id = ?', [$now, $session['id']]);
            DB::exec('UPDATE {an_visitors} SET clicks = clicks + 1, engaged = 1, last_seen = ? WHERE vid = ?', [$now, $vid]);
        } else { // leave: time on page + scroll depth for the page view $pid
            $secs = max(0, min(1800, (int)($in['s'] ?? 0)));
            $scroll = max(0, min(100, (int)($in['sc'] ?? 0)));
            $human = !empty($in['hu']) || $secs >= 10;
            if ($pid !== '') {
                $view = DB::row("SELECT id, seconds FROM {an_events} WHERE pid = ? AND sid = ? AND type = 'view'", [$pid, $sid]);
                if ($view) {
                    $added = max(0, $secs - (int)$view['seconds']);
                    DB::exec('UPDATE {an_events} SET seconds = ?, scroll = CASE WHEN scroll > ? THEN scroll ELSE ? END WHERE id = ?',
                        [max($secs, (int)$view['seconds']), $scroll, $scroll, $view['id']]);
                    DB::exec('UPDATE {an_sessions} SET duration = duration + ?, last_at = ?' . ($human ? ', engaged = 1' : '') . ' WHERE id = ?',
                        [$added, $now, $session['id']]);
                }
            }
            if ($human) {
                DB::exec('UPDATE {an_visitors} SET engaged = 1, last_seen = ? WHERE vid = ?', [$now, $vid]);
            }
        }

        if (mt_rand(1, 200) === 1) {
            self::$deferred[] = [self::class, 'purge'];
        }
    }

    /** Create the visit (and the visitor on their first visit). */
    private static function startSession(string $sid, string $vid, array $in, string $ua, string $path): ?array
    {
        $now = now();
        $ip = request_ip();
        $bot = self::botReason($ua, $in);
        $fromNetwork = (int)DB::value('SELECT COUNT(*) FROM {an_sessions} WHERE ip_masked = ? AND started_at > ?',
            [self::maskIp($ip), date('Y-m-d H:i:s', time() - 3600)]);
        if ($fromNetwork >= self::NETWORK_HARD_LIMIT) {
            return null;
        }
        if ($bot === '' && $fromNetwork >= self::NETWORK_SOFT_LIMIT) {
            $bot = 'too many visits from one network';
        }
        $src = self::source((string)($in['r'] ?? ''), is_array($in['u'] ?? null) ? $in['u'] : []);
        // Fit the column sizes (MySQL strict mode rejects longer values).
        $src['source'] = mb_substr($src['source'], 0, 60);
        $src['medium'] = mb_substr($src['medium'], 0, 30);
        $dev = self::device($ua, (int)($in['tp'] ?? 0));
        $geo = self::quickLocation($ip, (string)($in['tz'] ?? ''));
        $visitor = $bot === '' ? DB::row('SELECT * FROM {an_visitors} WHERE vid = ?', [$vid]) : null;
        $visitNo = $visitor ? (int)$visitor['sessions'] + 1 : 1;

        $row = [
            'sid' => $sid, 'vid' => $vid, 'started_at' => $now, 'last_at' => $now,
            'is_bot' => $bot !== '' ? 1 : 0, 'bot_reason' => mb_substr($bot, 0, 60),
            'is_new' => $bot === '' && !$visitor ? 1 : 0, 'visit_no' => $visitNo,
            'landing' => $path, 'exit_path' => $path,
            'referrer' => $src['referrer'], 'source' => $src['source'], 'medium' => $src['medium'],
            'campaign' => $src['campaign'], 'term' => $src['term'],
            'device' => $dev['device'], 'os' => $dev['os'], 'browser' => $dev['browser'],
            'screen' => preg_match('/^\d{2,5}x\d{2,5}$/', (string)($in['scr'] ?? '')) ? (string)$in['scr'] : '',
            'lang' => self::text($in['lg'] ?? '', 20), 'tz' => self::text($in['tz'] ?? '', 60),
            'country' => $geo['country'], 'region' => $geo['region'], 'city' => $geo['city'],
            'ip_masked' => self::maskIp($ip),
        ];
        try {
            DB::insert('an_sessions', $row);
        } catch (Throwable $e) {
            return self::session($sid); // created by a parallel request
        }

        if ($bot === '') {
            $place = ['device' => $dev['device'], 'os' => $dev['os'], 'browser' => $dev['browser'],
                'country' => $geo['country'], 'region' => $geo['region'], 'city' => $geo['city']];
            if ($visitor) {
                DB::update('an_visitors', ['sessions' => $visitNo, 'pageviews' => (int)$visitor['pageviews'] + 1, 'last_seen' => $now] + $place,
                    'vid = ?', [$vid]);
            } else {
                try {
                    DB::insert('an_visitors', [
                        'vid' => $vid, 'first_seen' => $now, 'last_seen' => $now, 'sessions' => 1, 'pageviews' => 1,
                        'first_source' => $src['source'], 'first_medium' => $src['medium'],
                        'first_referrer' => $src['referrer'], 'first_landing' => $path,
                    ] + $place);
                } catch (Throwable $e) {
                    // inserted by a parallel request
                }
            }
            if ($geo['lookup']) {
                self::$deferred[] = function () use ($ip, $sid, $vid) {
                    $loc = self::lookup($ip);
                    if ($loc) {
                        DB::update('an_sessions', $loc, 'sid = ?', [$sid]);
                        DB::update('an_visitors', $loc, 'vid = ?', [$vid]);
                    }
                };
            }
        }
        return self::session($sid);
    }

    public static function session(string $sid): ?array
    {
        if ($sid === '') {
            return null;
        }
        try {
            return DB::row('SELECT * FROM {an_sessions} WHERE sid = ?', [$sid]);
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function markBot(array $session, string $why): void
    {
        DB::update('an_sessions', ['is_bot' => 1, 'bot_reason' => $why], 'id = ?', [$session['id']]);
        DB::exec('DELETE FROM {an_events} WHERE sid = ?', [$session['sid']]);
        if ((int)$session['is_new'] === 1) {
            DB::exec('DELETE FROM {an_visitors} WHERE vid = ? AND sessions <= 1', [$session['vid']]);
        }
    }

    /** Link an enquiry to the visit it came from (shown on the lead and in the journey). */
    public static function attachLead(int $leadId, string $vid, string $sid, string $path): void
    {
        try {
            DB::exec('UPDATE {an_visitors} SET lead_id = ?, engaged = 1 WHERE vid = ?', [$leadId, $vid]);
            if ($sid !== '' && self::session($sid)) {
                DB::exec('UPDATE {an_sessions} SET lead_id = ?, engaged = 1 WHERE sid = ?', [$leadId, $sid]);
                DB::insert('an_events', ['sid' => $sid, 'vid' => $vid, 'type' => 'lead', 'path' => self::path($path),
                    'label' => 'Enquiry #' . $leadId, 'kind' => 'form', 'created_at' => now()]);
            }
        } catch (Throwable $e) {
            error_log('Analytics lead link failed: ' . $e->getMessage());
        }
    }

    /** Answer the beacon, then run slow work (location lookups, clean-up). */
    public static function runDeferred(): void
    {
        if (!self::$deferred) {
            return;
        }
        ignore_user_abort(true);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        foreach (self::$deferred as $job) {
            try {
                call_user_func($job);
            } catch (Throwable $e) {
                error_log('Analytics: ' . $e->getMessage());
            }
        }
        self::$deferred = [];
    }

    /** Delete data older than the retention period (Settings → Analytics). */
    public static function purge(): void
    {
        $days = max(30, (int)Settings::get('an_retention_days', '730'));
        $cut = date('Y-m-d H:i:s', time() - $days * 86400);
        DB::exec('DELETE FROM {an_events} WHERE created_at < ?', [$cut]);
        DB::exec('DELETE FROM {an_sessions} WHERE started_at < ?', [$cut]);
        DB::exec('DELETE FROM {an_visitors} WHERE last_seen < ?', [$cut]);
        DB::exec('DELETE FROM {an_geo} WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 30 * 86400)]);
    }

    // ---- Classification -------------------------------------------------------

    /** Why this visit looks automated ('' = looks like a person). */
    public static function botReason(string $ua, array $in): string
    {
        if (strlen($ua) < 25) {
            return 'no browser name';
        }
        if (preg_match(self::BOT_UA, $ua, $m, PREG_OFFSET_CAPTURE)) {
            // Widen the match to the whole product name, e.g. "googlebot", "headlesschrome".
            $start = $m[0][1];
            $end = $start + strlen($m[0][0]);
            while ($start > 0 && preg_match('/[\w.\-]/', $ua[$start - 1])) {
                $start--;
            }
            while ($end < strlen($ua) && preg_match('/[\w.\-]/', $ua[$end])) {
                $end++;
            }
            return mb_substr('bot: ' . strtolower(substr($ua, $start, $end - $start)), 0, 60);
        }
        if (!empty($in['wd'])) {
            return 'automated browser';
        }
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return 'no browser language';
        }
        return '';
    }

    /** Device type, operating system and browser from the user agent. */
    public static function device(string $ua, int $touchPoints = 0): array
    {
        $device = 'Desktop';
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|Android(?!.*Mobile)/i', $ua)
            || (stripos($ua, 'Macintosh') !== false && $touchPoints > 1)) {
            $device = 'Tablet';
        } elseif (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone|Opera Mini/i', $ua)) {
            $device = 'Mobile';
        }

        $os = 'Other';
        foreach ([
            '/Windows NT/i' => 'Windows', '/iPhone|iPod/i' => 'iOS', '/iPad/i' => 'iPadOS', '/Android/i' => 'Android',
            '/CrOS/i' => 'ChromeOS', '/Mac OS X|Macintosh/i' => 'macOS', '/Linux/i' => 'Linux',
        ] as $re => $name) {
            if (preg_match($re, $ua)) {
                $os = $name;
                break;
            }
        }
        if ($os === 'macOS' && $device === 'Tablet') {
            $os = 'iPadOS';
        }

        $browser = 'Other';
        foreach ([
            '/Edg(e|A|iOS)?\//' => 'Edge', '/OPR\/|Opera/' => 'Opera', '/SamsungBrowser/' => 'Samsung Internet',
            '/UCBrowser/' => 'UC Browser', '/YaBrowser/' => 'Yandex', '/Firefox\/|FxiOS/' => 'Firefox',
            '/Chrome\/|CriOS/' => 'Chrome', '/Safari\//' => 'Safari',
        ] as $re => $name) {
            if (preg_match($re, $ua)) {
                $browser = $name;
                break;
            }
        }
        return ['device' => $device, 'os' => $os, 'browser' => $browser];
    }

    /** Where the visit came from: source (Google, LinkedIn…), medium (organic, social…), campaign. */
    public static function source(string $referrer, array $utm): array
    {
        $u = function ($k) use ($utm) {
            return self::text($utm[$k] ?? '', 120);
        };
        $refHost = strtolower((string)parse_url($referrer, PHP_URL_HOST));
        $refHost = preg_replace('/^(www|m|l|lm|mobile)\./', '', $refHost);
        $ownHost = strtolower(preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
        $ownHost = preg_replace('/:\d+$/', '', $ownHost);
        if ($refHost !== '' && $refHost === $ownHost) {
            $refHost = '';
        }
        $out = ['referrer' => $refHost !== '' ? self::text(preg_replace('/[?#].*$/', '', $referrer), 255) : '',
            'source' => 'Direct', 'medium' => 'direct', 'campaign' => $u('utm_campaign'), 'term' => $u('utm_term')];

        $known = [
            'organic' => ['google.' => 'Google', 'bing.com' => 'Bing', 'yahoo.' => 'Yahoo', 'duckduckgo.com' => 'DuckDuckGo',
                'yandex.' => 'Yandex', 'baidu.com' => 'Baidu', 'ecosia.org' => 'Ecosia', 'search.brave.com' => 'Brave Search',
                'naver.com' => 'Naver', 'qwant.com' => 'Qwant', 'startpage.com' => 'Startpage'],
            'ai' => ['chatgpt.com' => 'ChatGPT', 'chat.openai.com' => 'ChatGPT', 'perplexity.ai' => 'Perplexity',
                'claude.ai' => 'Claude', 'gemini.google.com' => 'Gemini', 'copilot.microsoft.com' => 'Copilot'],
            'social' => ['linkedin.com' => 'LinkedIn', 'lnkd.in' => 'LinkedIn', 'facebook.com' => 'Facebook', 'fb.me' => 'Facebook',
                'instagram.com' => 'Instagram', 'twitter.com' => 'X (Twitter)', 'x.com' => 'X (Twitter)', 't.co' => 'X (Twitter)',
                'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube', 'whatsapp.com' => 'WhatsApp', 'wa.me' => 'WhatsApp',
                'pinterest.' => 'Pinterest', 'reddit.com' => 'Reddit', 'quora.com' => 'Quora', 't.me' => 'Telegram'],
            'email' => ['mail.google.com' => 'Gmail', 'outlook.live.com' => 'Outlook', 'outlook.office.com' => 'Outlook',
                'mail.yahoo.com' => 'Yahoo Mail', 'mail.zoho.' => 'Zoho Mail'],
        ];

        if ($u('utm_source') !== '') {
            // "linkedin" / "LinkedIn" / "linkedin.com" all report as LinkedIn.
            $tag = strtolower(preg_replace('/^www\./', '', $u('utm_source')));
            $out['source'] = $u('utm_source');
            foreach ($known as $names) {
                foreach ($names as $needle => $name) {
                    if ($tag === strtolower($name) || $tag === rtrim($needle, '.') || self::hostMatches($tag, $needle)) {
                        $out['source'] = $name;
                        break 2;
                    }
                }
            }
            $out['medium'] = strtolower($u('utm_medium')) ?: 'campaign';
            return $out;
        }
        if (!empty($utm['gclid']) || !empty($utm['gbraid']) || !empty($utm['wbraid'])) {
            return ['source' => 'Google Ads', 'medium' => 'paid'] + $out;
        }
        if (!empty($utm['msclkid'])) {
            return ['source' => 'Microsoft Ads', 'medium' => 'paid'] + $out;
        }
        if (!empty($utm['fbclid'])) {
            return ['source' => 'Facebook', 'medium' => 'social'] + $out;
        }
        if (!empty($utm['li_fat_id'])) {
            return ['source' => 'LinkedIn', 'medium' => 'social'] + $out;
        }
        if ($refHost === '') {
            return $out;
        }
        // AI assistants first (gemini.google.com would otherwise match "google.").
        foreach (['ai', 'email', 'organic', 'social'] as $medium) {
            foreach ($known[$medium] as $needle => $name) {
                if (self::hostMatches($refHost, $needle)) {
                    return ['source' => $name, 'medium' => $medium] + $out;
                }
            }
        }
        return ['source' => mb_substr($refHost, 0, 60), 'medium' => 'referral'] + $out;
    }

    /**
     * Does $host belong to $needle? "google." matches any Google country domain
     * (google.co.in, www.google.com); "t.co" matches t.co or *.t.co but not indiamart.com.
     */
    private static function hostMatches(string $host, string $needle): bool
    {
        if (substr($needle, -1) === '.') {
            return str_starts_with($host, $needle) || str_contains($host, '.' . $needle);
        }
        return $host === $needle || str_ends_with($host, '.' . $needle);
    }

    /** Readable "Google · organic search". */
    public static function sourceLabel(string $source, string $medium): string
    {
        $media = ['organic' => 'search', 'social' => 'social media', 'referral' => 'link from another website',
            'direct' => 'typed address / bookmark', 'email' => 'e-mail', 'paid' => 'paid ads', 'ai' => 'AI assistant',
            'campaign' => 'campaign link'];
        if ($medium === 'direct') {
            return 'Direct (' . $media['direct'] . ')';
        }
        return $source . ' · ' . ($media[$medium] ?? $medium);
    }

    /** What kind of click: phone call, e-mail, WhatsApp, download, other site, page link, button… */
    public static function linkKind(string $href, string $hint): string
    {
        $h = strtolower($href);
        if (str_starts_with($h, 'tel:')) return 'call';
        if (str_starts_with($h, 'mailto:')) return 'email';
        if (preg_match('~wa\.me|whatsapp\.com~', $h)) return 'whatsapp';
        if (str_contains($h, '#popup-')) return 'popup';
        if (preg_match('~\.(pdf|docx?|xlsx?|pptx?|zip|dwg)(\?|$)~', $h)) return 'download';
        if (preg_match('~^https?://~', $h)) {
            $host = strtolower((string)parse_url($h, PHP_URL_HOST));
            $own = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
            if ($host !== '' && preg_replace('/^www\./', '', $host) !== preg_replace('/^www\./', '', $own)) {
                return 'external';
            }
        }
        if ($h === '' || $h[0] === '#') {
            return in_array($hint, ['button', 'submit', 'filter', 'menu'], true) ? $hint : 'button';
        }
        return 'link';
    }

    // ---- Location ---------------------------------------------------------------

    /** Location without waiting: Cloudflare headers, cached lookup, or country from the browser's time zone. */
    private static function quickLocation(string $ip, string $tz): array
    {
        $loc = ['country' => '', 'region' => '', 'city' => '', 'lookup' => false];
        $cf = strtoupper((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $cf) && $cf !== 'XX' && $cf !== 'T1') {
            $loc['country'] = $cf;
            $loc['city'] = self::text($_SERVER['HTTP_CF_IPCITY'] ?? '', 80);
            $loc['region'] = self::text($_SERVER['HTTP_CF_REGION'] ?? '', 80);
            if ($loc['city'] !== '') {
                return $loc;
            }
        }
        if (self::isPublicIp($ip)) {
            try {
                $cached = DB::row('SELECT country, region, city FROM {an_geo} WHERE ip_key = ?', [self::maskIp($ip)]);
            } catch (Throwable $e) {
                $cached = null;
            }
            if ($cached) {
                return ['country' => (string)$cached['country'], 'region' => (string)$cached['region'], 'city' => (string)$cached['city'], 'lookup' => false];
            }
            $loc['lookup'] = Settings::get('geo_provider', 'ipinfo') !== 'none';
        }
        if ($loc['country'] === '' && $tz !== '') {
            $loc['country'] = self::countryFromTimezone($tz);
        }
        return $loc;
    }

    /** Ask the configured location service (Settings → Analytics) and cache the answer for the IP's network. */
    public static function lookup(string $ip): ?array
    {
        $provider = (string)Settings::get('geo_provider', 'ipinfo');
        $token = trim((string)Settings::get('geo_token', ''));
        if ($provider === 'ipapi') {
            $url = 'https://ipapi.co/' . rawurlencode($ip) . '/json/' . ($token !== '' ? '?key=' . rawurlencode($token) : '');
        } elseif ($provider === 'ipinfo') {
            $url = 'https://ipinfo.io/' . rawurlencode($ip) . '/json' . ($token !== '' ? '?token=' . rawurlencode($token) : '');
        } else {
            return null;
        }
        $data = json_decode((string)self::httpGet($url, 3), true);
        if (!is_array($data)) {
            return null;
        }
        $country = strtoupper((string)($data['country_code'] ?? $data['country'] ?? ''));
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            return null;
        }
        $loc = ['country' => $country, 'region' => self::text($data['region'] ?? '', 80), 'city' => self::text($data['city'] ?? '', 80)];
        try {
            $key = self::maskIp($ip);
            DB::exec('DELETE FROM {an_geo} WHERE ip_key = ?', [$key]);
            DB::insert('an_geo', ['ip_key' => $key, 'created_at' => now()] + $loc);
        } catch (Throwable $e) {
        }
        return $loc;
    }

    private static function httpGet(string $url, int $timeout): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_USERAGENT => 'SPARKTECH-CMS/1.0']);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code === 200 && is_string($body) ? $body : null;
        }
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "Accept: application/json\r\n"]]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }

    public static function isPublicIp(string $ip): bool
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** Shortened IP stored instead of the full address: 203.0.113.0 / 2001:db8:85a3:: */
    public static function maskIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bin = inet_pton($ip);
            return $bin === false ? '' : (string)inet_ntop(substr($bin, 0, 6) . str_repeat("\0", 10));
        }
        return '';
    }

    public static function countryFromTimezone(string $tz): string
    {
        // Old names some browsers still report (no location data in PHP).
        $aliases = ['Asia/Calcutta' => 'Asia/Kolkata', 'Asia/Katmandu' => 'Asia/Kathmandu', 'Asia/Dacca' => 'Asia/Dhaka',
            'Asia/Saigon' => 'Asia/Ho_Chi_Minh', 'Asia/Rangoon' => 'Asia/Yangon', 'Asia/Thimbu' => 'Asia/Thimphu',
            'Asia/Ujung_Pandang' => 'Asia/Makassar', 'Asia/Ulan_Bator' => 'Asia/Ulaanbaatar', 'Asia/Chongqing' => 'Asia/Shanghai',
            'Asia/Istanbul' => 'Europe/Istanbul', 'Europe/Kiev' => 'Europe/Kyiv', 'America/Buenos_Aires' => 'America/Argentina/Buenos_Aires',
            'Africa/Asmera' => 'Africa/Asmara', 'Pacific/Truk' => 'Pacific/Chuuk', 'US/Eastern' => 'America/New_York',
            'US/Central' => 'America/Chicago', 'US/Mountain' => 'America/Denver', 'US/Pacific' => 'America/Los_Angeles'];
        $tz = $aliases[$tz] ?? $tz;
        try {
            $loc = (new DateTimeZone($tz))->getLocation();
            $cc = strtoupper((string)($loc['country_code'] ?? ''));
            return preg_match('/^[A-Z]{2}$/', $cc) && $cc !== '??' ? $cc : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public static function countryName(string $code): string
    {
        static $names = null;
        if ($code === '') {
            return 'Unknown';
        }
        if (class_exists('Locale')) {
            $name = Locale::getDisplayRegion('-' . $code, 'en');
            if ($name !== '' && $name !== $code) {
                return $name;
            }
        }
        if ($names === null) {
            $names = require ST_APP . '/lib/countries.php';
        }
        return $names[$code] ?? $code;
    }

    /** Small country-code badge (flag emoji are not drawn on Windows). */
    public static function countryBadge(string $code): string
    {
        $code = preg_match('/^[A-Z]{2}$/', $code) ? $code : '··';
        return '<span class="an-cc" title="' . e(self::countryName($code === '··' ? '' : $code)) . '">' . $code . '</span>';
    }

    /** "Mumbai, Maharashtra, India" */
    public static function placeLabel(string $city, string $region, string $country): string
    {
        $parts = array_filter([$city, $region !== $city ? $region : '', $country !== '' ? self::countryName($country) : '']);
        return implode(', ', array_unique($parts));
    }

    // ---- Helpers ------------------------------------------------------------------

    /** Site path only (no query string), max 255 chars. */
    public static function path(string $path): string
    {
        $path = (string)parse_url($path, PHP_URL_PATH);
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/');
        return mb_substr(preg_replace('/[^\p{L}\p{N}\/._~%-]/u', '', $path), 0, 255);
    }

    private static function text($value, int $max): string
    {
        $value = is_scalar($value) ? (string)$value : '';
        $value = trim((string)preg_replace('/\s+/u', ' ', strip_tags($value)));
        return mb_substr($value, 0, $max);
    }
}
