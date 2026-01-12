<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

/**
 * Immutable Playlist data transfer object
 */
readonly class PlaylistDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $thumbnail,
        public int $itemCount,
        public Privacy $privacy,
        public string $publishedAt = '',
    ) {}

    public static function fromApi(array $data): self
    {
        $snippet = $data['snippet'] ?? [];
        $status = $data['status'] ?? [];
        $details = $data['contentDetails'] ?? [];

        $thumbnails = $snippet['thumbnails'] ?? [];
        $thumbnail = $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? $thumbnails['default']['url'] ?? '';

        return new self(
            id: $data['id'] ?? '',
            title: $snippet['title'] ?? 'Untitled',
            description: $snippet['description'] ?? '',
            thumbnail: $thumbnail,
            itemCount: (int) ($details['itemCount'] ?? 0),
            privacy: Privacy::fromString($status['privacyStatus'] ?? 'private'),
            publishedAt: $snippet['publishedAt'] ?? '',
        );
    }

    public function url(): string
    {
        return "https://www.youtube.com/playlist?list={$this->id}";
    }

    public function formattedDate(): string
    {
        return $this->publishedAt |> strtotime(...) |> (static fn($ts) => date('M j, Y', $ts));
    }
}
