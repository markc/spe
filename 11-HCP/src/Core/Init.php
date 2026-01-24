<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Acl;
use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;

final readonly class Init
{
    private const string NS = 'SPE\\HCP\\';

    private array $out;

    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        // Restore session from "remember me" cookie
        $usersDb = new Db('users');
        Util::remember($usersDb);

        [$o, $m, $i] = [$ctx->in['o'], $ctx->in['m'], $ctx->in['i']];

        // Clean URL routing
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $path = preg_replace('#^\d{2}-[^/]+/?#', '', $path);

        // Route based on path (query string ?o= takes priority)
        if (isset($_GET['o'])) {
            $main = $this->routePlugin($o, $m, $i);
            $ary = [];
        } elseif (!$path || $path === 'index.php') {
            // Homepage - show home page
            $ary = $ctx->db->read('posts', '*', "slug='home' AND type='page'", [], QueryType::One)
                ?: $ctx->db->read('posts', '*', 'id=1', [], QueryType::One) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = $ary ? (new $view($ctx, $ary))->page() : '<div class="card"><p>Welcome to HCP. Please configure your homepage.</p></div>';
        } elseif ($path === 'blog') {
            // Blog listing
            $model = self::NS . 'Plugins\\Blog\\BlogModel';
            $ary = (new $model($ctx))->list();
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = (new $view($ctx, $ary))->list();
        } else {
            // Page or post by slug
            $ary = $ctx->db->read('posts', '*', 'slug=:s', ['s' => $path], QueryType::One) ?: [];
            $view = self::NS . 'Plugins\\Blog\\BlogView';
            $main = $ary
                ? ($ary['type'] === 'page' ? (new $view($ctx, $ary))->page() : (new $view($ctx, $ary))->read())
                : '<div class="card"><p>Page not found.</p></div>';
        }

        $this->out = [...$ctx->out, ...(is_array($ary) ? $ary : []), 'main' => $main];
    }

    private function routePlugin(string $o, string $m, int $i): string
    {
        $ctx = $this->ctx;

        // Public plugins (no auth required)
        $publicPlugins = ['Auth', 'Blog', 'Docs'];

        // Admin plugins (require Admin level)
        $adminPlugins = ['Posts', 'Categories', 'Pages'];

        // HCP plugins (require SuperAdmin level)
        $hcpPlugins = ['Users', 'System', 'Vhosts', 'Vmails', 'Valias', 'Vdns', 'Ssl', 'Stats'];

        // Auth plugin - public
        if ($o === 'Auth') {
            $userMethods = ['profile', 'changepw'];
            if (in_array($m, $userMethods) && !Util::is_usr()) {
                Util::log('Please login');
                header('Location: ?o=Auth&m=login');
                exit();
            }
            return $this->callPlugin('Auth', $m);
        }

        // Blog/Docs - public read, admin write
        if (in_array($o, ['Blog', 'Docs'])) {
            $writeMethods = ['create', 'update', 'delete'];
            if (in_array($m, $writeMethods) && !Acl::check(Acl::Admin)) {
                Util::log('Admin access required');
                header('Location: /blog');
                exit();
            }
            return $this->callPlugin($o, $m);
        }

        // Admin plugins - require Admin
        if (in_array($o, $adminPlugins)) {
            if (!Acl::check(Acl::Admin)) {
                Util::log('Admin access required');
                header('Location: ?o=Auth&m=login');
                exit();
            }
            return $this->callPlugin($o, $m);
        }

        // HCP plugins - require SuperAdmin
        if (in_array($o, $hcpPlugins)) {
            if (!Acl::check(Acl::SuperAdmin)) {
                Util::log('Super Admin access required');
                header('Location: ?o=Auth&m=login');
                exit();
            }
            return $this->callPlugin($o, $m);
        }

        return '<div class="card"><p>Plugin not found.</p></div>';
    }

    private function callPlugin(string $o, string $m): string
    {
        $ctx = $this->ctx;
        $modelClass = self::NS . "Plugins\\{$o}\\{$o}Model";
        $viewClass = self::NS . "Plugins\\{$o}\\{$o}View";

        if (!class_exists($modelClass)) {
            return '<div class="card"><p>Plugin not implemented: ' . htmlspecialchars($o) . '</p></div>';
        }

        $model = new $modelClass($ctx);
        if (!method_exists($model, $m)) {
            $m = $o === 'Auth' ? 'login' : 'list';
        }
        $data = $model->$m();

        if (is_string($data)) {
            return $data;
        }

        if (class_exists($viewClass)) {
            $view = new $viewClass($ctx, $data);
            return method_exists($view, $m) ? $view->$m() : $view->list();
        }

        return '<pre>' . print_r($data, true) . '</pre>';
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

        // HTMX partial request - return main content only
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            $flash = Util::log();
            $html = $this->out['main'];
            if ($flash) {
                foreach ($flash as $type => $msg) {
                    $msg = htmlspecialchars($msg);
                    $html .= "<script>showToast('{$msg}', '{$type}');</script>";
                }
            }
            return $html;
        }

        $html = new Theme($this->ctx, $this->out)->render();
        Util::perfLog(__FILE__);
        return $html;
    }
}
