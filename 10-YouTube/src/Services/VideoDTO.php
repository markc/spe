<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

/**
 * Immutable Video data transfer object
 * PHP 8.2 readonly class with constructor property promotion
 */
readonly class VideoDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $thumbnail,
        public string $publishedAt,
        public Privacy $privacy,
        public int $viewCount = 0,
        public int $likeCount = 0,
        public int $commentCount = 0,
        public string $duration = '',
    ) {}

    /**
     * Create from YouTube API response using pipe operator
     */
    public static function fromApi(array $data): self
    {
        $snippet = $data['snippet'] ?? [];
        $stats = $data['statistics'] ?? [];
        $status = $data['status'] ?? [];
        $details = $data['contentDetails'] ?? [];

        $thumbnails = $snippet['thumbnails'] ?? [];
        $thumbnail = $thumbnails['high']['url']
            ?? $thumbnails['medium']['url']
            ?? $thumbnails['default']['url']
            ?? '';

        return new self(
            id: $data['id'] ?? '',
            title: $snippet['title'] ?? 'Untitled',
            description: $snippet['description'] ?? '',
            thumbnail: $thumbnail,
            publishedAt: $snippet['publishedAt'] ?? '',
            privacy: Privacy::fromString($status['privacyStatus'] ?? 'private'),
            viewCount: (int)($stats['viewCount'] ?? 0),
            likeCount: (int)($stats['likeCount'] ?? 0),
            commentCount: (int)($stats['commentCount'] ?? 0),
            duration: $details['duration'] ?? '',
        );
    }

    public function url(): string
    {
        return "https://www.youtube.com/watch?v={$this->id}";
    }

    public function embedUrl(): string
    {
        return "https://www.youtube.com/embed/{$this->id}";
    }

    public function formattedDate(): string
    {
        return $this->publishedAt
            |> strtotime(...)
            |> (static fn($ts) => date('M j, Y', $ts));
    }

    public function shortDescription(int $length = 150): string
    {
        return $this->description
            |> strip_tags(...)
            |> trim(...)
            |> (static fn($s) => mb_strlen($s) > $length ? mb_substr($s, 0, $length) . '...' : $s);
    }
}
