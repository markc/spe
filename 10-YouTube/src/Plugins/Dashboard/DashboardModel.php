<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Dashboard;

use SPE\App\Util;
use SPE\YouTube\Core\{Ctx, Plugin};
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
        $videos = $this->youtube->listVideos(50); // Fetch more to get actual count
        $playlists = $this->youtube->listPlaylists(6);

        return [
            'channel' => $channel,
            'recentVideos' => array_slice($videos, 0, 6), // Display only 6
            'playlists' => $playlists,
            'stats' => [
                // Use actual count (API stats can be delayed by hours/days)
                'totalVideos' => count($videos),
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
