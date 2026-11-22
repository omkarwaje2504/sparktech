<?php
/**
 * Admin → Analytics: visitors, genuine traffic, sources, pages, clicks, journeys,
 * locations and devices from the first-party tracker (app/lib/analytics.php).
 *   ?range=today|7d|30d|90d|12m|custom (&from=Y-m-d&to=Y-m-d)   &only=genuine
 *   ?vid=…   one visitor's visits and click-by-click journey
 *   ?export=visitors|visits   CSV for the selected range
 */
require __DIR__ . '/_init.php';
$user = require_login();

// ---- Delete one visitor's data (privacy requests) ----------------------------
if (is_post()) {
    csrf_require();
    $vid = Analytics::cleanId((string)($_POST['vid'] ?? ''));
    if (($_POST['action'] ?? '') === 'forget' && $vid !== '') {
        DB::exec('DELETE FROM {an_events} WHERE vid = ?', [$vid]);
        DB::exec('DELETE FROM {an_sessions} WHERE vid = ?', [$vid]);
        DB::exec('DELETE FROM {an_visitors} WHERE vid = ?', [$vid]);
        DB::exec("UPDATE {leads} SET visitor_id = '', session_id = '' WHERE visitor_id = ?", [$vid]);
        flash('All analytics data for that visitor was deleted.', 'info');
    }
    redirect('/admin/analytics.php');
}

// ---- Range & filters ------------------------------------------------------------
$presets = ['today' => 'Today', '7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'];
$range = (string)($_GET['range'] ?? '30d');
$today = date('Y-m-d');
$valid = function ($d) {
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
};
if ($range === 'custom' && $valid($_GET['from'] ?? null) && $valid($_GET['to'] ?? null)) {
    $fromDay = min($_GET['from'], $_GET['to']);
    $toDay = min(max($_GET['from'], $_GET['to']), $today);
} else {
    $range = isset($presets[$range]) ? $range : '30d';
    $days = ['today' => 0, '7d' => 6, '30d' => 29, '90d' => 89, '12m' => 364][$range];
    $fromDay = date('Y-m-d', strtotime("-$days days"));
    $toDay = $today;
}
$from = $fromDay . ' 00:00:00';
$to = $toDay . ' 23:59:59';
$spanDays = (int)round((strtotime($toDay) - strtotime($fromDay)) / 86400) + 1;
$prevTo = date('Y-m-d 23:59:59', strtotime($fromDay . ' -1 day'));
$prevFrom = date('Y-m-d 00:00:00', strtotime($fromDay . " -$spanDays days"));
$genuineOnly = ($_GET['only'] ?? '') === 'genuine';
$qs = function (array $over = []) use ($range, $fromDay, $toDay, $genuineOnly) {
    $q = ['range' => $range];
    if ($range === 'custom') {
        $q += ['from' => $fromDay, 'to' => $toDay];
    }
    if ($genuineOnly) {
        $q['only'] = 'genuine';
    }
    $q = array_filter(array_merge($q, $over), function ($v) {
        return $v !== null && $v !== '';
    });
    return '?' . http_build_query($q);
};

// Session filter used by every report: real people in the range (optionally only genuine ones).
$where = 's.is_bot = 0 AND s.started_at >= ? AND s.started_at <= ?' . ($genuineOnly ? ' AND s.engaged = 1' : '');
$args = [$from, $to];

// ---- Helpers ------------------------------------------------------------------------
function an_n($n): string
{
    $n = (float)$n;
    if ($n >= 100000) return number_format($n / 1000, 0) . 'K';
    if ($n >= 10000) return number_format($n / 1000, 1) . 'K';
    return number_format($n);
}
function an_dur($secs): string
{
    $secs = (int)round((float)$secs);
    if ($secs < 60) return $secs . 's';
    if ($secs < 3600) return floor($secs / 60) . 'm ' . str_pad((string)($secs % 60), 2, '0', STR_PAD_LEFT) . 's';
    return floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'm';
}
function an_pct($part, $whole, int $dec = 0): string
{
    return $whole > 0 ? number_format($part / $whole * 100, $dec) . '%' : '—';
}
/** Signed change vs the previous period, with an arrow + words (never colour alone). */
function an_delta($now, $before, bool $upIsGood = true): string
{
    if ($before <= 0) {
        return $now > 0 ? '<span class="an-delta">▲ up from 0 in the previous period</span>' : '';
    }
    $ch = ($now - $before) / $before * 100;
    if (abs($ch) < 0.5) {
        return '<span class="an-delta">— same as before</span>';
    }
    $good = ($ch > 0) === $upIsGood;
    return '<span class="an-delta ' . ($good ? 'is-good' : 'is-bad') . '">' . ($ch > 0 ? '▲' : '▼') . ' '
        . number_format(abs($ch), 0) . '% vs previous</span>';
}
function an_bar($value, $max): string
{
    $w = $max > 0 ? max(1, round($value / $max * 100)) : 0;
    return '<span class="an-bar" aria-hidden="true"><i style="width:' . $w . '%"></i></span>';
}
function an_short_url(string $url): string
{
    $u = preg_replace('~^https?://(www\.)?~i', '', $url);
    $own = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($own !== '' && str_starts_with($u, $own)) {
        $u = substr($u, strlen($own)) ?: '/';
    }
    return mb_strimwidth($u, 0, 60, '…');
}
function an_kind_label(string $kind): string
{
    $k = ['call' => 'Phone call', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'external' => 'Other website', 'download' => 'Download',
        'popup' => 'Popup', 'link' => 'Page link', 'button' => 'Button', 'submit' => 'Form button', 'filter' => 'Filter', 'menu' => 'Menu', 'form' => 'Enquiry'];
    return $k[$kind] ?? ucfirst($kind);
}
/** Summary numbers for a period. */
function an_summary(string $from, string $to, bool $genuineOnly): array
{
    $g = $genuineOnly ? ' AND engaged = 1' : '';
    $r = DB::row("SELECT COUNT(*) AS visits, COUNT(DISTINCT vid) AS visitors,
            COUNT(DISTINCT CASE WHEN is_new = 1 THEN vid END) AS new_visitors,
            COUNT(DISTINCT CASE WHEN engaged = 1 THEN vid END) AS genuine,
            COALESCE(SUM(pageviews), 0) AS pageviews, COALESCE(AVG(duration), 0) AS avg_duration,
            SUM(CASE WHEN pageviews <= 1 AND clicks = 0 THEN 1 ELSE 0 END) AS bounces,
            SUM(CASE WHEN lead_id IS NOT NULL THEN 1 ELSE 0 END) AS converted
        FROM {an_sessions} WHERE is_bot = 0 AND started_at >= ? AND started_at <= ?$g", [$from, $to]) ?: [];
    $r['bots'] = (int)DB::value('SELECT COUNT(*) FROM {an_sessions} WHERE is_bot = 1 AND started_at >= ? AND started_at <= ?', [$from, $to]);
    $r['leads'] = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE spam = 0 AND created_at >= ? AND created_at <= ?', [$from, $to]);
    $r['spam'] = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE spam = 1 AND created_at >= ? AND created_at <= ?', [$from, $to]);
    $r['clicks'] = DB::all("SELECT e.kind, COUNT(*) AS c FROM {an_events} e JOIN {an_sessions} s ON s.sid = e.sid
        WHERE e.type = 'click' AND s.is_bot = 0 AND e.created_at >= ? AND e.created_at <= ?" . ($genuineOnly ? ' AND s.engaged = 1' : '') . '
        GROUP BY e.kind', [$from, $to]);
    $r['contact_clicks'] = 0;
    foreach ($r['clicks'] as $c) {
        if (in_array($c['kind'], ['call', 'email', 'whatsapp'], true)) {
            $r['contact_clicks'] += (int)$c['c'];
            $r['k_' . $c['kind']] = (int)$c['c'];
        }
    }
    foreach (['visits', 'visitors', 'new_visitors', 'genuine', 'pageviews', 'bounces', 'converted'] as $k) {
        $r[$k] = (int)($r[$k] ?? 0);
    }
    $r['avg_duration'] = (float)($r['avg_duration'] ?? 0);
    return $r;
}

/**
 * Columns chart (inline SVG). $buckets [key => label]; $series [[name, css colour var, [key => value]]] stacked bottom-up.
 * Hover / focus a column for the values; the same numbers are in the table view below it.
 */
function an_columns(array $buckets, array $series, string $title, string $unit): string
{
    $W = 760; $H = 220; $L = 40; $R = 8; $T = 12; $B = 26;
    $keys = array_keys($buckets);
    $n = max(1, count($keys));
    $totals = [];
    foreach ($keys as $k) {
        $totals[$k] = 0;
        foreach ($series as $s) {
            $totals[$k] += (int)($s[2][$k] ?? 0);
        }
    }
    // Whole-number ticks: step = 1, 2 or 5 × 10^k, at most 5 gridlines.
    $max = max(1, max($totals ?: [0]));
    $raw = max(1, $max / 4);
    $mag = pow(10, floor(log10($raw)));
    $step = $mag;
    foreach ([1, 2, 5, 10] as $m) {
        if ($m * $mag >= $raw) { $step = $m * $mag; break; }
    }
    $ticks = (int)ceil($max / $step);
    $niceMax = $ticks * $step;
    $plotW = $W - $L - $R;
    $plotH = $H - $T - $B;
    $slot = $plotW / $n;
    $barW = min(24, max(3, $slot * 0.62));
    $y = function ($v) use ($T, $plotH, $niceMax) {
        return $T + $plotH - ($v / $niceMax) * $plotH;
    };
    $svg = '<svg class="an-chart" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . e($title) . '">';
    for ($i = 0; $i <= $ticks; $i++) {
        $v = $step * $i;
        $yy = round($y($v), 1);
        $svg .= '<line x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . $yy . '" y2="' . $yy . '" class="an-grid"/>';
        $svg .= '<text x="' . ($L - 6) . '" y="' . ($yy + 4) . '" class="an-tick" text-anchor="end">' . e(an_n($v)) . '</text>';
    }
    $every = max(1, (int)ceil($n / 12));
    foreach ($keys as $i => $k) {
        $cx = $L + $slot * $i + $slot / 2;
        $x = round($cx - $barW / 2, 1);
        $base = 0;
        $rows = [];
        $segs = '';
        $top = null;
        foreach ($series as $si => $s) {
            if ((int)($s[2][$k] ?? 0) > 0) {
                $top = $si;
            }
        }
        foreach ($series as $si => $s) {
            $v = (int)($s[2][$k] ?? 0);
            $rows[] = [$s[0], number_format($v), $s[1]];
            if ($v <= 0) {
                continue;
            }
            $y0 = $y($base);
            $y1 = $y($base + $v);
            $h = $y0 - $y1 - ($base > 0 ? 2 : 0); // 2px surface gap between stacked segments
            $base += $v;
            if ($h <= 0.3) {
                continue;
            }
            $yy = $y1;
            if ($si === $top) {
                $r = min(4, $h, $barW / 2);
                $segs .= '<path fill="' . $s[1] . '" d="M' . $x . ',' . round($yy + $h, 1) . 'V' . round($yy + $r, 1)
                    . 'Q' . $x . ',' . round($yy, 1) . ' ' . round($x + $r, 1) . ',' . round($yy, 1)
                    . 'H' . round($x + $barW - $r, 1) . 'Q' . round($x + $barW, 1) . ',' . round($yy, 1) . ' ' . round($x + $barW, 1) . ',' . round($yy + $r, 1)
                    . 'V' . round($yy + $h, 1) . 'Z"/>';
            } else {
                $segs .= '<rect fill="' . $s[1] . '" x="' . $x . '" y="' . round($yy, 1) . '" width="' . round($barW, 1) . '" height="' . round($h, 1) . '"/>';
            }
        }
        if (count($series) > 1) {
            $rows[] = ['Total', number_format($totals[$k]), ''];
        }
        $tip = json_encode(['t' => $buckets[$k], 'r' => $rows], JSON_UNESCAPED_UNICODE);
        $svg .= '<g class="an-col" tabindex="0" data-an-tip="' . e($tip) . '" aria-label="' . e($buckets[$k] . ': ' . number_format($totals[$k]) . ' ' . $unit) . '">'
            . '<rect class="an-hit" x="' . round($L + $slot * $i, 1) . '" y="' . $T . '" width="' . round($slot, 1) . '" height="' . $plotH . '"/>' . $segs . '</g>';
        if ($i % $every === 0) {
            $svg .= '<text x="' . round($cx, 1) . '" y="' . ($H - 8) . '" class="an-tick" text-anchor="middle">' . e($buckets[$k]) . '</text>';
        }
    }
    $svg .= '<line x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . ($T + $plotH) . '" y2="' . ($T + $plotH) . '" class="an-axis"/></svg>';

    $legend = '';
    if (count($series) > 1) {
        $legend = '<div class="an-legend">';
        foreach ($series as $s) {
            $legend .= '<span><i style="background:' . $s[1] . '"></i>' . e($s[0]) . '</span>';
        }
        $legend .= '</div>';
    }
    $table = '<details class="an-tableview"><summary>Show as table</summary><table class="adm-table"><thead><tr><th></th>';
    foreach ($series as $s) {
        $table .= '<th class="adm-right">' . e($s[0]) . '</th>';
    }
    $table .= '</tr></thead>';
    foreach (array_reverse($keys) as $k) {
        if ($totals[$k] === 0) continue;
        $table .= '<tr><td>' . e($buckets[$k]) . '</td>';
        foreach ($series as $s) {
            $table .= '<td class="adm-right">' . number_format((int)($s[2][$k] ?? 0)) . '</td>';
        }
        $table .= '</tr>';
    }
    $table .= '</table></details>';
    return $legend . '<div class="an-chart-wrap">' . $svg . '</div>' . $table;
}

// ---- CSV export ---------------------------------------------------------------------
if (in_array($_GET['export'] ?? '', ['visitors', 'visits'], true)) {
    $kind = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $kind . '-' . $fromDay . '-to-' . $toDay . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($kind === 'visitors') {
        fputcsv($out, ['Visitor ID', 'First seen', 'Last seen', 'Total visits', 'Total pages', 'Genuine', 'Enquiry ID', 'First source', 'First page', 'Device', 'OS', 'Browser', 'City', 'Region', 'Country']);
        $rows = DB::all("SELECT v.* FROM {an_visitors} v WHERE v.vid IN (SELECT s.vid FROM {an_sessions} s WHERE $where) ORDER BY v.last_seen DESC", $args);
        foreach ($rows as $v) {
            fputcsv($out, [$v['vid'], $v['first_seen'], $v['last_seen'], $v['sessions'], $v['pageviews'], $v['engaged'] ? 'yes' : 'no', $v['lead_id'],
                Analytics::sourceLabel($v['first_source'], $v['first_medium']), $v['first_landing'], $v['device'], $v['os'], $v['browser'],
                $v['city'], $v['region'], Analytics::countryName($v['country'])]);
        }
    } else {
        fputcsv($out, ['Started', 'Visitor ID', 'Visit no.', 'New visitor', 'Genuine', 'Pages', 'Clicks', 'Duration (s)', 'Source', 'Medium', 'Campaign', 'Landing page', 'Exit page', 'Device', 'Browser', 'City', 'Country', 'Enquiry ID']);
        foreach (DB::all("SELECT s.* FROM {an_sessions} s WHERE $where ORDER BY s.started_at DESC", $args) as $s) {
            fputcsv($out, [$s['started_at'], $s['vid'], $s['visit_no'], $s['is_new'] ? 'yes' : 'no', $s['engaged'] ? 'yes' : 'no', $s['pageviews'], $s['clicks'],
                $s['duration'], $s['source'], $s['medium'], $s['campaign'], $s['landing'], $s['exit_path'], $s['device'], $s['browser'], $s['city'],
                Analytics::countryName($s['country']), $s['lead_id']]);
        }
    }
    fclose($out);
    exit;
}

// ---- One visitor: profile + every visit, step by step -------------------------------
$vidParam = Analytics::cleanId((string)($_GET['vid'] ?? ''));
if ($vidParam !== '') {
    $v = DB::row('SELECT * FROM {an_visitors} WHERE vid = ?', [$vidParam]);
    $visits = DB::all('SELECT * FROM {an_sessions} WHERE vid = ? ORDER BY started_at DESC LIMIT 50', [$vidParam]);
    if (!$v && !$visits) {
        flash('That visitor has no data (it may have been deleted).', 'info');
        redirect('/admin/analytics.php');
    }
    $events = [];
    foreach (DB::all('SELECT * FROM {an_events} WHERE vid = ? ORDER BY id', [$vidParam]) as $ev) {
        $events[$ev['sid']][] = $ev;
    }
    $leads = DB::all('SELECT id, name, service, created_at, spam FROM {leads} WHERE visitor_id = ? ORDER BY id DESC', [$vidParam]);
    $first = $v ?: end($visits);
    admin_header('Visitor journey', 'analytics');
    ?>
    <p><a href="<?= e(url('/admin/analytics.php' . $qs())) ?>">← Analytics</a></p>
    <div class="adm-card">
      <div class="adm-card-row">
        <div>
          <h3 class="an-visitor-title"><?= Analytics::countryBadge((string)$first['country']) ?> <?= e(Analytics::placeLabel((string)$first['city'], (string)$first['region'], (string)$first['country']) ?: 'Unknown location') ?></h3>
          <p class="adm-muted adm-small">Visitor ID (random hash kept in their browser): <code><?= e($vidParam) ?></code></p>
        </div>
        <div class="an-badges">
          <?php if ($v && (int)$v['engaged']): ?><span class="adm-pill adm-pill-green">✓ Genuine</span><?php else: ?><span class="adm-pill">Not verified</span><?php endif; ?>
          <?php if ($v && (int)$v['sessions'] > 1): ?><span class="adm-pill an-pill-blue">↻ Returning · <?= (int)$v['sessions'] ?> visits</span><?php else: ?><span class="adm-pill an-pill-blue">New visitor</span><?php endif; ?>
          <?php foreach ($leads as $l): ?><a class="adm-pill adm-pill-amber" href="<?= e(url('/admin/leads.php?id=' . $l['id'])) ?>">✉ Enquiry #<?= (int)$l['id'] ?></a><?php endforeach; ?>
        </div>
      </div>
      <?php if ($v): ?>
      <dl class="adm-dl an-dl">
        <dt>First visit</dt><dd><?= e(date('d M Y, H:i', strtotime($v['first_seen']))) ?></dd>
        <dt>Last seen</dt><dd><?= e(date('d M Y, H:i', strtotime($v['last_seen']))) ?> (<?= e(time_ago($v['last_seen'])) ?>)</dd>
        <dt>Found the site via</dt><dd><?= e(Analytics::sourceLabel($v['first_source'], $v['first_medium'])) ?><?= $v['first_referrer'] !== '' ? ' — <span class="adm-muted">' . e(an_short_url($v['first_referrer'])) . '</span>' : '' ?></dd>
        <dt>First page</dt><dd><?= e($v['first_landing']) ?></dd>
        <dt>Totals</dt><dd><?= (int)$v['sessions'] ?> visits · <?= (int)$v['pageviews'] ?> pages · <?= (int)$v['clicks'] ?> clicks</dd>
        <dt>Device</dt><dd><?= e($v['device'] . ' · ' . $v['os'] . ' · ' . $v['browser']) ?></dd>
      </dl>
      <?php endif; ?>
      <form method="post" class="adm-actions">
        <?= csrf_field() ?><input type="hidden" name="vid" value="<?= e($vidParam) ?>">
        <button class="adm-btn adm-btn-danger adm-btn-sm" name="action" value="forget" data-confirm="Delete all analytics data for this visitor? Their enquiries stay.">Delete this visitor's data</button>
      </form>
    </div>

    <?php foreach ($visits as $s): $evs = $events[$s['sid']] ?? []; $t0 = strtotime($s['started_at']); ?>
    <div class="adm-card">
      <div class="adm-card-row an-visit-head">
        <div>
          <h3>Visit <?= (int)$s['visit_no'] ?> · <?= e(date('D d M Y, H:i', $t0)) ?></h3>
          <p class="adm-muted adm-small">
            <?= e(Analytics::sourceLabel($s['source'], $s['medium'])) ?><?= $s['campaign'] !== '' ? ' · campaign “' . e($s['campaign']) . '”' : '' ?>
            · <?= (int)$s['pageviews'] ?> page<?= (int)$s['pageviews'] === 1 ? '' : 's' ?> · <?= an_dur($s['duration']) ?> · <?= e($s['device'] . ', ' . $s['browser']) ?>
            <?= $s['city'] !== '' || $s['country'] !== '' ? ' · ' . Analytics::countryBadge((string)$s['country']) . ' ' . e(Analytics::placeLabel($s['city'], '', $s['country'])) : '' ?>
          </p>
        </div>
        <div class="an-badges">
          <?php if ((int)$s['is_bot']): ?><span class="adm-pill adm-pill-red">Bot · <?= e($s['bot_reason']) ?></span>
          <?php elseif ((int)$s['engaged']): ?><span class="adm-pill adm-pill-green">✓ Genuine</span>
          <?php else: ?><span class="adm-pill">No interaction</span><?php endif; ?>
        </div>
      </div>
      <?php if ($evs): ?>
      <ol class="an-tl">
        <?php foreach ($evs as $ev): $off = max(0, strtotime($ev['created_at']) - $t0); ?>
        <li class="an-tl-<?= e($ev['type']) ?>">
          <span class="an-tl-time"><?= $off < 60 ? $off . 's' : floor($off / 60) . 'm' ?></span>
          <?php if ($ev['type'] === 'view'): ?>
            <span class="an-tl-icon" aria-hidden="true">◉</span>
            <span><b><?= e($ev['title'] !== '' ? preg_replace('/\s*\|.*$/', '', $ev['title']) : $ev['path']) ?></b>
              <small class="adm-muted"><?= e($ev['path']) ?><?= (int)$ev['seconds'] > 0 ? ' · read ' . an_dur($ev['seconds']) : '' ?><?= (int)$ev['scroll'] > 0 ? ' · scrolled ' . (int)$ev['scroll'] . '%' : '' ?></small></span>
          <?php elseif ($ev['type'] === 'click'): ?>
            <span class="an-tl-icon" aria-hidden="true">↳</span>
            <span>Clicked <b>“<?= e($ev['label']) ?>”</b> <span class="adm-pill"><?= e(an_kind_label($ev['kind'])) ?></span>
              <?php if ($ev['target'] !== ''): ?><small class="adm-muted">→ <?= e(an_short_url($ev['target'])) ?></small><?php endif; ?></span>
          <?php else: ?>
            <span class="an-tl-icon" aria-hidden="true">✉</span>
            <span><b>Sent an enquiry</b> <small class="adm-muted"><?= e($ev['label']) ?></small></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php else: ?>
      <p class="adm-muted adm-small">No page-by-page details for this visit<?= (int)$s['is_bot'] ? ' (bots are not tracked in detail)' : '' ?>.</p>
      <?php endif; ?>
    </div>
    <?php endforeach;
    admin_footer();
    exit;
}

// ---- Overview data -------------------------------------------------------------------
$sum = an_summary($from, $to, $genuineOnly);
$prev = an_summary($prevFrom, $prevTo, $genuineOnly);
$allHuman = $genuineOnly ? an_summary($from, $to, false) : $sum;
$live = (int)DB::value('SELECT COUNT(DISTINCT vid) FROM {an_sessions} WHERE is_bot = 0 AND last_at >= ?', [date('Y-m-d H:i:s', time() - 300)]);
$returning = max(0, $sum['visitors'] - $sum['new_visitors']);
$prevReturning = max(0, $prev['visitors'] - $prev['new_visitors']);
// People who visited more than once inside the period (the visitor hash recognised them again).
$cameBack = (int)DB::value('SELECT COUNT(*) FROM (SELECT s.vid FROM {an_sessions} s WHERE ' . $where . ' GROUP BY s.vid HAVING COUNT(*) > 1) t', $args);

// Time buckets: hours (one day), days (≤ 92 days), months.
if ($spanDays === 1) {
    $bucketExpr = 'SUBSTR(s.started_at, 12, 2)';
    $buckets = [];
    for ($h = 0; $h < 24; $h++) {
        $buckets[str_pad((string)$h, 2, '0', STR_PAD_LEFT)] = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
    }
} elseif ($spanDays <= 92) {
    $bucketExpr = 'SUBSTR(s.started_at, 1, 10)';
    $buckets = [];
    for ($t = strtotime($fromDay); $t <= strtotime($toDay); $t += 86400) {
        $buckets[date('Y-m-d', $t)] = date('d M', $t);
    }
} else {
    $bucketExpr = 'SUBSTR(s.started_at, 1, 7)';
    $buckets = [];
    for ($t = strtotime(substr($fromDay, 0, 7) . '-01'); $t <= strtotime($toDay); $t = strtotime('+1 month', $t)) {
        $buckets[date('Y-m', $t)] = date('M Y', $t);
    }
}
$newSeries = $retSeries = [];
foreach (DB::all("SELECT $bucketExpr AS b, COUNT(DISTINCT CASE WHEN s.is_new = 1 THEN s.vid END) AS nv,
        COUNT(DISTINCT s.vid) AS v FROM {an_sessions} s WHERE $where GROUP BY $bucketExpr", $args) as $r) {
    $newSeries[$r['b']] = (int)$r['nv'];
    $retSeries[$r['b']] = max(0, (int)$r['v'] - (int)$r['nv']);
}

$ev = "FROM {an_events} e JOIN {an_sessions} s ON s.sid = e.sid WHERE $where";
$pages = DB::all("SELECT e.path, MAX(e.title) AS title, COUNT(*) AS views, COUNT(DISTINCT e.vid) AS visitors,
        AVG(CASE WHEN e.seconds > 0 THEN e.seconds END) AS secs, AVG(CASE WHEN e.scroll > 0 THEN e.scroll END) AS scroll
    $ev AND e.type = 'view' GROUP BY e.path ORDER BY views DESC LIMIT 15", $args);
$sources = DB::all("SELECT s.source, s.medium, COUNT(DISTINCT s.vid) AS visitors, COUNT(*) AS visits,
        SUM(CASE WHEN s.engaged = 1 THEN 1 ELSE 0 END) AS genuine, SUM(CASE WHEN s.lead_id IS NOT NULL THEN 1 ELSE 0 END) AS leads,
        AVG(s.duration) AS secs
    FROM {an_sessions} s WHERE $where GROUP BY s.source, s.medium ORDER BY visitors DESC LIMIT 12", $args);
$campaigns = DB::all("SELECT s.campaign, s.source, s.medium, COUNT(DISTINCT s.vid) AS visitors,
        SUM(CASE WHEN s.lead_id IS NOT NULL THEN 1 ELSE 0 END) AS leads
    FROM {an_sessions} s WHERE $where AND s.campaign <> '' GROUP BY s.campaign, s.source, s.medium ORDER BY visitors DESC LIMIT 10", $args);
$clicks = DB::all("SELECT e.label, e.target, e.kind, COUNT(*) AS c, COUNT(DISTINCT e.vid) AS visitors
    $ev AND e.type = 'click' GROUP BY e.label, e.target, e.kind ORDER BY c DESC LIMIT 15", $args);
$entries = DB::all("SELECT s.landing AS path, COUNT(*) AS visits, SUM(CASE WHEN s.pageviews <= 1 AND s.clicks = 0 THEN 1 ELSE 0 END) AS bounces
    FROM {an_sessions} s WHERE $where GROUP BY s.landing ORDER BY visits DESC LIMIT 8", $args);
$exits = DB::all("SELECT s.exit_path AS path, COUNT(*) AS visits FROM {an_sessions} s WHERE $where GROUP BY s.exit_path ORDER BY visits DESC LIMIT 8", $args);
$countries = DB::all("SELECT s.country, COUNT(DISTINCT s.vid) AS visitors FROM {an_sessions} s WHERE $where GROUP BY s.country ORDER BY visitors DESC LIMIT 10", $args);
$cities = DB::all("SELECT s.city, s.region, s.country, COUNT(DISTINCT s.vid) AS visitors FROM {an_sessions} s WHERE $where AND s.city <> ''
    GROUP BY s.city, s.region, s.country ORDER BY visitors DESC LIMIT 10", $args);
$tech = [];
foreach (['device' => 'Devices', 'browser' => 'Browsers', 'os' => 'Operating systems'] as $col => $label) {
    $tech[$label] = DB::all("SELECT s.$col AS name, COUNT(DISTINCT s.vid) AS visitors FROM {an_sessions} s WHERE $where GROUP BY s.$col ORDER BY visitors DESC LIMIT 6", $args);
}
$hours = array_fill_keys(array_map(function ($h) { return str_pad((string)$h, 2, '0', STR_PAD_LEFT); }, range(0, 23)), 0);
foreach (DB::all("SELECT SUBSTR(s.started_at, 12, 2) AS h, COUNT(*) AS c FROM {an_sessions} s WHERE $where GROUP BY SUBSTR(s.started_at, 12, 2)", $args) as $r) {
    $hours[$r['h']] = (int)$r['c'];
}

// Journeys: the most common "page → next page" steps and whole paths (first 4 pages of a visit).
$steps = [];
$paths = [];
$cur = null;
$trail = [];
$flush = function () use (&$trail, &$paths) {
    if (count($trail) > 1) {
        $key = implode(' → ', array_slice($trail, 0, 4)) . (count($trail) > 4 ? ' → …' : '');
        $paths[$key] = ($paths[$key] ?? 0) + 1;
    }
    $trail = [];
};
foreach (DB::all("SELECT e.sid, e.path $ev AND e.type = 'view' ORDER BY e.sid, e.id LIMIT 30000", $args) as $r) {
    if ($r['sid'] !== $cur) {
        $flush();
        $cur = $r['sid'];
    }
    $last = end($trail);
    if ($last !== false && $last !== $r['path']) {
        $k = $last . "\x1F" . $r['path'];
        $steps[$k] = ($steps[$k] ?? 0) + 1;
    }
    if ($last !== $r['path']) {
        $trail[] = $r['path'];
    }
}
$flush();
arsort($steps);
arsort($paths);
$steps = array_slice($steps, 0, 10, true);
$paths = array_slice($paths, 0, 8, true);

// Visitors list.
$who = (string)($_GET['who'] ?? 'all');
$whoSql = ['new' => ' AND v.sessions <= 1', 'returning' => ' AND v.sessions > 1', 'leads' => ' AND v.lead_id IS NOT NULL'][$who] ?? '';
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 25;
$visitorTotal = (int)DB::value("SELECT COUNT(DISTINCT s.vid) FROM {an_sessions} s JOIN {an_visitors} v ON v.vid = s.vid WHERE $where$whoSql", $args);
$visitorRows = DB::all("SELECT v.vid, v.first_seen, v.sessions, v.pageviews, v.engaged, v.lead_id, v.city, v.region, v.country,
        v.device, v.browser, v.os, v.first_source, v.first_medium, MAX(s.last_at) AS last_at, SUM(s.pageviews) AS pages_in_range
    FROM {an_sessions} s JOIN {an_visitors} v ON v.vid = s.vid WHERE $where$whoSql
    GROUP BY v.vid, v.first_seen, v.sessions, v.pageviews, v.engaged, v.lead_id, v.city, v.region, v.country, v.device, v.browser, v.os, v.first_source, v.first_medium
    ORDER BY last_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$visitorPages = max(1, (int)ceil($visitorTotal / $per));

$hasData = $sum['visits'] > 0 || $sum['bots'] > 0;
$rangeLabel = $range === 'custom' ? date('d M Y', strtotime($fromDay)) . ' – ' . date('d M Y', strtotime($toDay))
    : ($range === 'today' ? 'today' : 'the last ' . $presets[$range]);

admin_header('Analytics', 'analytics');
?>
<form method="get" class="an-filters" aria-label="Report filters">
  <div class="an-seg" role="group" aria-label="Date range">
    <?php foreach ($presets as $k => $label): ?>
    <a href="<?= e($qs(['range' => $k, 'from' => null, 'to' => null, 'p' => null])) ?>" class="<?= $range === $k ? 'is-active' : '' ?>"<?= $range === $k ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="an-custom">
    <input type="hidden" name="range" value="custom">
    <label class="adm-small">From <input type="date" name="from" value="<?= e($fromDay) ?>" max="<?= e($today) ?>"></label>
    <label class="adm-small">To <input type="date" name="to" value="<?= e($toDay) ?>" max="<?= e($today) ?>"></label>
    <?php if ($genuineOnly): ?><input type="hidden" name="only" value="genuine"><?php endif; ?>
    <button class="adm-btn adm-btn-sm">Apply</button>
  </div>
  <div class="an-seg" role="group" aria-label="Which visitors">
    <a href="<?= e($qs(['only' => null, 'p' => null])) ?>" class="<?= $genuineOnly ? '' : 'is-active' ?>">All people</a>
    <a href="<?= e($qs(['only' => 'genuine', 'p' => null])) ?>" class="<?= $genuineOnly ? 'is-active' : '' ?>">Genuine only</a>
  </div>
  <span class="an-live" title="Visitors active in the last 5 minutes"><i aria-hidden="true"></i><?= $live ?> on the site now</span>
</form>

<?php if (!$hasData): ?>
<div class="adm-card">
  <h3>No visits recorded for <?= e($rangeLabel) ?> yet</h3>
  <p class="adm-muted">Visits are counted from the moment this update went live. Your own visits are not counted while you are logged in to the admin
    <?= Analytics::enabled() ? '' : '— <b>and tracking is switched off in <a href="' . e(url('/admin/settings.php')) . '">Settings</a></b>' ?>.
    Open the website in a private window to see yourself appear here.</p>
</div>
<?php else: ?>

<div class="an-stats">
  <div class="an-stat an-stat-hero">
    <span>Visitors</span><b><?= an_n($sum['visitors']) ?></b><?= an_delta($sum['visitors'], $prev['visitors']) ?>
    <small><?= an_n($sum['new_visitors']) ?> new · <?= an_n($returning) ?> returning · <?= an_n($cameBack) ?> came back 2+ times</small>
  </div>
  <div class="an-stat"><span>New visitors</span><b><?= an_n($sum['new_visitors']) ?></b><?= an_delta($sum['new_visitors'], $prev['new_visitors']) ?>
    <small>first visit ever in this period</small></div>
  <div class="an-stat"><span>Returning visitors</span><b><?= an_n($returning) ?></b><?= an_delta($returning, $prevReturning) ?>
    <small>first came before this period</small></div>
  <div class="an-stat"><span>Came back in this period</span><b><?= an_n($cameBack) ?></b>
    <small><?= an_pct($cameBack, $sum['visitors']) ?> of visitors made 2+ visits</small></div>
  <div class="an-stat"><span>Genuine visitors</span><b><?= an_n($allHuman['genuine']) ?></b>
    <small><?= an_pct($allHuman['genuine'], $allHuman['visitors']) ?> of people interacted with the site</small></div>
  <div class="an-stat"><span>Visits</span><b><?= an_n($sum['visits']) ?></b><?= an_delta($sum['visits'], $prev['visits']) ?></div>
  <div class="an-stat"><span>Pages viewed</span><b><?= an_n($sum['pageviews']) ?></b>
    <small><?= $sum['visits'] ? number_format($sum['pageviews'] / $sum['visits'], 1) : '0' ?> pages per visit</small></div>
  <div class="an-stat"><span>Average visit</span><b><?= an_dur($sum['avg_duration']) ?></b><?= an_delta($sum['avg_duration'], $prev['avg_duration']) ?></div>
  <div class="an-stat"><span>Left after one page</span><b><?= an_pct($sum['bounces'], $sum['visits']) ?></b>
    <small>bounce rate · lower is better</small></div>
  <a class="an-stat" href="<?= e(url('/admin/leads.php')) ?>"><span>Enquiries</span><b><?= an_n($sum['leads']) ?></b><?= an_delta($sum['leads'], $prev['leads']) ?>
    <small><?= an_pct($sum['converted'], $sum['visits'], 1) ?> of visits sent one<?= $sum['spam'] ? ' · ' . $sum['spam'] . ' filtered as spam' : '' ?></small></a>
  <div class="an-stat"><span>Call / e-mail / WhatsApp taps</span><b><?= an_n($sum['contact_clicks']) ?></b>
    <small><?= (int)($sum['k_call'] ?? 0) ?> calls · <?= (int)($sum['k_email'] ?? 0) ?> e-mails · <?= (int)($sum['k_whatsapp'] ?? 0) ?> WhatsApp</small></div>
  <div class="an-stat"><span>Bots filtered out</span><b><?= an_n($sum['bots']) ?></b><small>crawlers & scripts, not counted above</small></div>
</div>

<div class="adm-card">
  <div class="adm-card-row"><h3><?= $spanDays === 1 ? 'Visitors by hour' : 'Visitors per ' . ($spanDays <= 92 ? 'day' : 'month') ?>: first visit or coming back</h3>
    <span class="adm-muted adm-small"><?= e(ucfirst($rangeLabel)) ?><?= $genuineOnly ? ' · genuine only' : '' ?></span></div>
  <?= an_columns($buckets, [['First visit', 'var(--an-s1)', $newSeries], ['Came back', 'var(--an-s2)', $retSeries]], 'Visitors over time: first visit or coming back', 'visitors') ?>
</div>

<div class="adm-grid2">
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Most viewed pages</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Page</th><th class="adm-right">Views</th><th class="adm-right">People</th><th class="adm-right" title="Average time the page was on screen">Read</th><th class="adm-right" title="Average scroll depth">Scroll</th></tr></thead>
      <?php $mx = $pages ? max(array_column($pages, 'views')) : 0; foreach ($pages as $p): ?>
      <tr>
        <td><span class="an-cell-title"><?= e(preg_replace('/\s*\|.*$/', '', (string)$p['title']) ?: $p['path']) ?></span><small class="adm-muted"><?= e($p['path']) ?></small><?= an_bar($p['views'], $mx) ?></td>
        <td class="adm-right"><?= an_n($p['views']) ?></td><td class="adm-right"><?= an_n($p['visitors']) ?></td>
        <td class="adm-right"><?= $p['secs'] !== null ? an_dur($p['secs']) : '—' ?></td><td class="adm-right"><?= $p['scroll'] !== null ? (int)$p['scroll'] . '%' : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Where visitors come from</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Source</th><th class="adm-right">People</th><th class="adm-right">Genuine</th><th class="adm-right">Enquiries</th></tr></thead>
      <?php $mx = $sources ? max(array_column($sources, 'visitors')) : 0; foreach ($sources as $s): ?>
      <tr>
        <td><span class="an-cell-title"><?= e(Analytics::sourceLabel($s['source'], $s['medium'])) ?></span><small class="adm-muted">avg. visit <?= an_dur($s['secs']) ?></small><?= an_bar($s['visitors'], $mx) ?></td>
        <td class="adm-right"><?= an_n($s['visitors']) ?></td><td class="adm-right"><?= an_pct($s['genuine'], $s['visits']) ?></td>
        <td class="adm-right"><?= (int)$s['leads'] ?: '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php if ($campaigns): ?>
    <h3 class="adm-pad an-card-title">Campaigns <small class="adm-muted">(links with utm_campaign)</small></h3>
    <table class="adm-table an-table">
      <thead><tr><th>Campaign</th><th class="adm-right">People</th><th class="adm-right">Enquiries</th></tr></thead>
      <?php foreach ($campaigns as $c): ?>
      <tr><td><span class="an-cell-title"><?= e($c['campaign']) ?></span><small class="adm-muted"><?= e($c['source'] . ' / ' . $c['medium']) ?></small></td>
        <td class="adm-right"><?= an_n($c['visitors']) ?></td><td class="adm-right"><?= (int)$c['leads'] ?: '—' ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="adm-grid2">
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Most clicked</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Link or button</th><th>Type</th><th class="adm-right">Clicks</th><th class="adm-right">People</th></tr></thead>
      <?php if (!$clicks): ?><tr><td colspan="4" class="adm-muted">No clicks recorded yet.</td></tr><?php endif; ?>
      <?php foreach ($clicks as $c): ?>
      <tr><td><span class="an-cell-title"><?= e($c['label']) ?></span><?php if ($c['target'] !== ''): ?><small class="adm-muted">→ <?= e(an_short_url($c['target'])) ?></small><?php endif; ?></td>
        <td><span class="adm-pill"><?= e(an_kind_label($c['kind'])) ?></span></td>
        <td class="adm-right"><?= an_n($c['c']) ?></td><td class="adm-right"><?= an_n($c['visitors']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Common journeys</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Visitors went from → to</th><th class="adm-right">Times</th></tr></thead>
      <?php if (!$steps): ?><tr><td colspan="2" class="adm-muted">Journeys appear once visitors open more than one page.</td></tr><?php endif; ?>
      <?php foreach ($steps as $k => $c): [$a, $b] = explode("\x1F", $k); ?>
      <tr><td><code><?= e($a) ?></code> → <code><?= e($b) ?></code></td><td class="adm-right"><?= an_n($c) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php if ($paths): ?>
    <h3 class="adm-pad an-card-title">Most common paths through the site</h3>
    <table class="adm-table an-table">
      <?php foreach ($paths as $k => $c): ?>
      <tr><td class="an-path"><?= e($k) ?></td><td class="adm-right"><?= an_n($c) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="adm-grid2">
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">First page of the visit</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Landing page</th><th class="adm-right">Visits</th><th class="adm-right">Left at once</th></tr></thead>
      <?php foreach ($entries as $r): ?>
      <tr><td><code><?= e($r['path']) ?></code></td><td class="adm-right"><?= an_n($r['visits']) ?></td><td class="adm-right"><?= an_pct($r['bounces'], $r['visits']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Last page before leaving</h3>
    <table class="adm-table an-table">
      <thead><tr><th>Exit page</th><th class="adm-right">Visits</th></tr></thead>
      <?php foreach ($exits as $r): ?>
      <tr><td><code><?= e($r['path']) ?></code></td><td class="adm-right"><?= an_n($r['visits']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="adm-grid2">
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Countries</h3>
    <table class="adm-table an-table">
      <?php $mx = $countries ? max(array_column($countries, 'visitors')) : 0; foreach ($countries as $c): ?>
      <tr><td><span class="an-cell-title"><?= Analytics::countryBadge((string)$c['country']) ?> <?= e(Analytics::countryName((string)$c['country'])) ?></span><?= an_bar($c['visitors'], $mx) ?></td>
        <td class="adm-right"><?= an_n($c['visitors']) ?></td><td class="adm-right adm-muted"><?= an_pct($c['visitors'], $sum['visitors']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title">Cities</h3>
    <table class="adm-table an-table">
      <?php if (!$cities): ?><tr><td class="adm-muted">City names appear once the location lookup is active (Settings → Visitor analytics).</td></tr><?php endif; ?>
      <?php $mx = $cities ? max(array_column($cities, 'visitors')) : 0; foreach ($cities as $c): ?>
      <tr><td><span class="an-cell-title"><?= Analytics::countryBadge((string)$c['country']) ?> <?= e($c['city']) ?></span><small class="adm-muted"><?= e(Analytics::placeLabel('', $c['region'], $c['country'])) ?></small><?= an_bar($c['visitors'], $mx) ?></td>
        <td class="adm-right"><?= an_n($c['visitors']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="an-grid3">
  <?php foreach ($tech as $label => $rows): $tot = array_sum(array_column($rows, 'visitors')); ?>
  <div class="adm-card adm-card-flush">
    <h3 class="adm-pad an-card-title"><?= e($label) ?></h3>
    <table class="adm-table an-table">
      <?php foreach ($rows as $r): ?>
      <tr><td><span class="an-cell-title"><?= e($r['name'] ?: 'Unknown') ?></span><?= an_bar($r['visitors'], $tot) ?></td><td class="adm-right"><?= an_pct($r['visitors'], $tot) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endforeach; ?>
</div>

<div class="adm-card">
  <div class="adm-card-row"><h3>Busiest hours</h3><span class="adm-muted adm-small">Visits started per hour of the day (<?= e(date_default_timezone_get()) ?> time)</span></div>
  <?php $hourLabels = []; foreach (array_keys($hours) as $h) { $hourLabels[$h] = $h . ':00'; } ?>
  <?= an_columns($hourLabels, [['Visits', 'var(--an-s1)', $hours]], 'Visits by hour of day', 'visits') ?>
</div>

<div class="adm-card adm-card-flush" id="visitors">
  <div class="adm-card-row adm-pad">
    <h3>Visitors <small class="adm-muted">(<?= an_n($visitorTotal) ?>)</small></h3>
    <div class="adm-inline">
      <div class="an-seg an-seg-sm" role="group" aria-label="Filter visitors">
        <?php foreach (['all' => 'All', 'new' => 'New', 'returning' => 'Returning', 'leads' => 'Sent an enquiry'] as $k => $label): ?>
        <a href="<?= e($qs(['who' => $k === 'all' ? null : $k, 'p' => null])) ?>#visitors" class="<?= $who === $k || ($k === 'all' && !in_array($who, ['new', 'returning', 'leads'], true)) ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
      <a class="adm-btn adm-btn-ghost adm-btn-sm" href="<?= e($qs(['export' => 'visitors'])) ?>">Visitors CSV</a>
      <a class="adm-btn adm-btn-ghost adm-btn-sm" href="<?= e($qs(['export' => 'visits'])) ?>">Visits CSV</a>
    </div>
  </div>
  <table class="adm-table adm-table-click an-table">
    <thead><tr><th>Location</th><th>Device</th><th>First came from</th><th class="adm-right">Visits</th><th class="adm-right">Pages</th><th>Status</th><th class="adm-right">Last seen</th></tr></thead>
    <?php if (!$visitorRows): ?><tr><td colspan="7" class="adm-muted">No visitors match this filter.</td></tr><?php endif; ?>
    <?php foreach ($visitorRows as $r): $href = url('/admin/analytics.php' . $qs(['vid' => $r['vid']])); ?>
    <tr data-href="<?= e($href) ?>">
      <td><a href="<?= e($href) ?>"><?= Analytics::countryBadge((string)$r['country']) ?> <?= e(Analytics::placeLabel((string)$r['city'], '', (string)$r['country']) ?: 'Unknown') ?></a></td>
      <td><small><?= e($r['device'] . ' · ' . $r['browser']) ?></small></td>
      <td><small><?= e(Analytics::sourceLabel($r['first_source'], $r['first_medium'])) ?></small></td>
      <td class="adm-right"><?= (int)$r['sessions'] ?></td>
      <td class="adm-right"><?= (int)$r['pageviews'] ?></td>
      <td>
        <?= (int)$r['sessions'] > 1 ? '<span class="adm-pill an-pill-blue">Returning</span>' : '<span class="adm-pill an-pill-blue">New</span>' ?>
        <?= (int)$r['engaged'] ? '<span class="adm-pill adm-pill-green">Genuine</span>' : '' ?>
        <?= $r['lead_id'] ? '<span class="adm-pill adm-pill-amber">Enquiry</span>' : '' ?>
      </td>
      <td class="adm-right"><small><?= e(time_ago($r['last_at'])) ?></small></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php if ($visitorPages > 1): ?>
<div class="adm-pager">
  <?php for ($i = 1; $i <= min($visitorPages, 40); $i++): ?>
  <a href="<?= e($qs(['p' => $i, 'who' => $who !== 'all' ? $who : null])) ?>#visitors" class="<?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<p class="adm-muted adm-small an-foot">
  Counted by this website itself — no Google cookies or third-party scripts. A <b>visitor</b> is one browser (a random ID kept in its storage),
  a <b>visit</b> ends after 30 minutes without activity, and <b>genuine</b> means the person scrolled, tapped, typed, clicked or stayed 10+ seconds.
  Logged-in admins and <a href="<?= e(url('/admin/settings.php#analytics')) ?>">excluded IP addresses</a> are never counted.
</p>
<?php admin_footer();
