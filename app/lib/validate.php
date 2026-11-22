<?php
/**
 * Enquiry checks, so the inbox only gets real, answerable enquiries.
 *
 *  1. Format rules (regex) — a failed rule rejects the form with a message
 *     under the field. The same patterns run in the browser first
 *     (assets/js/site.js → initContactForm) for instant feedback.
 *  2. Spam signals — suspicious but well-formed enquiries are still saved,
 *     marked "filtered" (no e-mail notification) with the reasons, so a real
 *     customer is never lost. Admin → Enquiries → Filtered.
 */
final class LeadCheck
{
    /** Letters in any language, spaces, . ' ’ - (e.g. "Dr. A. K. Sharma", "O'Brien", "José"). */
    const NAME = '/^(?=.{2,80}$)\p{L}[\p{L}\p{M}.\'’\- ]*[\p{L}\p{M}.]$/u';
    /** name@domain.tld — no spaces, no consecutive dots, real-looking TLD. */
    const EMAIL = '/^(?!.*\.\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\-]{0,62}[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,24}$/';
    /** Optional +, digits, spaces, dashes, dots, brackets. Digit count is checked separately (7–15, E.164). */
    const PHONE = '/^\+?[0-9(][0-9 ().\-]{5,24}[0-9]$/';
    /** Letters, numbers and common company punctuation (& . , \' ( ) / + -). */
    const COMPANY = '/^[\p{L}\p{N}][\p{L}\p{N}\p{M} &.,\'’()\/+\-–]{1,119}$/u';
    const MESSAGE_MIN = 20;
    const MESSAGE_MAX = 5000;

    /** Throw-away inbox services. */
    const DISPOSABLE = ['mailinator.com', 'guerrillamail.com', 'guerrillamail.info', 'guerrillamail.net', 'sharklasers.com',
        'grr.la', '10minutemail.com', '10minutemail.net', 'temp-mail.org', 'tempmail.com', 'tempmail.net', 'tempmail.dev',
        'tempmailo.com', 'tempr.email', 'yopmail.com', 'yopmail.net', 'trashmail.com', 'getnada.com', 'nada.email',
        'dispostable.com', 'maildrop.cc', 'fakeinbox.com', 'throwawaymail.com', 'mintemail.com', 'mohmal.com',
        'emailondeck.com', 'spamgourmet.com', 'discard.email', 'mailnesia.com', 'mytemp.email', 'burnermail.io',
        'moakt.com', 'inboxkitten.com', '1secmail.com', '1secmail.org', '1secmail.net', 'emailfake.com', 'tmpmail.org',
        'mail.tm', 'mailpoof.com', 'spambox.us', 'trashmail.de', 'wegwerfmail.de', 'tempinbox.com', 'mailcatch.com'];

    /** Common misspellings of big mail providers → suggestion. */
    const TYPOS = ['gmial.com' => 'gmail.com', 'gamil.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gmail.co' => 'gmail.com',
        'gmail.con' => 'gmail.com', 'gmail.cm' => 'gmail.com', 'gmaill.com' => 'gmail.com', 'gnail.com' => 'gmail.com',
        'hotmial.com' => 'hotmail.com', 'hotmal.com' => 'hotmail.com', 'hotmail.con' => 'hotmail.com',
        'yaho.com' => 'yahoo.com', 'yahooo.com' => 'yahoo.com', 'yahoo.con' => 'yahoo.com', 'yahoo.co' => 'yahoo.com',
        'outlok.com' => 'outlook.com', 'outlook.con' => 'outlook.com', 'rediffmail.con' => 'rediffmail.com',
        'redifmail.com' => 'rediffmail.com'];

    /** Typical SEO / marketing / scam spam (whole words, case-insensitive). */
    const SPAM_WORDS = ['viagra', 'cialis', 'casino', 'betting', 'crypto', 'bitcoin', 'forex', 'porn', 'escort', 'dating',
        'loan offer', 'seo services', 'seo service', 'backlink', 'backlinks', 'guest post', 'link building', 'rank your website',
        'first page of google', 'page 1 of google', 'increase your traffic', 'website traffic', 'web design services',
        'website redesign', 'digital marketing services', 'lead generation services', 'app development services',
        'outsourcing services', 'virtual assistant', 'unsubscribe', 'click here', 'limited offer', 'investment opportunity'];

    /** Default messages (the contact page's form.err_* fields override them). */
    const MESSAGES = [
        'name' => 'Please enter your full name (letters only, at least 2 characters).',
        'email' => 'Please enter a valid e-mail address, e.g. name@company.com.',
        'email_temp' => 'Please use your company or personal e-mail — temporary inboxes are not accepted.',
        'email_domain' => 'This e-mail domain does not seem to exist. Please check the spelling.',
        'phone' => 'Please enter a valid phone number with country code, e.g. +91 98765 43210.',
        'company' => 'Please enter a valid company name, or leave it empty.',
        'service' => 'Please choose the service you are interested in.',
        'message' => 'Please describe your requirement in a few words (at least 20 characters).',
        'message_links' => 'Please remove links and HTML from your message.',
    ];

    /** Message for $key, preferring the text set on the contact page. */
    public static function message(string $key): string
    {
        $custom = Content::doc('contact')->str('form.err_' . $key);
        return $custom !== '' ? $custom : (self::MESSAGES[$key] ?? 'Please check this field.');
    }

    /** Collapse whitespace, strip control characters. */
    public static function tidy(string $value, bool $multiline = false): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        if ($multiline) {
            $value = preg_replace("/[ \t]+/u", ' ', str_replace(["\r\n", "\r"], "\n", $value)) ?? '';
            $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? '';
        } else {
            $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        }
        return trim($value);
    }

    /**
     * Validate an enquiry. $allowedServices = options shown in the form (empty = any).
     * Returns ['data' => cleaned values, 'errors' => [field => message]].
     */
    public static function validate(array $in, array $allowedServices = []): array
    {
        $d = [
            'name' => self::tidy((string)($in['name'] ?? '')),
            'email' => strtolower(self::tidy((string)($in['email'] ?? ''))),
            'phone' => self::tidy((string)($in['phone'] ?? '')),
            'company' => self::tidy((string)($in['company'] ?? '')),
            'service' => self::tidy((string)($in['service'] ?? '')),
            'message' => self::tidy((string)($in['message'] ?? ''), true),
        ];
        $errors = [];

        if (!preg_match(self::NAME, $d['name']) || preg_match('/(.)\1{3,}/u', $d['name'])) {
            $errors['name'] = self::message('name');
        }

        if (!preg_match(self::EMAIL, $d['email']) || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = self::message('email');
        } else {
            $domain = substr(strrchr($d['email'], '@'), 1);
            if (isset(self::TYPOS[$domain])) {
                $errors['email'] = 'Did you mean ' . substr($d['email'], 0, -strlen($domain)) . self::TYPOS[$domain] . '?';
            } elseif (self::isDisposable($domain)) {
                $errors['email'] = self::message('email_temp');
            } elseif (!self::domainExists($domain)) {
                $errors['email'] = self::message('email_domain');
            }
        }

        if (!self::phoneValid($d['phone'])) {
            $errors['phone'] = self::message('phone');
        }

        if ($d['company'] !== '' && (!preg_match(self::COMPANY, $d['company']) || !preg_match('/\p{L}/u', $d['company']))) {
            $errors['company'] = self::message('company');
        }

        if ($allowedServices && !in_array($d['service'], $allowedServices, true)) {
            $errors['service'] = self::message('service');
        }

        $len = mb_strlen($d['message']);
        if ($len < self::MESSAGE_MIN || $len > self::MESSAGE_MAX || count(preg_split('/\s+/u', $d['message'])) < 3) {
            $errors['message'] = self::message('message');
        } elseif (preg_match('~<\s*/?\s*[a-z!]|\[/?(url|link|img)~i', $d['message']) || self::linkCount($d['message']) > 1) {
            $errors['message'] = self::message('message_links');
        }

        return ['data' => $d, 'errors' => $errors];
    }

    /** 7–15 digits (E.164), no fake patterns; Indian numbers must have 10 digits after +91 / 0. */
    public static function phoneValid(string $phone): bool
    {
        if (!preg_match(self::PHONE, $phone) || substr_count($phone, '+') > 1) {
            return false;
        }
        $digits = preg_replace('/\D/', '', $phone);
        $n = strlen($digits);
        if ($n < 7 || $n > 15 || count(array_unique(str_split($digits))) < 3) {
            return false;
        }
        foreach (['0123456789', '1234567890', '9876543210'] as $seq) {
            if (strpos($digits, substr($seq, 0, 8)) !== false) {
                return false;
            }
        }
        $intl = $phone[0] === '+';
        if ($intl && str_starts_with($digits, '91')) {
            return $n === 12 && $digits[2] !== '0';
        }
        if (!$intl && $n === 10) {
            return $digits[0] !== '0';                  // Indian number without country code
        }
        if (!$intl && $n === 11 && $digits[0] === '0') {
            return $digits[1] !== '0';                  // 0 + 10-digit Indian number
        }
        if (!$intl && $n === 12 && str_starts_with($digits, '91')) {
            return $digits[2] !== '0';
        }
        return $intl;                                   // other countries need their +code
    }

    public static function isDisposable(string $domain): bool
    {
        foreach (self::DISPOSABLE as $bad) {
            if ($domain === $bad || str_ends_with($domain, '.' . $bad)) {
                return true;
            }
        }
        return false;
    }

    /** Domain has mail (MX) or address (A) records. Skipped when the server cannot do DNS lookups. */
    public static function domainExists(string $domain): bool
    {
        static $dnsWorks = null;
        if (!function_exists('checkdnsrr')) {
            return true;
        }
        if ($dnsWorks === null) {
            $dnsWorks = @checkdnsrr('gmail.com', 'MX');
        }
        if (!$dnsWorks) {
            return true;
        }
        return @checkdnsrr($domain . '.', 'MX') || @checkdnsrr($domain . '.', 'A');
    }

    public static function linkCount(string $text): int
    {
        return (int)preg_match_all('~(https?://|www\.)\S+~i', $text);
    }

    /**
     * Spam signals for a well-formed enquiry. Returns reasons; 3+ points = filtered.
     * $context: form_age (seconds), engaged (bool|null, from analytics), visitor_seen (bool|null).
     */
    public static function spamSignals(array $d, array $context = []): array
    {
        $points = 0;
        $reasons = [];
        $add = function (int $p, string $why) use (&$points, &$reasons) {
            $points += $p;
            $reasons[] = $why;
        };

        $text = mb_strtolower($d['name'] . ' ' . $d['company'] . ' ' . $d['message']);
        $hits = [];
        foreach (self::SPAM_WORDS as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $text)) {
                $hits[] = $w;
            }
        }
        if ($hits) {
            $add(min(4, 2 * count($hits)), 'spam words: ' . implode(', ', array_slice($hits, 0, 3)));
        }
        if (self::linkCount($d['message']) > 0) {
            $add(1, 'link in message');
        }
        // Keyboard mashing: long Latin words with no vowels, or 5+ consonants in a row.
        $plain = preg_replace('~(https?://|www\.)\S+|\S+@\S+~i', ' ', $d['name'] . ' ' . $d['message']);
        if (preg_match('/\b[b-df-hj-np-tv-xz]{5,}\b|[b-df-hj-np-tv-xz]{6,}/i', $plain)) {
            $add(2, 'gibberish text');
        }
        $letters = preg_replace('/[^A-Za-z]/', '', $d['message']);
        if (strlen($letters) > 30 && strtoupper($letters) === $letters) {
            $add(1, 'message in capitals');
        }
        if ($d['company'] !== '' && mb_strtolower($d['company']) === mb_strtolower($d['name'])) {
            $add(1, 'company same as name');
        }
        $age = $context['form_age'] ?? null;
        if ($age !== null && $age < 6) {
            $add(2, 'form filled in ' . (int)$age . ' s');
        }
        if (($context['engaged'] ?? null) === false) {
            $add(2, 'no human interaction on the page');
        }
        if (($context['visitor_seen'] ?? null) === false) {
            $add(1, 'no page view recorded');
        }
        try {
            $dupes = (int)DB::value('SELECT COUNT(*) FROM {leads} WHERE (email = ? OR phone = ?) AND created_at > ?',
                [$d['email'], $d['phone'], date('Y-m-d H:i:s', time() - 86400)]);
            if ($dupes >= 2) {
                $add(2, 'repeat sender (' . $dupes . ' today)');
            }
        } catch (Throwable $e) {
        }
        return ['points' => $points, 'reasons' => $reasons, 'spam' => $points >= 3];
    }
}
