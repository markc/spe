<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Services;

/**
 * Custom exception for YouTube API errors
 */
class YouTubeException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $apiError = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
