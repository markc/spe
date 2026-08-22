<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Core;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;

final readonly class Init
{
    private const string NS = 'SPE\\Users\\';
    private array $out;

    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        // Restore session from "remember me" cookie
        $usersDb = new Db('users');
        Util::remember($usersDb);

        [$o, $m, $i] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['i']];

        // Clean URL routing: parse path (strip chapter prefix if present)
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $path = preg_replace('#^\d{2}-[^/]+/?#', '', $path);

        // Route based on path (query string ?o= takes priority)
        if (isset($_GET['o'])) {
            $main = $this->routePlugin($o, $m, $i);
            $ary = [];
        } elseif (!$path || $path === 'index.php') {
            $ary = $ctx->db->read('posts', '*', "slug='home' AND type='page'", [], QueryType::One)
                ?: $ctx->db->read('posts', '*', 'id=1', [], QueryType::One) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = $ary ? new $view($ctx, $ary)->page() : '<div class="card"><p>No content found.</p></div>';
        } elseif ($path === 'blog') {
            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = new $model($ctx)->list();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = new $view($ctx, $ary)->list();
        } else {
            $ary = $ctx->db->read('posts', '*', 'slug=:s', ['s' => $path], QueryType::One) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = $ary
                ? ($ary['type'] === 'page' ? new $view($ctx, $ary)->page() : new $view($ctx, $ary)->read())
                : '<div class="card"><p>Page not found.</p></div>';
        }

        $this->out = [...$ctx->out, ...(is_array($ary) ? $ary : []), 'main' => $main];
    }

    private function routePlugin(string $o, string $m, int $i): string
    {
        $ctx = $this->ctx;

        if ($o === 'Users') {
            $publicMethods = ['login', 'logout'];
            $userMethods = ['profile'];
            $adminMethods = ['list', 'create', 'read', 'update', 'delete'];

            if (in_array($m, $adminMethods) && !Util::is_adm()) {
                Util::log('Admin access required');
                header('Location: ?o=Users&m=login');
                exit();
            }
            if (in_array($m, $userMethods) && !Util::is_usr()) {
                Util::log('Please login');
                header('Location: ?o=Users&m=login');
                exit();
            }

            $model = self::NS . 'Plugins\\Users\\UsersModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Users\\UsersView';
            return new $view($ctx, $ary)->$m();
        }

        if ($o === 'Blog') {
            if (isset($_GET['edit']) && !isset($_REQUEST['m'])) {
                $m = 'list';
            }

            $writeMethods = ['create', 'update', 'delete'];
            if (in_array($m, $writeMethods) && !Util::is_adm()) {
                Util::log('Admin access required');
                header('Location: /blog');
                exit();
            }

            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            return new $view($ctx, $ary)->$m();
        }

        return '<div class="card"><p>Plugin not found.</p></div>';
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        $x = $this->ctx->in['x'];

        if ($x === 'text') {
            return preg_replace('/^\h*\v+/m', '', strip_tags($this->out['main']));
        }
        if ($x === 'json') {
            header('Content-Type: application/json');
            return json_encode($this->out);
        }
        if ($x && isset($this->out[$x])) {
            header('Content-Type: application/json');
            return json_encode($this->out[$x], JSON_PRETTY_PRINT);
        }

        $html = new Theme($this->ctx, $this->out)->render();
        Util::perfLog(__FILE__);
        return $html;
    }
}
