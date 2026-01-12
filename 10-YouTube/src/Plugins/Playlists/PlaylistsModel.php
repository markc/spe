<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Playlists;

use SPE\App\Util;
use SPE\YouTube\Core\Ctx;
use SPE\YouTube\Core\Plugin;
use SPE\YouTube\Services\Privacy;
use SPE\YouTube\Services\YouTubeException;
use SPE\YouTube\Services\YouTubeService;

/**
 * Playlists plugin - CRUD for YouTube playlists
 */
final class PlaylistsModel extends Plugin
{
    private YouTubeService $youtube;

    public function __construct(
        protected Ctx $ctx,
    ) {
        parent::__construct($ctx);
        $this->youtube = new YouTubeService();
    }

    /**
     * List all playlists
     */
    #[\Override]
    public function list(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $playlists = $this->youtube->listPlaylists(50);

        return [
            'playlists' => $playlists,
            'count' => count($playlists),
        ];
    }

    /**
     * View playlist with videos
     */
    #[\Override]
    public function read(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $id = $_GET['id'] ?? '';

        if (empty($id)) {
            Util::log('No playlist ID provided');
            Util::redirect('?o=Playlists');
        }

        // Get playlist info from list
        $playlists = $this->youtube->listPlaylists(50);
        $playlist = null;
        foreach ($playlists as $pl) {
            if ($pl->id !== $id) {
                continue;
            }

            $playlist = $pl;
            break;
        }

        if (!$playlist) {
            Util::log('Playlist not found');
            Util::redirect('?o=Playlists');
        }

        // Get videos in playlist
        $videos = $this->youtube->getPlaylistVideos($id);

        return [
            'playlist' => $playlist,
            'videos' => $videos,
        ];
    }

    /**
     * Create new playlist
     */
    #[\Override]
    public function create(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        if (Util::is_post()) {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $privacy = Privacy::fromString($_POST['privacy'] ?? 'public');

            if (empty($title)) {
                Util::log('Playlist title is required');
                return ['privacyOptions' => Privacy::cases()];
            }

            try {
                $playlistId = $this->youtube->createPlaylist($title, $description, $privacy);

                if ($playlistId) {
                    Util::log("Playlist created: $title", 'success');
                    Util::redirect("?o=Playlists&m=read&id=$playlistId");
                }
            } catch (YouTubeException $e) {
                Util::log("Failed to create playlist: {$e->getMessage()}");
            }
        }

        return ['privacyOptions' => Privacy::cases()];
    }

    /**
     * Delete playlist
     */
    #[\Override]
    public function delete(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $id = $_GET['id'] ?? '';

        if (empty($id)) {
            Util::log('No playlist ID provided');
            Util::redirect('?o=Playlists');
        }

        try {
            $this->youtube->deletePlaylist($id);
            Util::log('Playlist deleted', 'success');
        } catch (YouTubeException $e) {
            Util::log("Failed to delete playlist: {$e->getMessage()}");
        }

        Util::redirect('?o=Playlists');

        return [];
    }

    /**
     * Add video to playlist
     */
    #[\Override]
    public function update(): array
    {
        if (!$this->youtube->authenticate()) {
            Util::redirect('?o=Auth');
        }

        $playlistId = $_GET['id'] ?? '';
        $videoId = $_POST['video_id'] ?? $_GET['video_id'] ?? '';

        if (empty($playlistId) || empty($videoId)) {
            Util::log('Missing playlist or video ID');
            Util::redirect('?o=Playlists');
        }

        try {
            $this->youtube->addToPlaylist($playlistId, $videoId);
            Util::log('Video added to playlist', 'success');
        } catch (YouTubeException $e) {
            Util::log("Failed to add video: {$e->getMessage()}");
        }

        Util::redirect("?o=Playlists&m=read&id=$playlistId");

        return [];
    }
}
