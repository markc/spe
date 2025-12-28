<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\YouTube\Plugins\Videos;

use SPE\YouTube\Core\{ Theme};
use SPE\YouTube\Services\{Privacy, YouTubeService};

/**
 * Videos view - Video grid and detail UI
 */
final class VideosView extends Theme
{
    #[\Override]
    public function list(): string
    {
        $videos = $this->ctx->ary['videos'] ?? [];
        $count = $this->ctx->ary['count'] ?? 0;

        $grid = $this->renderVideoGrid($videos);

        return <<<HTML
        <div class="card">
            <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:1.5rem">
                <h1 style="margin:0">📹 Videos <small class="text-muted">($count)</small></h1>
                <a href="?o=Videos&m=create" class="btn">+ Upload Video</a>
            </div>
            $grid
        </div>
        <style>
            .video-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
            .video-card { background:var(--card-bg); border:1px solid var(--border); border-radius:8px; overflow:hidden; transition:transform 0.2s,box-shadow 0.2s; }
            .video-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.1); }
            .video-card img { width:100%; aspect-ratio:16/9; object-fit:cover; }
            .video-card-body { padding:1rem; }
            .video-card h3 { margin:0 0 0.5rem; font-size:1rem; line-height:1.3; }
            .video-card-meta { font-size:0.85rem; color:var(--text-muted); }
            .video-duration { position:absolute; bottom:8px; right:8px; background:rgba(0,0,0,0.8); color:#fff; padding:2px 6px; border-radius:3px; font-size:0.75rem; }
            .video-thumb-wrapper { position:relative; }
        </style>
        HTML;
    }

    private function renderVideoGrid(array $videos): string
    {
        if (empty($videos)) {
            return '<p class="text-muted">No videos uploaded yet.</p>';
        }

        $html = '<div class="video-grid">';

        foreach ($videos as $video) {
            $title = htmlspecialchars($video->title);
            $shortTitle = mb_strlen($title) > 50 ? mb_substr($title, 0, 50) . '...' : $title;
            $thumb = htmlspecialchars($video->thumbnail);
            $views = number_format($video->viewCount);
            $date = $video->formattedDate();
            $duration = YouTubeService::formatDuration($video->duration);
            $privacy = $video->privacy->label();

            $html .= <<<HTML
            <a href="?o=Videos&m=read&id={$video->id}" class="video-card" style="text-decoration:none;color:inherit">
                <div class="video-thumb-wrapper">
                    <img src="$thumb" alt="$title">
                    <span class="video-duration">$duration</span>
                </div>
                <div class="video-card-body">
                    <h3 title="$title">$shortTitle</h3>
                    <div class="video-card-meta">
                        $views views • $date<br>
                        $privacy
                    </div>
                </div>
            </a>
            HTML;
        }

        return $html . '</div>';
    }

    #[\Override]
    public function read(): string
    {
        $video = $this->ctx->ary['video'] ?? null;

        if (!$video) {
            return '<div class="card"><p>Video not found.</p></div>';
        }

        $title = htmlspecialchars($video->title);
        $description = nl2br(htmlspecialchars($video->description));
        $views = number_format($video->viewCount);
        $likes = number_format($video->likeCount);
        $comments = number_format($video->commentCount);
        $date = $video->formattedDate();
        $privacy = $video->privacy->label();
        $duration = YouTubeService::formatDuration($video->duration);
        $embedUrl = htmlspecialchars($video->embedUrl());
        $ytUrl = htmlspecialchars($video->url());

        return <<<HTML
        <div class="card">
            <a href="?o=Videos" class="btn btn-sm mb-2">← Back to Videos</a>

            <div class="video-embed mb-2">
                <iframe src="$embedUrl" frameborder="0" allowfullscreen
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                </iframe>
            </div>

            <h1>$title</h1>

            <div class="video-stats flex mb-2" style="gap:2rem">
                <span>👁️ $views views</span>
                <span>👍 $likes likes</span>
                <span>💬 $comments comments</span>
                <span>⏱️ $duration</span>
                <span>$privacy</span>
            </div>

            <p class="text-muted">Published: $date</p>

            <div class="video-description mt-2">
                <h3>Description</h3>
                <p>$description</p>
            </div>

            <div class="mt-2">
                <a href="$ytUrl" target="_blank" class="btn">Watch on YouTube →</a>
            </div>
        </div>
        <style>
            .video-embed { position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px; }
            .video-embed iframe { position:absolute; top:0; left:0; width:100%; height:100%; }
            .video-stats { flex-wrap:wrap; color:var(--text-muted); }
            .video-description { background:var(--bg); padding:1rem; border-radius:8px; }
        </style>
        HTML;
    }

    #[\Override]
    public function create(): string
    {
        $privacyOptions = $this->ctx->ary['privacyOptions'] ?? Privacy::cases();
        $error = $this->ctx->ary['error'] ?? '';

        $errorHtml = $error ? "<div class=\"alert alert-danger\">$error</div>" : '';

        $optionsHtml = '';
        foreach ($privacyOptions as $p) {
            $selected = $p === Privacy::Private ? ' selected' : '';
            $optionsHtml .= "<option value=\"{$p->value}\"$selected>{$p->label()}</option>";
        }

        return <<<HTML
        <div class="card" style="max-width:600px;margin:0 auto">
            <h1>📤 Upload Video</h1>
            $errorHtml
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="video">Video File</label>
                    <input type="file" id="video" name="video" accept="video/*" required>
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="privacy">Privacy</label>
                    <select id="privacy" name="privacy">
                        $optionsHtml
                    </select>
                </div>
                <div class="flex" style="justify-content:space-between">
                    <a href="?o=Videos" class="btn btn-muted">Cancel</a>
                    <button type="submit" class="btn">Upload</button>
                </div>
            </form>
            <p class="text-muted mt-2" style="font-size:0.85rem">
                Note: Large files may take a while to upload. Maximum file size depends on your PHP configuration.
            </p>
        </div>
        HTML;
    }

    #[\Override]
    public function update(): string { return $this->read(); }

    #[\Override]
    public function delete(): string { return $this->list(); }
}
