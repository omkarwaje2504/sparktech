<?php
/**
 * Enquiry notification e-mails.
 *
 * Uses SMTP when configured in Admin → Settings (recommended on cPanel:
 * use a mailbox you created, e.g. noreply@yourdomain, port 465/SSL), and
 * falls back to PHP mail() otherwise.
 */
final class Mailer
{
    /** Most addresses one notification is sent to. */
    public const MAX_RECIPIENTS = 10;

    /**
     * Split a stored recipient list ("a@x.com, b@y.com", one per line, or ";"-separated)
     * into valid, de-duplicated addresses. Invalid entries are dropped.
     *
     * @return string[]
     */
    public static function recipients(string $list): array
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', $list, -1, PREG_SPLIT_NO_EMPTY) as $addr) {
            if (filter_var($addr, FILTER_VALIDATE_EMAIL) && !isset($out[strtolower($addr)])) {
                $out[strtolower($addr)] = $addr;
            }
        }
        return array_slice(array_values($out), 0, self::MAX_RECIPIENTS);
    }

    /**
     * Send a plain-text e-mail. $to may hold several addresses (see recipients());
     * every address receives the message.
     */
    public static function send(string $to, string $subject, string $text, string $replyTo = ''): bool
    {
        $rcpts = self::recipients($to);
        if (!$rcpts) {
            return false;
        }
        $to = implode(', ', $rcpts);
        $fromEmail = (string)Settings::get('mail_from', '');
        if ($fromEmail === '') {
            $host = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $host = preg_replace('/:\d+$/', '', (string)$host);
            $fromEmail = 'noreply@' . $host;
        }
        $fromName = (string)Settings::get('mail_from_name', 'Website');

        if (Settings::get('smtp_host', '') !== '') {
            try {
                return self::smtp($rcpts, $subject, $text, $fromEmail, $fromName, $replyTo);
            } catch (Throwable $e) {
                error_log('SMTP failed: ' . $e->getMessage());
                return false;
            }
        }

        $headers = [
            'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        return @mail($to, self::encodeName($subject), $text, implode("\r\n", $headers), '-f' . $fromEmail);
    }

    private static function encodeName(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    /** @param string[] $rcpts */
    private static function smtp(array $rcpts, string $subject, string $text, string $from, string $fromName, string $replyTo): bool
    {
        $host = (string)Settings::get('smtp_host');
        $port = (int)Settings::get('smtp_port', '465');
        $secure = (string)Settings::get('smtp_secure', 'ssl'); // ssl | tls | none
        $user = (string)Settings::get('smtp_user', '');
        $pass = (string)Settings::get('smtp_pass', '');

        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("Connect failed: $errstr ($errno)");
        }
        stream_set_timeout($fp, 15);
        $read = function () use ($fp) {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = function (string $c, array $ok) use ($fp, $read) {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int)substr($r, 0, 3), $ok, true)) {
                throw new RuntimeException('SMTP: ' . trim($r));
            }
            return $r;
        };
        $read();
        $ehloHost = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $cmd('EHLO ' . $ehloHost, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS failed');
            }
            $cmd('EHLO ' . $ehloHost, [250]);
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode($pass), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        // One rejected address must not stop the others from getting the enquiry.
        $accepted = [];
        foreach ($rcpts as $rcpt) {
            try {
                $cmd('RCPT TO:<' . $rcpt . '>', [250, 251]);
                $accepted[] = $rcpt;
            } catch (RuntimeException $e) {
                error_log('SMTP recipient rejected (' . $rcpt . '): ' . $e->getMessage());
            }
        }
        if (!$accepted) {
            throw new RuntimeException('SMTP: every recipient was rejected');
        }
        $cmd('DATA', [354]);
        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::encodeName($fromName) . ' <' . $from . '>',
            'To: ' . implode(', ', $accepted),
            'Subject: ' . self::encodeName($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $body = str_replace("\n.", "\n..", str_replace(["\r\n", "\r"], "\n", $text));
        $body = str_replace("\n", "\r\n", $body);
        $cmd(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
        return true;
    }
}
