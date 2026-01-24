<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Core;

use SPE\App\Acl;
use SPE\App\Util;

final readonly class Init
{
    private const string NS = 'SPE\\HCP\\';

    private array $out;

    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);

        [$o, $m] = [$ctx->in['o'], $ctx->in['m']];

        // Auth plugin - public login
        if ($o === 'Auth') {
            $main = $this->routePlugin('Auth', $m);
        }
        // All other plugins require admin access
        elseif (!Acl::check(Acl::Admin)) {
            Util::log('Admin access required');
            header('Location: ?o=Auth&m=login');
            exit();
        } else {
            $main = $this->routePlugin($o, $m);
        }

        $this->out = [...$ctx->out, 'main' => $main];
    }

    private function routePlugin(string $o, string $m): string
    {
        $ctx = $this->ctx;

        // Map plugin names to classes
        $plugins = ['Auth', 'System', 'Vhosts', 'Vmails', 'Valias', 'Vdns', 'Ssl', 'Stats'];

        if (!in_array($o, $plugins)) {
            return '<div class="card"><p>Plugin not found: ' . htmlspecialchars($o) . '</p></div>';
        }

        $modelClass = self::NS . "Plugins\\{$o}\\{$o}Model";
        $viewClass = self::NS . "Plugins\\{$o}\\{$o}View";

        if (!class_exists($modelClass)) {
            return '<div class="card"><p>Plugin not implemented: ' . htmlspecialchars($o) . '</p></div>';
        }

        // Execute model method
        $model = new $modelClass($ctx);
        if (!method_exists($model, $m)) {
            $m = $o === 'Auth' ? 'login' : 'list';
        }
        if (!method_exists($model, $m)) {
            return '<div class="card"><p>Method not found: ' . htmlspecialchars($m) . '</p></div>';
        }
        $data = $model->$m();

        // If model returned a string, use it directly
        if (is_string($data)) {
            return $data;
        }

        // Render via view
        if (class_exists($viewClass)) {
            $view = new $viewClass($ctx, $data);
            return method_exists($view, $m) ? $view->$m() : $view->list();
        }

        // Fallback: dump data
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
