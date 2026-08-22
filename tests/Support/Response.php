<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace Tests\Support;

final readonly class Response
{
    public int $status;

    public function __construct(public array $headers, public string $body)
    {
        preg_match('#^HTTP/\S+ (\d{3})#', $headers[0] ?? '', $m);
        $this->status = (int) ($m[1] ?? 0);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $h) {
            if (stripos($h, "$name:") === 0) {
                return trim(substr($h, strlen($name) + 1));
            }
        }
        return null;
    }

    public function json(): array
    {
        return json_decode($this->body, true, flags: JSON_THROW_ON_ERROR);
    }

    /** Extracts the CSRF token from a rendered form. */
    public function csrf(): string
    {
        preg_match('/name="csrf" value="([0-9a-f]+)"/', $this->body, $m);
        return $m[1] ?? '';
    }
}
