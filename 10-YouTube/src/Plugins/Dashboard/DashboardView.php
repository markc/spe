<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Dashboard;

use SPE\YouTube\Core\{Ctx, Theme};
use SPE\YouTube\Services\YouTubeService;

/**
 * Dashboard view - Channel overview UI
 */
final class DashboardView extends Theme
{
    #[\Override]
    public function list(): string
    {
        $a = $this->ctx->ary;
        $channel = $a['channel'];
        $stats = $a['stats'];
        $videos = $a['recentVideos'];
        $playlists = $a['playlists'];

        if (!$channel) {
            return '<div class="card"><p>Unable to load channel data.</p></div>';
        }

        $channelTitle = htmlspecialchars($channel->title);
        $channelThumb = htmlspecialchars($channel->thumbnail);
        $channelUrl = htmlspecialchars($channel->url());

        // Stats cards
        $statsHtml = $this->renderStats($stats, $channel);

        // Recent videos grid
        $videosHtml = $this->renderVideoGrid($videos);

        // Playlists
        $playlistsHtml = $this->renderPlaylistList($playlists);

        return <<<HTML
        <div class="dashboard">
            <div class="channel-header card mb-3">
                <div class="flex" style="gap:1.5rem;align-items:center">
                    <img src="$channelThumb" alt="$channelTitle" class="channel-avatar">
                    <div>
                        <h1 style="margin:0">$channelTitle</h1>
                        <a href="$channelUrl" target="_blank" class="text-muted">View on YouTube →</a>
                    </div>
                </div>
            </div>

            $statsHtml

            <div class="grid-2 mb-3">
                <div class="card">
                    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1rem">
                        <h2 style="margin:0">📹 Recent Videos</h2>
                        <a href="?o=Videos" class="btn btn-sm">View All</a>
                    </div>
                    $videosHtml
                </div>
                <div class="card">
                    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1rem">
                        <h2 style="margin:0">📋 Playlists</h2>
                        <a href="?o=Playlists" class="btn btn-sm">View All</a>
                    </div>
                    $playlistsHtml
                </div>
            </div>
        </div>
        <style>
            .channel-avatar { width:80px; height:80px; border-radius:50%; }
            .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
            .stat-card { background:var(--card-bg); border:1px solid var(--border); border-radius:8px; padding:1.25rem; text-align:center; }
            .stat-value { font-size:1.75rem; font-weight:700; color:var(--primary); }
            .stat-label { font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem; }
            .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
            .video-mini { display:flex; gap:0.75rem; padding:0.5rem 0; border-bottom:1px solid var(--border); }
            .video-mini:last-child { border-bottom:none; }
            .video-mini img { width:120px; height:68px; object-fit:cover; border-radius:4px; }
            .video-mini-info h4 { margin:0 0 0.25rem; font-size:0.9rem; }
            .playlist-item { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--border); }
            .playlist-item:last-child { border-bottom:none; }
            @media (max-width:768px) {
                .stats-grid { grid-template-columns:repeat(2,1fr); }
                .grid-2 { grid-template-columns:1fr; }
            }
        </style>
        HTML;
    }

    private function renderStats(array $stats, $channel): string
    {
        $subs = $channel->formattedSubscribers();
        $views = $channel->formattedViews();
        $videos = number_format($stats['totalVideos']);
        $playlists = $stats['playlistCount'];

        return <<<HTML
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">$subs</div>
                <div class="stat-label">Subscribers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$views</div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$videos</div>
                <div class="stat-label">Videos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$playlists</div>
                <div class="stat-label">Playlists</div>
            </div>
        </div>
        HTML;
    }

    private function renderVideoGrid(array $videos): string
    {
        if (empty($videos)) {
            return '<p class="text-muted">No videos yet.</p>';
        }

        $html = '';
        foreach (array_slice($videos, 0, 4) as $video) {
            $title = htmlspecialchars($video->title);
            $thumb = htmlspecialchars($video->thumbnail);
            $views = number_format($video->viewCount);
            $date = $video->formattedDate();

            $html .= <<<HTML
            <a href="?o=Videos&m=read&id={$video->id}" class="video-mini" style="text-decoration:none;color:inherit">
                <img src="$thumb" alt="$title">
                <div class="video-mini-info">
                    <h4>$title</h4>
                    <small class="text-muted">$views views • $date</small>
                </div>
            </a>
            HTML;
        }

        return $html;
    }

    private function renderPlaylistList(array $playlists): string
    {
        if (empty($playlists)) {
            return '<p class="text-muted">No playlists yet.</p>';
        }

        $html = '';
        foreach (array_slice($playlists, 0, 5) as $pl) {
            $title = htmlspecialchars($pl->title);
            $count = $pl->itemCount;
            $privacy = $pl->privacy->label();

            $html .= <<<HTML
            <a href="?o=Playlists&m=read&id={$pl->id}" class="playlist-item" style="text-decoration:none;color:inherit">
                <span>$title <small class="text-muted">($count videos)</small></span>
                <small>$privacy</small>
            </a>
            HTML;
        }

        return $html;
    }

    #[\Override]
    public function create(): string { return $this->list(); }

    #[\Override]
    public function read(): string { return $this->list(); }

    #[\Override]
    public function update(): string { return $this->list(); }

    #[\Override]
    public function delete(): string { return $this->list(); }
}
