<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use SPE\App\{Db, QueryType, Util};

final class Init {
    private const string NS = 'SPE\\Blog\\';

    public function __construct(private Ctx $ctx) {
        session_status() === PHP_SESSION_NONE && session_start();

        // Check remember-me cookie
        $this->checkRemember();

        // Handle ?p=slug for pages (shortcut URL)
        // Default to home page if no route specified
        if (isset($_GET['p'])) {
            $_REQUEST['o'] = 'Pages';
            $_REQUEST['m'] = 'read';
            $_REQUEST['slug'] = $_GET['p'];
        } elseif (!isset($_GET['o']) && !isset($_SESSION['o'])) {
            $_REQUEST['o'] = 'Pages';
            $_REQUEST['m'] = 'read';
            $_REQUEST['slug'] = 'home';
        }

        // Only persist o and t in session; m defaults to 'list' when not in URL
        $this->ctx->in['o'] = Util::ses('o', $this->ctx->in['o']);
        $this->ctx->in['t'] = Util::ses('t', $this->ctx->in['t']);
        $this->ctx->in['m'] = $_REQUEST['m'] ?? 'list';
        $this->ctx->in['id'] = $_REQUEST['id'] ?? 0;

        ['o' => $o, 'm' => $m, 't' => $t] = $this->ctx->in;

        // Route protection based on plugin meta.json
        $meta = $this->ctx->loader->get($o);
        if ($meta) {
            if ($meta->auth && !Util::is_usr()) {
                Util::log('Please login to access this page');
                Util::redirect('?o=Auth');
            }
            if ($meta->admin && !Util::is_adm()) {
                Util::log('Admin access required');
                Util::redirect('?o=Home');
            }
        }

        $model = self::NS . "Plugins\\{$o}\\{$o}Model";
        $this->ctx->ary = class_exists($model) ? (new $model($this->ctx))->$m() : [];

        $view = self::NS . "Plugins\\{$o}\\{$o}View";
        $theme = self::NS . "Themes\\{$t}";

        // Fallback to Simple if theme doesn't exist
        if (!class_exists($theme)) {
            $t = 'Simple';
            $this->ctx->in['t'] = $t;
            $_SESSION['t'] = $t;
            $theme = self::NS . "Themes\\{$t}";
        }

        $render = fn(?object $obj, string $method) =>
            ($obj && method_exists($obj, $method)) ? $obj->$method() : null;

        $v1 = class_exists($view) ? new $view($this->ctx) : null;
        $v2 = class_exists($theme) ? new $theme($this->ctx) : null;

        $this->ctx->out['main'] = $render($v1, $m) ?? $render($v2, $m) ?? $this->ctx->out['main'];
        foreach ($this->ctx->out as $k => &$v)
            $v = $render($v1, $k) ?? $render($v2, $k) ?? $v;

        $this->ctx->buf = $render($v1, 'html') ?? $render($v2, 'html') ?? '';
    }

    private function checkRemember(): void {
        if (Util::is_usr() || !isset($_COOKIE['remember'])) return;

        $db = new Db('users');
        $cookie = $_COOKIE['remember'];
        $usr = $db->read('users', '*', 'cookie = :cookie AND cookie != ""', ['cookie' => $cookie], QueryType::One);

        if ($usr && (int)$usr['acl'] !== 9) {
            $_SESSION['usr'] = [
                'id' => $usr['id'], 'login' => $usr['login'], 'fname' => $usr['fname'],
                'lname' => $usr['lname'], 'acl' => $usr['acl'], 'grp' => $usr['grp']
            ];
        }
    }

    public function __toString(): string {
        $_SESSION['x'] = '';
        return match ($this->ctx->in['x']) {
            'text' => preg_replace('/^\h*\v+/m', '', strip_tags($this->ctx->out['main'])),
            'json' => (header('Content-Type: application/json') ?: '') . $this->ctx->out['main'],
            default => $this->ctx->out[$this->ctx->in['x']] ?? $this->ctx->buf
        };
    }
}
