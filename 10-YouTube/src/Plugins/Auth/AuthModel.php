<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Auth;

use SPE\App\Util;
use SPE\YouTube\Core\{Ctx, Plugin};
use SPE\YouTube\Services\{YouTubeService, YouTubeException};

/**
 * Google OAuth authentication plugin
 * Handles web-based OAuth flow for YouTube API
 */
final class AuthModel extends Plugin
{
    private YouTubeService $youtube;

    public function __construct(protected Ctx $ctx)
    {
        parent::__construct($ctx);
        $this->youtube = new YouTubeService();
    }

    /**
     * Show login page / initiate OAuth
     */
    #[\Override]
    public function list(): array
    {
        // If already authenticated in session, redirect to Dashboard
        if (!empty($_SESSION['authenticated'])) {
            Util::redirect('?o=Dashboard');
        }

        // Check if we have a valid token and can authenticate
        if ($this->youtube->authenticate()) {
            $_SESSION['authenticated'] = true;
            $channel = $this->youtube->getChannel();
            if ($channel) {
                $_SESSION['channel'] = [
                    'id' => $channel->id,
                    'title' => $channel->title,
                    'thumbnail' => $channel->thumbnail,
                ];
            }
            Util::redirect('?o=Dashboard');
        }

        return [
            'authUrl' => $this->youtube->getAuthUrl($this->getRedirectUri()),
            'authenticated' => false,
        ];
    }

    /**
     * Handle OAuth callback
     */
    #[\Override]
    public function create(): array
    {
        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            Util::log('No authorization code received');
            Util::redirect('?o=Auth');
        }

        try {
            $this->youtube->handleAuthCallback($code);
            $_SESSION['authenticated'] = true;

            $channel = $this->youtube->getChannel();
            if ($channel) {
                $_SESSION['channel'] = [
                    'id' => $channel->id,
                    'title' => $channel->title,
                    'thumbnail' => $channel->thumbnail,
                ];
            }

            Util::log('Successfully authenticated with YouTube', 'success');
            Util::redirect('?o=Dashboard');
        } catch (YouTubeException $e) {
            Util::log("Authentication failed: {$e->getMessage()}");
            Util::redirect('?o=Auth');
        }

        return [];
    }

    /**
     * Logout - revoke token
     */
    #[\Override]
    public function delete(): array
    {
        $this->youtube->revokeToken();
        unset($_SESSION['authenticated'], $_SESSION['channel']);

        Util::log('Logged out successfully', 'success');
        Util::redirect('?o=Auth');

        return [];
    }

    /**
     * Check auth status (API endpoint)
     */
    #[\Override]
    public function read(): array
    {
        return [
            'authenticated' => $this->youtube->isAuthenticated(),
            'channel' => $_SESSION['channel'] ?? null,
        ];
    }

    #[\Override]
    public function update(): array
    {
        return $this->list();
    }

    private function getRedirectUri(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8010';
        // When running standalone (port 8010), redirect to root
        $path = str_contains($host, ':8010') ? '/' : '/10-YouTube/public/';
        return "{$protocol}://{$host}{$path}?o=Auth&m=create";
    }
}
