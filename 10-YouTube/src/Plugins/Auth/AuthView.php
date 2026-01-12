<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Auth;

use SPE\YouTube\Core\Theme;

/**
 * Auth view - Google OAuth login UI
 */
final class AuthView extends Theme
{
    #[\Override]
    public function list(): string
    {
        $authUrl = htmlspecialchars($this->ctx->ary['authUrl'] ?? '#');

        return <<<HTML
        <div class="auth-container">
            <div class="card" style="max-width:400px;margin:4rem auto;text-align:center">
                <div class="youtube-logo">
                    <svg viewBox="0 0 90 20" width="120" style="margin:1rem auto">
                        <path fill="#ff0000" d="M27.9727 3.12324C27.6435 1.89323 26.6768 0.926623 25.4468 0.597366C23.2197 2.24288e-07 14.285 0 14.285 0C14.285 0 5.35042 2.24288e-07 3.12323 0.597366C1.89323 0.926623 0.926623 1.89323 0.597366 3.12324C2.24288e-07 5.35042 0 10 0 10C0 10 2.24288e-07 14.6496 0.597366 16.8768C0.926623 18.1068 1.89323 19.0734 3.12323 19.4026C5.35042 20 14.285 20 14.285 20C14.285 20 23.2197 20 25.4468 19.4026C26.6768 19.0734 27.6435 18.1068 27.9727 16.8768C28.5701 14.6496 28.5701 10 28.5701 10C28.5701 10 28.5677 5.35042 27.9727 3.12324Z"/>
                        <path fill="#fff" d="M11.4253 14.2854L18.8477 10.0004L11.4253 5.71533V14.2854Z"/>
                        <path fill="#282828" d="M34.6024 19.4644L35.1319 16.3352H39.0787V14.5765H35.348L35.8149 11.7564H40.0138V10H33.9178L32.1528 19.4644H34.6024ZM44.0044 19.4644V15.7231C44.0044 15.234 44.0794 14.8093 44.2295 14.449C44.3795 14.0887 44.6026 13.8101 44.8988 13.6132C45.1949 13.4163 45.5613 13.3179 45.998 13.3179C46.5027 13.3179 46.8841 13.4752 47.1427 13.7899C47.4013 14.1046 47.5306 14.5589 47.5306 15.1527V19.4644H50.0103V14.6396C50.0103 14.0117 49.9035 13.4709 49.6898 13.0171C49.4761 12.5634 49.1627 12.2152 48.7494 11.9725C48.3362 11.7299 47.8365 11.6086 47.2504 11.6086C46.6644 11.6086 46.158 11.7446 45.7315 12.0168C45.3049 12.2889 44.9968 12.6701 44.8068 13.1602L44.6342 11.7764H42.5247V19.4644H44.0044Z"/>
                    </svg>
                </div>
                <h2>YouTube Manager</h2>
                <p class="text-muted mb-3">Sign in with your Google account to manage your YouTube channel</p>
                <a href="$authUrl" class="btn btn-google">
                    <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right:8px">
                        <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                        <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                        <path fill="#FBBC05" d="M3.964 10.71c-.18-.54-.282-1.117-.282-1.71s.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.548 0 9s.348 2.827.957 4.042l3.007-2.332z"/>
                        <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
                    </svg>
                    Sign in with Google
                </a>
                <p class="text-muted mt-3" style="font-size:0.85rem">
                    This will request access to your YouTube account to list videos, manage playlists, and view channel statistics.
                </p>
            </div>
        </div>
        <style>
            .btn-google {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                color: #757575;
                border: 1px solid #ddd;
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
                font-weight: 500;
                border-radius: 4px;
                text-decoration: none;
                transition: box-shadow 0.2s;
            }
            .btn-google:hover {
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                color: #333;
            }
        </style>
        HTML;
    }

    #[\Override]
    public function create(): string
    {
        return ''; // Handled by redirect
    }

    #[\Override]
    public function read(): string
    {
        return '';
    }

    #[\Override]
    public function update(): string
    {
        return '';
    }

    #[\Override]
    public function delete(): string
    {
        return '';
    }
}
