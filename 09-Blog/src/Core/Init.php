<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use SPE\App\Acl;
use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;

final readonly class Init
{
    private const string NS = 'SPE\\Blog\\';

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
            // Query string routing: ?o=Auth, ?o=Users, ?o=Blog, etc.
            $main = $this->routePlugin($o, $m, $i);
            $ary = [];
        } elseif (!$path || $path === 'index.php') {
            // Root: show home page
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
            // Blog listing
            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = new $model($ctx)->list();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = new $view($ctx, $ary)->list();
        } else {
            // Clean URL: look up by slug
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

        // Auth plugin - public and user methods
        if ($o === 'Auth') {
            $publicMethods = ['login', 'logout', 'forgotpw', 'resetpw'];
            $userMethods = ['profile', 'changepw'];

            if (in_array($m, $userMethods) && !Util::is_usr()) {
                Util::log('Please login');
                header('Location: ?o=Auth&m=login');
                exit();
            }

            $model = self::NS . 'Plugins\\Auth\\AuthModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Auth\\AuthView';
            return new $view($ctx, $ary)->$m();
        }

        // Users plugin - admin only
        if ($o === 'Users') {
            if (!Acl::check(Acl::Admin)) {
                Util::log('Admin access required');
                header('Location: ?o=Auth&m=login');
                exit();
            }

            $model = self::NS . 'Plugins\\Users\\UsersModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Users\\UsersView';
            return new $view($ctx, $ary)->$m();
        }

        // Blog plugin - read is public, write requires admin
        if ($o === 'Blog') {
            $writeMethods = ['create', 'update', 'delete'];
            if (in_array($m, $writeMethods) && !Acl::check(Acl::Admin)) {
                Util::log('Admin access required');
                header('Location: /blog');
                exit();
            }

            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            return new $view($ctx, $ary)->$m();
        }

        // Categories plugin - admin only
        if ($o === 'Categories') {
            if (!Acl::check(Acl::Admin)) {
                Util::log('Admin access required');
                header('Location: ?o=Auth&m=login');
                exit();
            }

            $model = self::NS . 'Plugins\\Categories\\CategoriesModel';
            $ary = new $model($ctx)->$m();
            $view = self::NS . 'Plugins\\Categories\\CategoriesView';
            return new $view($ctx, $ary)->$m();
        }

        return '<div class="card"><p>Plugin not found.</p></div>';
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        $x = $this->ctx->in['x'];

        // Extended output modes
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

        // Default: render HTML via theme
        $t = $this->ctx->in['t'];
        $theme = self::NS . "Themes\\{$t}";
        $html = new $theme($this->ctx, $this->out)->render();

        Util::perfLog(__FILE__);

        return $html;
    }
}
