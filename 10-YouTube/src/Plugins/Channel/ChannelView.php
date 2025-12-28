<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Channel;

use SPE\YouTube\Core\{ Theme};

/**
 * Channel view - Channel statistics UI
 */
final class ChannelView extends Theme
{
    #[\Override]
    public function list(): string
    {
        $channel = $this->ctx->ary['channel'] ?? null;

        if (!$channel) {
            return '<div class="card"><p>Unable to load channel data.</p></div>';
        }

        $title = htmlspecialchars($channel->title);
        $desc = nl2br(htmlspecialchars($channel->description));
        $thumb = htmlspecialchars($channel->thumbnail);
        $url = htmlspecialchars($channel->url());
        $subs = $channel->formattedSubscribers();
        $views = $channel->formattedViews();
        // Use actual count (API stats can be delayed by hours/days)
        $videos = number_format($this->ctx->ary['actualVideoCount'] ?? $channel->videoCount);
        $joined = $channel->publishedAt
            ? date('F j, Y', strtotime($channel->publishedAt))
            : 'Unknown';

        return <<<HTML
        <div class="card">
            <div class="channel-profile">
                <img src="$thumb" alt="$title" class="channel-avatar-lg">
                <div class="channel-info">
                    <h1>$title</h1>
                    <p class="text-muted">$url</p>
                </div>
            </div>

            <div class="channel-stats-grid mt-3">
                <div class="stat-box">
                    <div class="stat-number">$subs</div>
                    <div class="stat-label">Subscribers</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">$views</div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">$videos</div>
                    <div class="stat-label">Videos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">📅</div>
                    <div class="stat-label">Joined $joined</div>
                </div>
            </div>

            <div class="channel-description mt-3">
                <h3>About</h3>
                <p>$desc</p>
            </div>

            <div class="mt-3">
                <a href="$url" target="_blank" class="btn">View on YouTube →</a>
            </div>
        </div>
        <style>
            .channel-profile { display:flex; gap:1.5rem; align-items:center; }
            .channel-avatar-lg { width:120px; height:120px; border-radius:50%; }
            .channel-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; }
            .stat-box { background:var(--bg); padding:1.5rem; border-radius:8px; text-align:center; }
            .stat-number { font-size:1.75rem; font-weight:700; color:var(--primary); }
            .stat-label { font-size:0.9rem; color:var(--text-muted); margin-top:0.5rem; }
            .channel-description { background:var(--bg); padding:1.5rem; border-radius:8px; }
            @media (max-width:768px) {
                .channel-profile { flex-direction:column; text-align:center; }
                .channel-stats-grid { grid-template-columns:repeat(2,1fr); }
            }
        </style>
        HTML;
    }

    #[\Override]
    public function read(): string { return $this->list(); }

    #[\Override]
    public function create(): string { return $this->list(); }

    #[\Override]
    public function update(): string { return $this->list(); }

    #[\Override]
    public function delete(): string { return $this->list(); }
}
