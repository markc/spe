<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

use Google\Client;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube as GoogleYouTube;
use Google\Service\YouTube\Playlist;
use Google\Service\YouTube\PlaylistItem;
use Google\Service\YouTube\PlaylistItemSnippet;
use Google\Service\YouTube\PlaylistSnippet;
use Google\Service\YouTube\PlaylistStatus;
use Google\Service\YouTube\ResourceId;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;

/**
 * YouTube API Service - Shared between CLI and Web
 *
 * PHP 8.5 features: pipe operator, typed constants, readonly properties,
 * first-class callables, match expressions, constructor promotion
 */
final class YouTubeService
{
    private const string CONFIG_DIR = '/.config/google/';
    private const string CLIENT_SECRET = 'client_secret.json';
    private const string TOKEN_FILE = 'youtube_token.json';
    private const string CATEGORY_EDUCATION = '27';

    private const array SCOPES = [
        GoogleYouTube::YOUTUBE_UPLOAD,
        GoogleYouTube::YOUTUBE_READONLY,
        GoogleYouTube::YOUTUBE,
    ];

    private ?GoogleYouTube $service = null;

    public readonly string $configPath;
    public readonly string $clientSecretPath;
    public readonly string $tokenPath;

    public function __construct(
        private Client $client = new Client(),
        ?string $configDir = null,
    ) {
        $home = getenv('HOME') ?: $_SERVER['HOME'] ?? '';
        $this->configPath = $configDir ?? $home . self::CONFIG_DIR;
        $this->clientSecretPath = $this->configPath . self::CLIENT_SECRET;
        $this->tokenPath = $this->configPath . self::TOKEN_FILE;

        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        if (!file_exists($this->clientSecretPath)) {
            throw new YouTubeException("Client secret not found: {$this->clientSecretPath}");
        }

        $this->client->setApplicationName('SPE YouTube Manager');
        $this->client->setScopes(self::SCOPES);
        $this->client->setAuthConfig($this->clientSecretPath);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    // ========== Authentication ==========

    public function isAuthenticated(): bool
    {
        return file_exists($this->tokenPath) && !$this->client->isAccessTokenExpired();
    }

    public function authenticate(): bool
    {
        if (!file_exists($this->tokenPath)) {
            return false;
        }

        $this->tokenPath
            |> file_get_contents(...)
            |> (static fn($json) => json_decode($json, true))
            |> $this->client->setAccessToken(...);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->saveToken();
                return true;
            }
            return false;
        }

        return true;
    }

    public function getAuthUrl(?string $redirectUri = null): string
    {
        if ($redirectUri) {
            $this->client->setRedirectUri($redirectUri);
        }
        return $this->client->createAuthUrl();
    }

    public function handleAuthCallback(string $code): bool
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new YouTubeException($token['error_description'] ?? 'Auth failed');
            }

            $this->client->setAccessToken($token);
            $this->saveToken();
            return true;
        } catch (\Exception $e) {
            throw new YouTubeException("Authentication failed: {$e->getMessage()}");
        }
    }

    private function saveToken(): void
    {
        $token = $this->client->getAccessToken();
        if ($token) {
            $this->tokenPath |> (static fn($path) => file_put_contents($path, json_encode($token, JSON_PRETTY_PRINT)));
            chmod($this->tokenPath, 0o600);
        }
    }

    public function revokeToken(): void
    {
        if (file_exists($this->tokenPath)) {
            unlink($this->tokenPath);
        }
    }

    // ========== Service Access ==========

    private function getService(): GoogleYouTube
    {
        return $this->service ??= new GoogleYouTube($this->client);
    }

    // ========== Channel ==========

    public function getChannel(): ?ChannelDTO
    {
        $response = $this->getService()->channels->listChannels('snippet,statistics', ['mine' => true]);

        $items = $response->getItems();
        if (empty($items)) {
            return null;
        }

        return $items[0]
            |> (static fn($ch) => [
                'id' => $ch->getId(),
                'snippet' => $ch->getSnippet(),
                'statistics' => $ch->getStatistics(),
            ])
            |> (fn($data) => [
                'id' => $data['id'],
                'snippet' => [
                    'title' => $data['snippet']->getTitle(),
                    'description' => $data['snippet']->getDescription(),
                    'customUrl' => $data['snippet']->getCustomUrl(),
                    'publishedAt' => $data['snippet']->getPublishedAt(),
                    'thumbnails' => $this->extractThumbnails($data['snippet']->getThumbnails()),
                ],
                'statistics' => [
                    'subscriberCount' => $data['statistics']->getSubscriberCount(),
                    'videoCount' => $data['statistics']->getVideoCount(),
                    'viewCount' => $data['statistics']->getViewCount(),
                ],
            ])
            |> ChannelDTO::fromApi(...);
    }

    // ========== Videos ==========

    /**
     * @return VideoDTO[]
     */
    public function listVideos(int $maxResults = 25): array
    {
        $channelResponse = $this->getService()->channels->listChannels('contentDetails', ['mine' => true]);

        $items = $channelResponse->getItems();
        if (empty($items)) {
            return [];
        }

        $uploadsPlaylistId = $items[0]->getContentDetails()->getRelatedPlaylists()->getUploads();

        $playlistResponse = $this->getService()->playlistItems->listPlaylistItems('snippet,contentDetails', [
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => $maxResults,
        ]);

        return $playlistResponse->getItems()
            |> (fn($items) => array_map(fn($item) => $this->fetchVideoDetails(
                $item->getSnippet()->getResourceId()->getVideoId(),
            ), $items))
            |> array_filter(...);
    }

    public function getVideo(string $videoId): ?VideoDTO
    {
        return $this->fetchVideoDetails($videoId);
    }

    private function fetchVideoDetails(string $videoId): ?VideoDTO
    {
        $response = $this->getService()->videos->listVideos('snippet,statistics,status,contentDetails', [
            'id' => $videoId,
        ]);

        $items = $response->getItems();
        if (empty($items)) {
            return null;
        }

        $video = $items[0];
        return [
            'id' => $video->getId(),
            'snippet' => [
                'title' => $video->getSnippet()->getTitle(),
                'description' => $video->getSnippet()->getDescription(),
                'publishedAt' => $video->getSnippet()->getPublishedAt(),
                'thumbnails' => $this->extractThumbnails($video->getSnippet()->getThumbnails()),
            ],
            'statistics' => [
                'viewCount' => $video->getStatistics()->getViewCount(),
                'likeCount' => $video->getStatistics()->getLikeCount(),
                'commentCount' => $video->getStatistics()->getCommentCount(),
            ],
            'status' => [
                'privacyStatus' => $video->getStatus()->getPrivacyStatus(),
            ],
            'contentDetails' => [
                'duration' => $video->getContentDetails()->getDuration(),
            ],
        ]
            |> VideoDTO::fromApi(...);
    }

    public function uploadVideo(
        string $filePath,
        string $title,
        string $description = '',
        Privacy $privacy = Privacy::Private,
        array $tags = [],
        ?callable $progressCallback = null,
    ): ?string {
        if (!file_exists($filePath)) {
            throw new YouTubeException("Video file not found: $filePath");
        }

        $snippet = new VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);
        $snippet->setTags($tags);
        $snippet->setCategoryId(self::CATEGORY_EDUCATION);

        $status = new VideoStatus();
        $status->setPrivacyStatus($privacy->value);

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        $fileSize = filesize($filePath);
        $chunkSize = 5 * 1024 * 1024; // 5MB

        $this->client->setDefer(true);

        $request = $this->getService()->videos->insert('snippet,status', $video);

        $media = new MediaFileUpload($this->client, $request, 'video/*', null, true, $chunkSize);
        $media->setFileSize($fileSize);

        $handle = fopen($filePath, 'rb');
        $response = false;
        $uploaded = 0;

        while (!$response && !feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $response = $media->nextChunk($chunk);
            $uploaded += strlen($chunk);

            if ($progressCallback) {
                $percent = (int) round(($uploaded / $fileSize) * 100);
                $progressCallback($percent, $uploaded, $fileSize);
            }
        }

        fclose($handle);
        $this->client->setDefer(false);

        return $response instanceof Video ? $response->getId() : null;
    }

    // ========== Playlists ==========

    /**
     * @return PlaylistDTO[]
     */
    public function listPlaylists(int $maxResults = 25): array
    {
        $response = $this->getService()->playlists->listPlaylists('snippet,contentDetails,status', [
            'mine' => true,
            'maxResults' => $maxResults,
        ]);

        return $response->getItems()
            |> (fn($items) => array_map(
                fn($pl) => [
                    'id' => $pl->getId(),
                    'snippet' => [
                        'title' => $pl->getSnippet()->getTitle(),
                        'description' => $pl->getSnippet()->getDescription(),
                        'publishedAt' => $pl->getSnippet()->getPublishedAt(),
                        'thumbnails' => $this->extractThumbnails($pl->getSnippet()->getThumbnails()),
                    ],
                    'contentDetails' => [
                        'itemCount' => $pl->getContentDetails()->getItemCount(),
                    ],
                    'status' => [
                        'privacyStatus' => $pl->getStatus()->getPrivacyStatus(),
                    ],
                ]
                    |> PlaylistDTO::fromApi(...),
                $items,
            ));
    }

    public function createPlaylist(string $title, string $description = '', Privacy $privacy = Privacy::Public): ?string
    {
        $snippet = new PlaylistSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);

        $status = new PlaylistStatus();
        $status->setPrivacyStatus($privacy->value);

        $playlist = new Playlist();
        $playlist->setSnippet($snippet);
        $playlist->setStatus($status);

        try {
            $response = $this->getService()->playlists->insert('snippet,status', $playlist);
            return $response->getId();
        } catch (\Exception $e) {
            throw new YouTubeException("Failed to create playlist: {$e->getMessage()}");
        }
    }

    public function deletePlaylist(string $playlistId): bool
    {
        try {
            $this->getService()->playlists->delete($playlistId);
            return true;
        } catch (\Exception $e) {
            throw new YouTubeException("Failed to delete playlist: {$e->getMessage()}");
        }
    }

    public function addToPlaylist(string $playlistId, string $videoId, ?int $position = null): bool
    {
        $resourceId = new ResourceId();
        $resourceId->setKind('youtube#video');
        $resourceId->setVideoId($videoId);

        $snippet = new PlaylistItemSnippet();
        $snippet->setPlaylistId($playlistId);
        $snippet->setResourceId($resourceId);

        if ($position !== null) {
            $snippet->setPosition($position);
        }

        $playlistItem = new PlaylistItem();
        $playlistItem->setSnippet($snippet);

        try {
            $this->getService()->playlistItems->insert('snippet', $playlistItem);
            return true;
        } catch (\Exception $e) {
            throw new YouTubeException("Failed to add video to playlist: {$e->getMessage()}");
        }
    }

    public function removeFromPlaylist(string $playlistItemId): bool
    {
        try {
            $this->getService()->playlistItems->delete($playlistItemId);
            return true;
        } catch (\Exception $e) {
            throw new YouTubeException("Failed to remove from playlist: {$e->getMessage()}");
        }
    }

    /**
     * @return VideoDTO[]
     */
    public function getPlaylistVideos(string $playlistId, int $maxResults = 50): array
    {
        $response = $this->getService()->playlistItems->listPlaylistItems('snippet,contentDetails', [
            'playlistId' => $playlistId,
            'maxResults' => $maxResults,
        ]);

        return $response->getItems()
            |> (fn($items) => array_map(fn($item) => $this->fetchVideoDetails(
                $item->getSnippet()->getResourceId()->getVideoId(),
            ), $items))
            |> array_filter(...);
    }

    // ========== Helpers ==========

    private function extractThumbnails(?object $thumbnails): array
    {
        if (!$thumbnails) {
            return [];
        }

        $result = [];
        foreach (['default', 'medium', 'high', 'standard', 'maxres'] as $size) {
            $getter = 'get' . ucfirst($size);
            $thumb = $thumbnails->$getter();
            if ($thumb) {
                $result[$size] = ['url' => $thumb->getUrl()];
            }
        }
        return $result;
    }

    public static function formatBytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => round($bytes / 1_073_741_824, 2) . ' GB',
            $bytes >= 1_048_576 => round($bytes / 1_048_576, 2) . ' MB',
            $bytes >= 1_024 => round($bytes / 1_024, 2) . ' KB',
            default => $bytes . ' B',
        };
    }

    public static function formatDuration(string $isoDuration): string
    {
        // PT1H2M3S -> 1:02:03
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $isoDuration, $matches);

        $hours = (int) ($matches[1] ?? 0);
        $minutes = (int) ($matches[2] ?? 0);
        $seconds = (int) ($matches[3] ?? 0);

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%d:%02d', $minutes, $seconds);
    }
}
