<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace Tests\Support;

/**
 * Runs one chapter on PHP's built-in server and talks to it over HTTP.
 * Each chapter gets its own process, so class names never collide between chapters.
 */
final class Chapter
{
    /** @var array<string, self> */
    private static array $running = [];

    /** @var resource */
    private $proc;
    private array $cookies = [];

    private function __construct(public readonly string $dir, public readonly int $port)
    {
        $root = dirname(__DIR__, 2);
        $this->proc = proc_open(
            ['php', '-S', "127.0.0.1:$port", '-t', "$root/$dir/public"],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        set_error_handler(static fn(): bool => true);
        try {
            for ($i = 0; $i < 50; $i++) {
                if ($sock = fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1)) {
                    fclose($sock);
                    return;
                }
                usleep(100_000);
            }
        } finally {
            restore_error_handler();
        }
        throw new \RuntimeException("Chapter $dir did not start on port $port");
    }

    public static function start(string $dir): self
    {
        return self::$running[$dir] ??= new self($dir, 8100 + (int) substr($dir, 0, 2));
    }

    public static function stopAll(): void
    {
        foreach (self::$running as $c) {
            proc_terminate($c->proc);
        }
        self::$running = [];
    }

    public function get(string $query = ''): Response
    {
        return $this->request('GET', $query);
    }

    public function post(string $query, array $data): Response
    {
        return $this->request('POST', $query, $data);
    }

    public function forget(): void
    {
        $this->cookies = [];
    }

    private function request(string $method, string $query, array $data = []): Response
    {
        $headers = $this->cookies ? ['Cookie: ' . implode('; ', $this->cookies)] : [];
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $ctx = stream_context_create(['http' => [
            'method' => $method,
            'header' => $headers,
            'content' => http_build_query($data),
            'ignore_errors' => true,
            'follow_location' => 0,
        ]]);
        $body = (string) file_get_contents("http://127.0.0.1:{$this->port}/$query", false, $ctx);
        $response = new Response($http_response_header, $body);
        foreach ($response->headers as $h) {
            if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $h, $m)) {
                $this->cookies[strtok($m[1], '=')] = $m[1];
            }
        }
        return $response;
    }
}
