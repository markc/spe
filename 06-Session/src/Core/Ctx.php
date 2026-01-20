<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

final class Ctx
{
    public array $in;
    public array $out;

    public function __construct(
        public string $email = 'mc@netserva.org',
        array $in = ['o' => 'Home', 'm' => 'list', 't' => 'Simple', 'x' => ''],
        array $out = ['doc' => 'SPE::06', 'head' => '', 'main' => '', 'foot' => ''],
        public array $nav = [
            ['home',      'Home',    'Home'],
            ['book-open', 'About',   'About'],
            ['mail',      'Contact', 'Contact'],
        ],
        public array $themes = [
            ['layout-template', 'Simple',  'Simple'],
            ['navigation',      'TopNav',  'TopNav'],
            ['panel-left',      'SideBar', 'SideBar'],
        ],
    ) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Sticky parameters: URL overrides session, session persists across requests
        $this->in = array_map($this->ses(...), array_keys($in), $in)
            |> (static fn($v) => array_combine(array_keys($in), $v));
        $this->out = $out;
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
