<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Channel;

use SPE\App\Util;
use SPE\YouTube\Core\{Ctx, Plugin};
use SPE\YouTube\Services\YouTubeService;

/**
 * Channel plugin - View channel statistics
 */
final class ChannelModel extends Plugin
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

        // Get actual video count (API stats can be delayed)
        $videos = $this->youtube->listVideos(50);
        $actualVideoCount = count($videos);

        return [
            'channel' => $this->youtube->getChannel(),
            'actualVideoCount' => $actualVideoCount,
        ];
    }

    #[\Override]
    public function read(): array { return $this->list(); }

    #[\Override]
    public function create(): array { return $this->list(); }

    #[\Override]
    public function update(): array { return $this->list(); }

    #[\Override]
    public function delete(): array { return $this->list(); }
}
