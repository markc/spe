<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Channel;

use SPE\YouTube\Core\{Ctx, Plugin, Util};
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

        return ['channel' => $this->youtube->getChannel()];
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
