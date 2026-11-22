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
    public static function send(string $to, string $subject, string $text, string $replyTo = ''): bool
    {
        $to = trim($to);
        if ($to === '') {
            return false;
        }
        $fromEmail = (string)Settings::get('mail_from', '');
        if ($fromEmail === '') {
            $host = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $host = preg_replace('/:\d+$/', '', (string)$host);
            $fromEmail = 'noreply@' . $host;
        }
        $fromName = (string)Settings::get('mail_from_name', 'Website');

        if (Settings::get('smtp_host', '') !== '') {
            try {
                return self::smtp($to, $subject, $text, $fromEmail, $fromName, $replyTo);
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

    private static function smtp(string $to, string $subject, string $text, string $from, string $fromName, string $replyTo): bool
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
        foreach (preg_split('/\s*,\s*/', $to) as $rcpt) {
            if ($rcpt !== '') {
                $cmd('RCPT TO:<' . $rcpt . '>', [250, 251]);
            }
        }
        $cmd('DATA', [354]);
        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::encodeName($fromName) . ' <' . $from . '>',
            'To: ' . $to,
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
