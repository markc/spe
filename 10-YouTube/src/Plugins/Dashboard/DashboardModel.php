<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Dashboard;

use SPE\YouTube\Core\{Ctx, Plugin, Util};
use SPE\YouTube\Services\YouTubeService;

/**
 * Dashboard plugin - Channel overview and stats
 */
final class DashboardModel extends Plugin
{
    private YouTubeService $youtube;

    public function __construct(protected Ctx $ctx)
    {
        parent::__construct($ctx);
        $this->youtube = new YouTubeService();
    }

    #[\Override]
    public function list(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $channel = $this->youtube->getChannel();
        $videos = $this->youtube->listVideos(6);
        $playlists = $this->youtube->listPlaylists(6);

        return [
            'channel' => $channel,
            'recentVideos' => $videos,
            'playlists' => $playlists,
            'stats' => [
                'totalVideos' => $channel?->videoCount ?? 0,
                'totalViews' => $channel?->viewCount ?? 0,
                'subscribers' => $channel?->subscriberCount ?? 0,
                'playlistCount' => count($playlists),
            ],
        ];
    }

    #[\Override]
    public function read(): array
    {
        return $this->list();
    }

    #[\Override]
    public function create(): array
    {
        return $this->list();
    }

    #[\Override]
    public function update(): array
    {
        return $this->list();
    }

    #[\Override]
    public function delete(): array
    {
        return $this->list();
    }
}
