<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

/**
 * Immutable Channel data transfer object
 */
readonly class ChannelDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $thumbnail,
        public string $customUrl,
        public int $subscriberCount,
        public int $videoCount,
        public int $viewCount,
        public string $publishedAt = '',
    ) {}

    public static function fromApi(array $data): self
    {
        $snippet = $data['snippet'] ?? [];
        $stats = $data['statistics'] ?? [];

        $thumbnails = $snippet['thumbnails'] ?? [];
        $thumbnail = $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? $thumbnails['default']['url'] ?? '';

        return new self(
            id: $data['id'] ?? '',
            title: $snippet['title'] ?? 'Unknown',
            description: $snippet['description'] ?? '',
            thumbnail: $thumbnail,
            customUrl: $snippet['customUrl'] ?? '',
            subscriberCount: (int) ($stats['subscriberCount'] ?? 0),
            videoCount: (int) ($stats['videoCount'] ?? 0),
            viewCount: (int) ($stats['viewCount'] ?? 0),
            publishedAt: $snippet['publishedAt'] ?? '',
        );
    }

    public function url(): string
    {
        return $this->customUrl
            ? "https://www.youtube.com/{$this->customUrl}"
            : "https://www.youtube.com/channel/{$this->id}";
    }

    public function formattedSubscribers(): string
    {
        return $this->subscriberCount |> $this->formatNumber(...);
    }

    public function formattedViews(): string
    {
        return $this->viewCount |> $this->formatNumber(...);
    }

    private function formatNumber(int $num): string
    {
        return match (true) {
            $num >= 1_000_000_000 => round($num / 1_000_000_000, 1) . 'B',
            $num >= 1_000_000 => round($num / 1_000_000, 1) . 'M',
            $num >= 1_000 => round($num / 1_000, 1) . 'K',
            default => (string) $num,
        };
    }
}
