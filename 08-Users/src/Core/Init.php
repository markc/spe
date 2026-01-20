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

    public function __construct(
        private Ctx $ctx,
    ) {
        Util::elog(__METHOD__);

        // Restore session from "remember me" cookie
        $usersDb = new Db('users');
        Util::remember($usersDb);

        [$o, $m, $t, $i] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['t'], $ctx->in['i']];

        // Clean URL routing: parse path
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Route based on path (query string ?o= takes priority)
        if (isset($_GET['o'])) {
            // Query string routing: ?o=Blog, ?o=Users
            $main = $this->routePlugin($o, $m, $i);
            $ary = [];
        } elseif (!$path || $path === 'index.php') {
            // Root: show home page (slug=home) or fallback to id=1
            $ary = $ctx->db->read('posts', '*', "slug='home' AND type='page'", [], QueryType::One) ?: $ctx->db->read(
                'posts',
                '*',
                'id=1',
                [],
                QueryType::One,
            ) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = $ary ? new $view($ctx, $ary)->page() : '<div class="card"><p>No content found.</p></div>';
        } elseif ($path === 'blog') {
            // Blog listing (public view, supports ?page= for pagination)
            $o = 'Blog';
            $m = 'list';
            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = new $model($ctx)->list();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = new $view($ctx, $ary)->list();
        } else {
            // Clean URL: look up by slug (any type)
            $ary = $ctx->db->read('posts', '*', 'slug=:s', ['s' => $path], QueryType::One) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            if ($ary) {
                $main = $ary['type'] === 'page' ? new $view($ctx, $ary)->page() : new $view($ctx, $ary)->read();
            } else {
                $main = '<div class="card"><p>Page not found.</p></div>';
            }
        }

        $this->out = [...$ctx->out, ...(is_array($ary) ? $ary : []), 'main' => $main];
    }

    private function routePlugin(string $o, string $m, int $i): string
    {
        $ctx = $this->ctx;

        // Users plugin - includes auth methods
        if ($o === 'Users') {
            // Public methods
            $publicMethods = ['login', 'logout'];
            // User methods (authenticated)
            $userMethods = ['profile'];
            // Admin methods
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

        // Blog plugin - admin operations
        if ($o === 'Blog') {
            // &edit flag without explicit method implies list
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

        // Extended output modes (from HCP pattern)
        if ($x === 'text') {
            // Plain text: strip HTML tags
            return preg_replace('/^\h*\v+/m', '', strip_tags($this->out['main']));
        }

        if ($x === 'json') {
            header('Content-Type: application/json');
            return json_encode($this->out);
        }

        if ($x && isset($this->out[$x])) {
            // Return specific output key as JSON
            header('Content-Type: application/json');
            return json_encode($this->out[$x], JSON_PRETTY_PRINT);
        }

        // Default: render HTML via theme
        $t = $this->ctx->in['t'];
        $theme = self::NS . "Themes\\{$t}";
        $html = new $theme($this->ctx, $this->out)->render();

        // Performance logging (when DEBUG=true)
        Util::perfLog(__FILE__);

        return $html;
    }
}
