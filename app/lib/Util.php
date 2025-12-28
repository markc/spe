<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

final class Util
{
    // === Text Processing ===

    public static function enc(string $s): string
    {
        return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
    }

    public static function excerpt(string $s, int $len = 200): string
    {
        $s = strip_tags($s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        return strlen($s) > $len ? substr($s, 0, $len) . '...' : $s;
    }

    public static function esc(array $in): array
    {
        foreach ($in as $k => $v)
            $in[$k] = isset($_REQUEST[$k]) && !is_array($_REQUEST[$k]) ? self::enc($_REQUEST[$k]) : $v;
        return $in;
    }

    public static function nlbr(string $s): string
    {
        return nl2br(self::enc($s));
    }

    // === Session ===

    public static function ses(string $k, mixed $v = '', mixed $x = null): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : self::enc($_REQUEST[$k]))
            : ($_SESSION[$k] ?? $x ?? $v);
    }

    // === Authentication ===

    public static function is_usr(int $id = null): bool
    {
        return is_null($id)
            ? isset($_SESSION['usr'])
            : isset($_SESSION['usr']['id']) && (int)$_SESSION['usr']['id'] === $id;
    }

    public static function is_adm(): bool
    {
        return Acl::check(Acl::Admin);
    }

    public static function is_acl(int|Acl $acl): bool
    {
        $required = $acl instanceof Acl ? $acl : (Acl::tryFrom($acl) ?? Acl::Anonymous);
        return Acl::check($required);
    }

    // === Security ===

    public static function token(int $len = 32): string
    {
        $token = base64_encode(random_bytes($len));
        return substr(str_replace(['+', '/', '='], '', $token), 0, $len);
    }

    public static function csrf(): string
    {
        if (!isset($_SESSION['c'])) {
            $_SESSION['c'] = self::token(32);
        }
        return $_SESSION['c'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="c" value="' . self::csrf() . '">';
    }

    public static function is_post(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        if (!isset($_POST['c']) || !isset($_SESSION['c']) || $_POST['c'] !== $_SESSION['c']) {
            self::log('Invalid form submission');
            return false;
        }
        return true;
    }

    public static function chkpw(string $pw, string $pw2 = ''): bool
    {
        if (strlen($pw) < 12) {
            self::log('Password must be at least 12 characters');
            return false;
        }
        if (!preg_match('/[0-9]/', $pw)) {
            self::log('Password must contain at least one number');
            return false;
        }
        if (!preg_match('/[A-Z]/', $pw)) {
            self::log('Password must contain at least one capital letter');
            return false;
        }
        if (!preg_match('/[a-z]/', $pw)) {
            self::log('Password must contain at least one lowercase letter');
            return false;
        }
        if ($pw2 && $pw !== $pw2) {
            self::log('Passwords do not match');
            return false;
        }
        return true;
    }

    public static function genpw(int $len = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $pw = '';
        for ($i = 0; $i < $len; $i++) {
            $pw .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pw;
    }

    // === OTP (One Time Password) for password reset (from HCP pattern) ===

    private const int OTP_LENGTH = 10;
    private const int OTP_TTL = 3600;  // 1 hour

    public static function genOtp(): string
    {
        return self::token(self::OTP_LENGTH);
    }

    public static function chkOtp(int $otpttl): bool
    {
        if (!$otpttl) {
            self::log('Invalid reset token');
            return false;
        }
        if (($otpttl + self::OTP_TTL) < time()) {
            self::log('Your reset token has expired');
            return false;
        }
        return true;
    }

    public static function mailResetPw(string $email, string $otp, string $from = ''): bool
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $self = str_replace('index.php', '', $_SERVER['PHP_SELF'] ?? '/');
        $link = "{$scheme}://{$host}{$self}?o=Users&m=resetpw&otp={$otp}";

        $subject = "Password reset for {$host}";
        $body = <<<MAIL
Here is your one-time password reset link, valid for 1 hour.

Click below to reset your password:

{$link}

If you did not request this, please ignore this message.
MAIL;

        $headers = $from ? "From: {$from}" : '';
        return mail($email, $subject, $body, $headers);
    }

    // Decode HTML entities in password (handles special chars from forms)
    public static function decpw(string $pw): string
    {
        return html_entity_decode($pw, ENT_QUOTES, 'UTF-8');
    }

    // === Cookies ===

    public static function getCookie(string $name, string $default = ''): string
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function setCookie(string $name, string $val, int $exp = 604800): bool
    {
        return setcookie($name, $val, [
            'expires' => time() + $exp,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public static function delCookie(string $name): bool
    {
        return self::setCookie($name, '', -3600);
    }

    // === Flow Control ===

    public static function redirect(string $url, int $delay = 0, string $msg = ''): never
    {
        if ($delay > 0) {
            header("Refresh: $delay; url=$url");
            echo "<!DOCTYPE html><title>Redirecting...</title>";
            echo "<h2 style='text-align:center'>Redirecting in $delay seconds...</h2>";
            if ($msg) echo "<pre style='width:50em;margin:0 auto'>$msg</pre>";
            exit;
        }
        header("Location: $url");
        exit;
    }

    // === Flash Messages (accumulating, dual-use) ===

    public static function log(string $msg = '', string $type = 'danger'): array
    {
        if ($msg) {
            // Write mode
            $_SESSION['log'][$type] = empty($_SESSION['log'][$type])
                ? $msg
                : $_SESSION['log'][$type] . '<br>' . $msg;
        } elseif (!empty($_SESSION['log'])) {
            // Read mode (no $msg passed) - return and clear
            $log = $_SESSION['log'];
            $_SESSION['log'] = [];
            return $log;
        }
        return [];
    }

    public static function toast(): string
    {
        $log = self::log();
        if (!$log) return '';

        $html = '';
        foreach ($log as $type => $msg) {
            $ok = $type === 'success';
            $bg = $ok ? '#d4edda' : '#f8d7da';
            $fg = $ok ? '#155724' : '#721c24';
            $html .= "<div style=\"margin-bottom:1rem;padding:1rem;border-radius:4px;background:$bg;color:$fg\">" . $msg . "</div>";
        }
        return $html;
    }

    // === Time Formatting ===

    public static function timeAgo(int|string $date): string
    {
        $ts = is_numeric($date) ? (int)$date : strtotime($date);
        $d = abs(time() - $ts);

        if ($d < 10) return 'just now';

        $units = [
            ['year', 31536000], ['month', 2678400], ['week', 604800],
            ['day', 86400], ['hour', 3600], ['min', 60], ['sec', 1]
        ];
        $parts = [];
        foreach ($units as [$name, $secs]) {
            if (!($d >= $secs && count($parts) < 2)) { continue; }

$amt = (int)($d / $secs);
                $parts[] = "$amt $name" . ($amt > 1 ? 's' : '');
                $d %= $secs;
        }
        return implode(' ', $parts) . ' ago';
    }

    // === Number Formatting ===

    public static function numfmt(float $size, int $precision = null): string
    {
        if ($size == 0) return '0';
        if ($size >= 1e12) return round($size / 1e12, $precision ?? 3) . ' TB';
        if ($size >= 1e9) return round($size / 1e9, $precision ?? 2) . ' GB';
        if ($size >= 1e6) return round($size / 1e6, $precision ?? 1) . ' MB';
        if ($size >= 1e3) return round($size / 1e3, $precision ?? 0) . ' KB';
        return $size . ' B';
    }

    // === Markdown (delegates to Md class) ===

    public static function md(string $s): string
    {
        return Md::parse($s);
    }

    // === Remember Me (persistent login via cookie) ===

    public static function remember(Db $db): void
    {
        // Already logged in
        if (self::is_usr()) return;

        // Check for remember cookie
        $cookie = self::getCookie('remember');
        if (!$cookie) return;

        // Look up user by cookie token
        $usr = $db->read('users', '*', 'cookie = :cookie', ['cookie' => $cookie], QueryType::One);
        if (!$usr || (int)$usr['acl'] === 9) return;

        // Restore session
        $_SESSION['usr'] = [
            'id' => $usr['id'],
            'login' => $usr['login'],
            'fname' => $usr['fname'],
            'lname' => $usr['lname'],
            'acl' => $usr['acl']
        ];
    }

    public static function setRemember(Db $db, int $id): void
    {
        $token = self::token(32);
        $db->update('users', ['cookie' => $token], 'id = :id', ['id' => $id]);
        self::setCookie('remember', $token, 86400 * 30); // 30 days
    }

    public static function clearRemember(Db $db, int $id): void
    {
        $db->update('users', ['cookie' => ''], 'id = :id', ['id' => $id]);
        self::delCookie('remember');
    }

    // === API Authentication ===

    public static function chkapi(string $key): bool
    {
        $apiKey = Env::get('API_KEY', '');
        if (!$apiKey || $key !== $apiKey) {
            self::log('Invalid API key');
            return false;
        }
        return true;
    }

    // === Debug Logging ===

    public static function elog(string $msg): void
    {
        if (Env::get('DEBUG', '') === 'true') {
            error_log($msg);
        }
    }

    public static function dump(mixed ...$vars): void
    {
        if (Env::get('DEBUG', '') === 'true') {
            foreach ($vars as $var) {
                error_log(print_r($var, true));
            }
        }
    }

    // === Performance Timing ===

    public static function elapsed(): float
    {
        return round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4);
    }

    public static function perfLog(string $label = ''): void
    {
        $time = self::elapsed();
        $mem = self::numfmt(memory_get_peak_usage(true));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        self::elog(($label ? "$label " : '') . "{$ip} {$time}s {$mem}");
    }

    // === Debug Output (for $out['end'] slot) ===

    public static function dbg(mixed ...$vars): string
    {
        if (Env::get('DEBUG', '') !== 'true') return '';

        $out = "<pre style='background:#1e1e1e;color:#d4d4d4;padding:1rem;margin:1rem 0;overflow:auto;font-size:12px'>";
        $out .= "<b>DEBUG</b> " . self::elapsed() . "s | " . self::numfmt(memory_get_peak_usage(true)) . "\n";
        $out .= str_repeat('─', 60) . "\n";

        foreach ($vars as $i => $var) {
            $out .= "<b>[$i]</b> " . self::enc(print_r($var, true)) . "\n";
        }

        $out .= str_repeat('─', 60) . "\n";
        $out .= "GET: " . self::enc(print_r($_GET, true));
        $out .= "POST: " . self::enc(print_r($_POST, true));
        $out .= "SESSION: " . self::enc(print_r($_SESSION ?? [], true));
        return $out . "</pre>";
    }
}
