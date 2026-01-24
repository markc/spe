<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Ctx
{
    public array $in;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 'x' => ''],
        public array $out = ['doc' => 'SPE::06', 'page' => '← 06 Session', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [['home', 'Home', 'Home'], ['book-open', 'About', 'About'], ['mail', 'Contact', 'Contact']],
        public array $colors = [['circle', 'Stone', 'default'], ['waves', 'Ocean', 'ocean'], ['trees', 'Forest', 'forest'], ['sunset', 'Sunset', 'sunset']],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();
        // Only 'o' (plugin) is sticky; 'm' (method) defaults to 'list' each request
        $this->in = [
            'o' => $this->ses('o', $in['o']),
            'm' => ($_REQUEST['m'] ?? $in['m']) |> trim(...) |> htmlspecialchars(...),
            'x' => ($_REQUEST['x'] ?? $in['x']) |> trim(...) |> htmlspecialchars(...),
        ];
    }

    // Get/set session value: URL param overrides, else use session, else use default
    public function ses(string $k, mixed $v = ''): mixed
    {
        return $_SESSION[$k] = isset($_REQUEST[$k])
            ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : (trim($_REQUEST[$k]) |> htmlspecialchars(...)))
            : $_SESSION[$k] ?? $v;
    }

    // Flash message: set message, retrieve once, then clear
    public function flash(string $k, ?string $msg = null): ?string
    {
        if ($msg !== null) {
            $_SESSION["_flash_{$k}"] = $msg;
            return $msg;
        }
        $val = $_SESSION["_flash_{$k}"] ?? null;
        unset($_SESSION["_flash_{$k}"]);
        return $val;
    }
}
