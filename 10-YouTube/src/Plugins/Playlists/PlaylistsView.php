<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Playlists;

use SPE\YouTube\Core\{Ctx, Theme};
use SPE\YouTube\Services\{Privacy, YouTubeService};

/**
 * Playlists view - Playlist list and detail UI
 */
final class PlaylistsView extends Theme
{
    #[\Override]
    public function list(): string
    {
        $playlists = $this->ctx->ary['playlists'] ?? [];
        $count = $this->ctx->ary['count'] ?? 0;

        $grid = $this->renderPlaylistGrid($playlists);

        return <<<HTML
        <div class="card">
            <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1.5rem">
                <h1 style="margin:0">📋 Playlists <small class="text-muted">($count)</small></h1>
                <a href="?o=Playlists&m=create" class="btn">+ New Playlist</a>
            </div>
            $grid
        </div>
        <style>
            .playlist-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; }
            .playlist-card { background:var(--card-bg); border:1px solid var(--border); border-radius:8px; padding:1.25rem; transition:transform 0.2s; }
            .playlist-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.1); }
            .playlist-card h3 { margin:0 0 0.5rem; }
            .playlist-card-meta { font-size:0.9rem; color:var(--text-muted); }
            .playlist-actions { margin-top:1rem; display:flex; gap:0.5rem; }
        </style>
        HTML;
    }

    private function renderPlaylistGrid(array $playlists): string
    {
        if (empty($playlists)) {
            return '<p class="text-muted">No playlists yet. Create one!</p>';
        }

        $html = '<div class="playlist-grid">';

        foreach ($playlists as $pl) {
            $title = htmlspecialchars($pl->title);
            $desc = htmlspecialchars($pl->description ?: 'No description');
            $shortDesc = mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc;
            $count = $pl->itemCount;
            $privacy = $pl->privacy->label();
            $url = htmlspecialchars($pl->url());

            $html .= <<<HTML
            <div class="playlist-card">
                <h3>$title</h3>
                <p class="playlist-card-meta">$shortDesc</p>
                <p class="playlist-card-meta">
                    <strong>$count</strong> videos • $privacy
                </p>
                <div class="playlist-actions">
                    <a href="?o=Playlists&m=read&id={$pl->id}" class="btn btn-sm">View</a>
                    <a href="$url" target="_blank" class="btn btn-sm btn-muted">YouTube</a>
                    <a href="?o=Playlists&m=delete&id={$pl->id}" class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this playlist?')">Delete</a>
                </div>
            </div>
            HTML;
        }

        return $html . '</div>';
    }

    #[\Override]
    public function read(): string
    {
        $playlist = $this->ctx->ary['playlist'] ?? null;
        $videos = $this->ctx->ary['videos'] ?? [];

        if (!$playlist) {
            return '<div class="card"><p>Playlist not found.</p></div>';
        }

        $title = htmlspecialchars($playlist->title);
        $desc = htmlspecialchars($playlist->description);
        $count = $playlist->itemCount;
        $privacy = $playlist->privacy->label();
        $url = htmlspecialchars($playlist->url());

        $videosHtml = $this->renderPlaylistVideos($videos);

        return <<<HTML
        <div class="card">
            <a href="?o=Playlists" class="btn btn-sm mb-2">← Back to Playlists</a>

            <div class="flex" style="justify-content:space-between;align-items:flex-start">
                <div>
                    <h1>$title</h1>
                    <p class="text-muted">$desc</p>
                    <p><strong>$count</strong> videos • $privacy</p>
                </div>
                <div>
                    <a href="$url" target="_blank" class="btn">Open on YouTube →</a>
                </div>
            </div>

            <hr>

            <h2>Videos in Playlist</h2>
            $videosHtml
        </div>
        <style>
            .playlist-video { display:flex; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--border); }
            .playlist-video:last-child { border-bottom:none; }
            .playlist-video img { width:160px; height:90px; object-fit:cover; border-radius:4px; }
            .playlist-video-info h4 { margin:0 0 0.5rem; }
            .playlist-video-info p { margin:0; font-size:0.9rem; color:var(--text-muted); }
        </style>
        HTML;
    }

    private function renderPlaylistVideos(array $videos): string
    {
        if (empty($videos)) {
            return '<p class="text-muted">No videos in this playlist yet.</p>';
        }

        $html = '';
        foreach ($videos as $i => $video) {
            $num = $i + 1;
            $title = htmlspecialchars($video->title);
            $thumb = htmlspecialchars($video->thumbnail);
            $views = number_format($video->viewCount);
            $duration = YouTubeService::formatDuration($video->duration);

            $html .= <<<HTML
            <div class="playlist-video">
                <span style="min-width:30px;color:var(--text-muted)">$num</span>
                <img src="$thumb" alt="$title">
                <div class="playlist-video-info">
                    <h4><a href="?o=Videos&m=read&id={$video->id}">$title</a></h4>
                    <p>$views views • $duration</p>
                </div>
            </div>
            HTML;
        }

        return $html;
    }

    #[\Override]
    public function create(): string
    {
        $privacyOptions = $this->ctx->ary['privacyOptions'] ?? Privacy::cases();

        $optionsHtml = '';
        foreach ($privacyOptions as $p) {
            $selected = $p === Privacy::Public ? ' selected' : '';
            $optionsHtml .= "<option value=\"{$p->value}\"$selected>{$p->label()}</option>";
        }

        return <<<HTML
        <div class="card" style="max-width:500px;margin:0 auto">
            <h1>📋 New Playlist</h1>
            <form method="post">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required maxlength="150">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="privacy">Privacy</label>
                    <select id="privacy" name="privacy">
                        $optionsHtml
                    </select>
                </div>
                <div class="flex" style="justify-content:space-between">
                    <a href="?o=Playlists" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">Create Playlist</button>
                </div>
            </form>
        </div>
        HTML;
    }

    #[\Override]
    public function update(): string { return ''; }

    #[\Override]
    public function delete(): string { return ''; }
}
