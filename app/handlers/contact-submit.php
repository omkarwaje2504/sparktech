<?php
/**
 * POST /contact/submit — validates the enquiry (app/lib/validate.php), stores it
 * as a lead linked to the visitor's analytics journey, and e-mails a notification.
 * Suspicious enquiries are saved as "filtered" without a notification.
 * Responds with JSON for fetch() requests (errors per field), otherwise redirects back.
 */
$wantsJson = stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;

$respond = function (bool $ok, string $message = '', int $code = 200, array $errors = []) use ($wantsJson) {
    if ($wantsJson) {
        json_response(['ok' => $ok, 'message' => $message, 'errors' => (object)$errors], $code);
    }
    redirect('/contact/' . ($ok ? '?sent=1' : '?error=1') . '#enquiry');
};

if (!is_post()) {
    redirect('/contact/');
}

// Spam traps: hidden honeypot field + signed timestamp (too fast / forged = bot).
if (trim((string)($_POST['website'] ?? '')) !== '') {
    $respond(true); // pretend success for bots
}
$token = (string)($_POST['_ft'] ?? '');
if (!form_token_valid($token)) {
    $respond(false, 'Please wait a moment and submit the form again.', 422);
}
$formAge = time() - (int)explode('.', $token)[0];

$contact = Content::doc('contact');
$check = LeadCheck::validate($_POST, $contact->lines('form.services'));
if ($check['errors']) {
    $respond(false, 'Please correct the highlighted fields.', 422, $check['errors']);
}
$data = $check['data'] + ['page' => mb_substr(clean_text((string)($_POST['page'] ?? ''), 255), 0, 255)];
foreach (['name' => 190, 'email' => 190, 'phone' => 64, 'company' => 190, 'service' => 190, 'message' => 5000] as $k => $max) {
    $data[$k] = mb_substr($data[$k], 0, $max);
}

// Link to the visitor's analytics (random IDs kept in their browser by assets/js/track.js).
$vid = Analytics::cleanId((string)($_POST['_vid'] ?? ''));
$sid = Analytics::cleanId((string)($_POST['_sid'] ?? ''));
$context = ['form_age' => $formAge];
if (Analytics::enabled()) {
    $session = $sid !== '' ? Analytics::session($sid) : null;
    $context['visitor_seen'] = $session !== null;
    $context['engaged'] = $session ? ((int)$session['engaged'] === 1 || ($_POST['_h'] ?? '') === '1') : (($_POST['_h'] ?? '') === '1');
}
$spam = LeadCheck::spamSignals($data, $context);

// Rate limit: max 5 enquiries per IP per 10 minutes.
$ip = request_ip();
try {
    $recent = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE ip = ? AND created_at > ?', [$ip, date('Y-m-d H:i:s', time() - 600)]);
    if ($recent >= 5) {
        $respond(false, 'Too many messages from your connection. Please try again in a few minutes or call us.', 429);
    }
    $id = DB::insert('leads', $data + [
        'ip' => $ip,
        'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'is_read' => $spam['spam'] ? 1 : 0,
        'visitor_id' => $vid,
        'session_id' => $sid,
        'spam' => $spam['spam'] ? 1 : 0,
        'spam_reasons' => mb_substr(implode('; ', $spam['reasons']), 0, 255),
        'created_at' => now(),
    ]);
    if ($vid !== '' && !$spam['spam']) {
        Analytics::attachLead($id, $vid, $sid, (string)$data['page']);
    }
} catch (Throwable $e) {
    error_log('Lead save failed: ' . $e->getMessage());
    $respond(false, '', 500);
}

$to = trim((string)Settings::get('notify_email', ''));
if ($to !== '' && !$spam['spam']) {
    $visit = $sid !== '' ? Analytics::session($sid) : null;
    $lines = [
        'New enquiry from the website (#' . $id . ')',
        '',
        'Name:    ' . $data['name'],
        'E-mail:  ' . $data['email'],
        'Phone:   ' . $data['phone'],
        'Company: ' . $data['company'],
        'Service: ' . $data['service'],
        'Page:    ' . $data['page'],
    ];
    if ($visit) {
        $lines[] = 'Found us: ' . Analytics::sourceLabel($visit['source'], $visit['medium'])
            . ($visit['campaign'] !== '' ? ' (' . $visit['campaign'] . ')' : '');
        $place = Analytics::placeLabel($visit['city'], $visit['region'], $visit['country']);
        if ($place !== '') {
            $lines[] = 'Location: ' . $place;
        }
    }
    $lines = array_merge($lines, [
        '',
        'Message:',
        $data['message'],
        '',
        '—',
        'View this enquiry and the visitor\'s journey: ' . abs_url('/admin/leads.php?id=' . $id),
    ]);
    Mailer::send($to, 'New website enquiry: ' . ($data['service'] ?: $data['name']), implode("\n", $lines), $data['email']);
}

$respond(true);
