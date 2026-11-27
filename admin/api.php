<?php
/**
 * JSON API used by the live editor (assets/js/editor.js).
 * All calls: POST, logged-in admin, X-CSRF-Token header.
 */
require __DIR__ . '/_init.php';

if (!is_admin()) {
    json_response(['ok' => false, 'error' => 'Your login expired. Log in again in another tab, then retry.'], 401);
}
if (!is_post() || !csrf_valid()) {
    json_response(['ok' => false, 'error' => 'Security check failed. Reload the page and try again.'], 419);
}
$user = current_user();
$uid = (int)$user['id'];
$action = (string)($_GET['action'] ?? '');

$body = [];
if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false) {
    $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
}

$docName = function () use ($body): string {
    $doc = (string)($body['doc'] ?? '');
    $known = isset(Content::BUILTIN[$doc]) || preg_match('/^page:[a-z0-9-]{1,60}$/', $doc);
    if (!$known) {
        json_response(['ok' => false, 'error' => 'Unknown content: ' . $doc], 422);
    }
    return $doc;
};
$validPath = function (string $path): bool {
    return (bool)preg_match('/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)*$/', $path);
};
$done = function (array $extra = []) {
    json_response(['ok' => true, 'pending' => count(Content::pending())] + $extra);
};

try {
    switch ($action) {
        case 'save': {
            $doc = $docName();
            $fields = $body['fields'] ?? [];
            if (!is_array($fields) || !$fields) {
                json_response(['ok' => false, 'error' => 'Nothing to save.'], 422);
            }
            $data = Content::draftForEdit($doc);
            $saved = [];
            foreach ($fields as $f) {
                $path = (string)($f['p'] ?? '');
                $type = (string)($f['t'] ?? 'text');
                if (!$validPath($path)) {
                    json_response(['ok' => false, 'error' => 'Invalid field.'], 422);
                }
                $value = clean_value($f['v'] ?? '', $type);
                if ($type === 'select' || $type === 'icon') {
                    $value = mb_substr((string)$value, 0, 120);
                }
                Content::setPath($data, $path, $value);
                $saved[$path] = $value;
            }
            Content::saveDraft($doc, $data, $uid);
            $done(['values' => $saved]);
        }

        case 'list': {
            $doc = $docName();
            $path = (string)($body['path'] ?? '');
            $op = (string)($body['op'] ?? '');
            $i = (int)($body['index'] ?? -1);
            if (!$validPath($path)) {
                json_response(['ok' => false, 'error' => 'Invalid list.'], 422);
            }
            $data = Content::draftForEdit($doc);
            $list = Content::getPath($data, $path, []);
            if (!is_array($list)) {
                $list = [];
            }
            $list = array_values($list);
            $n = count($list);
            if (!in_array($op, ['add', 'add_block', 'add_photos', 'add_youtube'], true) && ($i < 0 || $i >= $n)) {
                json_response(['ok' => false, 'error' => 'That item no longer exists. Reload the page.'], 409);
            }
            switch ($op) {
                case 'up':
                    if ($i > 0) {
                        [$list[$i - 1], $list[$i]] = [$list[$i], $list[$i - 1]];
                    }
                    break;
                case 'down':
                    if ($i < $n - 1) {
                        [$list[$i + 1], $list[$i]] = [$list[$i], $list[$i + 1]];
                    }
                    break;
                case 'dup':
                    $copy = $list[$i];
                    if (is_array($copy) && isset($copy['slug'])) {
                        $copy['slug'] = $copy['slug'] . '-copy-' . substr(random_token(2), 0, 3);
                    }
                    if (is_array($copy) && isset($copy['uid'])) {
                        $copy['uid'] = substr(random_token(4), 0, 8);
                        $copy['anchor'] = '';
                    }
                    array_splice($list, $i + 1, 0, [$copy]);
                    break;
                case 'del':
                    array_splice($list, $i, 1);
                    break;
                case 'add':
                    $template = $n ? $list[$n - 1] : (Content::getPath(Content::defaults($doc), $path, [])[0] ?? '');
                    if (is_array($template) && isset($template['slug'])) {
                        $template['slug'] = $template['slug'] . '-new-' . substr(random_token(2), 0, 3);
                    }
                    $list[] = $template;
                    break;
                case 'add_block':
                    $block = Content::blockTemplate((string)($body['type'] ?? ''));
                    if (!$block) {
                        json_response(['ok' => false, 'error' => 'Unknown section type.'], 422);
                    }
                    $list[] = $block;
                    break;
                case 'add_photos':
                    // One new item per uploaded photo, copied from the last item.
                    $field = (string)($body['field'] ?? 'image');
                    $photos = array_slice((array)($body['photos'] ?? []), 0, 60);
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $field) || !$photos) {
                        json_response(['ok' => false, 'error' => 'No photos.'], 422);
                    }
                    $template = $n ? $list[$n - 1] : (Content::getPath(Content::defaults($doc), $path, [])[0] ?? []);
                    // Optional group (e.g. gallery category): copy the last item of that
                    // group so location / year match, and stamp the group on every photo.
                    $groupField = (string)($body['group']['field'] ?? '');
                    $groupValue = trim(clean_text((string)($body['group']['value'] ?? ''), 80));
                    if ($groupField !== '' && !preg_match('/^[A-Za-z0-9_]+$/', $groupField)) {
                        json_response(['ok' => false, 'error' => 'Invalid group.'], 422);
                    }
                    if ($groupField !== '' && $groupValue !== '') {
                        foreach (array_reverse($list) as $it) {
                            if (is_array($it) && ($it[$groupField] ?? null) === $groupValue) {
                                $template = $it;
                                break;
                            }
                        }
                    }
                    if (!is_array($template)) {
                        $template = [];
                    }
                    foreach ($photos as $ph) {
                        $item = $template;
                        if ($groupField !== '' && $groupValue !== '') {
                            $item[$groupField] = $groupValue;
                        }
                        $name = trim(clean_text($ph['name'] ?? '', 120));
                        $item[$field] = ['src' => clean_url($ph['url'] ?? ''), 'alt' => $name];
                        foreach (['title', 'caption'] as $k) {
                            if (array_key_exists($k, $item)) {
                                $item[$k] = $name;
                            }
                        }
                        if (isset($item['uid'])) {
                            $item['uid'] = substr(random_token(4), 0, 8);
                        }
                        if ($item[$field]['src'] !== '') {
                            $list[] = $item;
                        }
                    }
                    break;
                case 'add_youtube':
                    // One new item for a YouTube link, copied from the last item (of its
                    // group, when given) so location / year match. The photo is cleared so
                    // the video's own thumbnail shows until one is chosen.
                    $field = (string)($body['field'] ?? 'youtube');
                    $photoField = (string)($body['photo'] ?? '');
                    $groupField = (string)($body['group']['field'] ?? '');
                    $groupValue = trim(clean_text((string)($body['group']['value'] ?? ''), 80));
                    foreach ([$field, $photoField, $groupField] as $k) {
                        if ($k !== '' && !preg_match('/^[A-Za-z0-9_]+$/', $k)) {
                            json_response(['ok' => false, 'error' => 'Invalid field.'], 422);
                        }
                    }
                    $id = youtube_id((string)($body['url'] ?? ''));
                    if ($id === '') {
                        json_response(['ok' => false, 'error' => 'That is not a YouTube video link.'], 422);
                    }
                    $template = $n ? $list[$n - 1] : (Content::getPath(Content::defaults($doc), $path, [])[0] ?? []);
                    if ($groupField !== '' && $groupValue !== '') {
                        foreach (array_reverse($list) as $it) {
                            if (is_array($it) && ($it[$groupField] ?? null) === $groupValue) {
                                $template = $it;
                                break;
                            }
                        }
                    }
                    $item = is_array($template) ? $template : [];
                    if ($groupField !== '' && $groupValue !== '') {
                        $item[$groupField] = $groupValue;
                    }
                    $item[$field] = 'https://www.youtube.com/watch?v=' . $id;
                    if ($photoField !== '') {
                        $item[$photoField] = '';
                    }
                    $title = trim(clean_text((string)($body['title'] ?? ''), 200));
                    foreach (['title', 'caption'] as $k) {
                        if (array_key_exists($k, $item)) {
                            $item[$k] = $title !== '' ? $title : 'New video';
                        }
                    }
                    if (array_key_exists('description', $item)) {
                        $item['description'] = ''; // the copied text describes another item
                    }
                    if (isset($item['uid'])) {
                        $item['uid'] = substr(random_token(4), 0, 8);
                    }
                    $list[] = $item;
                    break;
                default:
                    json_response(['ok' => false, 'error' => 'Unknown list action.'], 422);
            }
            Content::setPath($data, $path, $list);
            Content::saveDraft($doc, $data, $uid);
            $done();
        }

        case 'section': {
            $doc = $docName();
            $key = (string)($body['key'] ?? '');
            if (!preg_match('/^[a-z0-9_-]{1,60}$/i', $key)) {
                json_response(['ok' => false, 'error' => 'Invalid section.'], 422);
            }
            $data = Content::draftForEdit($doc);
            Content::setPath($data, '_hidden.' . $key, (bool)($body['hidden'] ?? false));
            Content::saveDraft($doc, $data, $uid);
            $done();
        }

        case 'upload': {
            if (empty($_FILES['file'])) {
                $max = ini_get('post_max_size');
                json_response(['ok' => false, 'error' => "No file received. It may be larger than the server limit ($max)."], 422);
            }
            $row = Media::upload($_FILES['file'], $uid);
            $done(['file' => ['url' => $row['path'], 'name' => $row['original_name'], 'mime' => $row['mime'],
                'size' => $row['size'], 'w' => $row['width'], 'h' => $row['height'], 'kind' => 'upload',
                'original_size' => $row['original_size'], 'converted' => $row['converted'], 'summary' => Media::summary($row)]]);
        }

        case 'media': {
            $done(['uploads' => Media::uploads(300), 'bundled' => Media::bundled()]);
        }

        case 'publish': {
            $n = Content::publish(Content::pending(), $uid, 'Published from live editor');
            $done(['published' => $n]);
        }

        case 'discard': {
            $n = Content::discard(Content::pending());
            $done(['discarded' => $n]);
        }

        default:
            json_response(['ok' => false, 'error' => 'Unknown action.'], 404);
    }
} catch (RuntimeException $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('CMS API error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    json_response(['ok' => false, 'error' => cfg('debug') ? $e->getMessage() : 'Something went wrong on the server.'], 500);
}
