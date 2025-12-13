<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Themes;

use SPE\YouTube\Core\{Ctx, Theme, Util};

/**
 * Simple theme for YouTube Manager
 */
final class Simple extends Theme
{
    private const string TITLE = 'YouTube Manager';

    #[\Override]
    public function html(): string
    {
        $title = self::TITLE;
        $nav = $this->buildNav();
        $main = $this->ctx->out['main'] ?? '';
        $toast = Util::toast();
        $channel = $_SESSION['channel'] ?? null;
        $isAuth = !empty($_SESSION['authenticated']);

        $userMenu = $isAuth && $channel
            ? $this->userMenu($channel)
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="color-scheme" content="light dark">
            <title>$title</title>
            <link rel="stylesheet" href="/spe.css">
            <style>
                :root { --primary: #ff0000; }
                .yt-nav { background: #282828; padding: 0.5rem 1rem; }
                .yt-nav a { color: #fff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
                .yt-nav a:hover { background: rgba(255,255,255,0.1); }
                .yt-nav a.active { background: var(--primary); }
                .user-menu { display: flex; align-items: center; gap: 0.75rem; margin-left: auto; }
                .user-menu img { width: 32px; height: 32px; border-radius: 50%; }
                .btn-danger { background: #dc3545; color: #fff; }
                .btn-danger:hover { background: #c82333; }
            </style>
        </head>
        <body>
            <nav class="yt-nav flex items-center">
                <a href="?" style="font-weight:bold;font-size:1.1rem">📺 $title</a>
                $nav
                $userMenu
            </nav>
            <div class="container" style="padding:1.5rem">
                $toast
                <main>$main</main>
            </div>
            <script src="/spe.js"></script>
        </body>
        </html>
        HTML;
    }

    private function buildNav(): string
    {
        if (empty($_SESSION['authenticated'])) {
            return '';
        }

        $current = $this->ctx->in['o'] ?? 'Dashboard';
        $items = [
            'Dashboard' => '🏠 Dashboard',
            'Videos' => '📹 Videos',
            'Playlists' => '📋 Playlists',
            'Channel' => '📊 Channel',
        ];

        $html = '';
        foreach ($items as $o => $label) {
            $active = $o === $current ? ' class="active"' : '';
            $html .= "<a href=\"?o=$o\"$active>$label</a>";
        }

        return $html;
    }

    private function userMenu(array $channel): string
    {
        $title = htmlspecialchars($channel['title'] ?? '');
        $thumb = htmlspecialchars($channel['thumbnail'] ?? '');

        return <<<HTML
        <div class="user-menu">
            <img src="$thumb" alt="$title" title="$title">
            <a href="?o=Auth&m=delete" style="font-size:0.9rem">Logout</a>
            <button class="theme-toggle" id="theme-icon">🌙</button>
        </div>
        HTML;
    }
}
