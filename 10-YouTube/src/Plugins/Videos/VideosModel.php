<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Videos;

use SPE\App\Util;
use SPE\YouTube\Core\Ctx;
use SPE\YouTube\Core\Plugin;
use SPE\YouTube\Services\Privacy;
use SPE\YouTube\Services\YouTubeService;

/**
 * Videos plugin - List and view uploaded videos
 */
final class VideosModel extends Plugin
{
    private YouTubeService $youtube;

    public function __construct(
        protected Ctx $ctx,
    ) {
        parent::__construct($ctx);
        $this->youtube = new YouTubeService();
    }

    /**
     * List all videos
     */
    #[\Override]
    public function list(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $videos = $this->youtube->listVideos(50);

        return [
            'videos' => $videos,
            'count' => count($videos),
        ];
    }

    /**
     * View single video details
     */
    #[\Override]
    public function read(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $id = $_GET['id'] ?? '';

        if (empty($id)) {
            Util::log('No video ID provided');
            Util::redirect('?o=Videos');
        }

        $video = $this->youtube->getVideo($id);

        if (!$video) {
            Util::log('Video not found');
            Util::redirect('?o=Videos');
        }

        return ['video' => $video];
    }

    /**
     * Upload form / handle upload
     */
    #[\Override]
    public function create(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        if (Util::is_post()) {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $privacy = Privacy::fromString($_POST['privacy'] ?? 'private');

            if (empty($_FILES['video']['tmp_name'])) {
                Util::log('No video file uploaded');
                return ['error' => 'Please select a video file'];
            }

            $tmpFile = $_FILES['video']['tmp_name'];

            try {
                $videoId = $this->youtube->uploadVideo(
                    filePath: $tmpFile,
                    title: $title,
                    description: $description,
                    privacy: $privacy,
                    tags: ['SPE', 'PHP'],
                );

                if ($videoId) {
                    Util::log("Video uploaded: $videoId", 'success');
                    Util::redirect("?o=Videos&m=read&id=$videoId");
                }
            } catch (\Exception $e) {
                Util::log("Upload failed: {$e->getMessage()}");
                return ['error' => $e->getMessage()];
            }
        }

        return [
            'privacyOptions' => Privacy::cases(),
        ];
    }

    #[\Override]
    public function update(): array
    {
        return $this->read();
    }

    #[\Override]
    public function delete(): array
    {
        return $this->list();
    }
}
