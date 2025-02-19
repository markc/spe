<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250219
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\PDO\Plugins\News;

use SPE\PDO\Core\{Ctx, Theme, Util};

final class NewsView extends Theme
{
    public function __construct(
        private Ctx $ctx
    )
    {
        Util::elog(__METHOD__);
    }

    public function create(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            return ''; // Return empty for AJAX POST requests
        }
        return $this->modalForm();
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->ary, EXTR_SKIP);
        return '
        <div class="container py-4">
            <article class="card shadow-sm mx-auto" style="max-width: 800px;">
                <div class="card-body">
                    <h1 class="card-title h2 mb-3">' . $title . '</h1>
                    <div class="text-muted small mb-4">
                        <span class="me-3">By ' . $author . '</span>
                        <span class="me-3">Published: ' . $created . '</span>
                        <span>Last updated: ' . $updated . '</span>
                    </div>
                    <div class="card-text mb-4">
                        ' . Util::nlbr($content) . '
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <div>
                            <small class="text-muted">Post ID: ' . $id . '</small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                onclick="document.getElementById(\'confirmDelete\').setAttribute(\'data-post-id\', \'' . $id . '\')">
                                Delete
                            </button>
                            <a href="?o=News&m=list" class="btn btn-secondary me-2">Cancel</a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                                Edit
                            </button>
                        </div>
                        ' . $this->modalForm(['id' => $id, 'title' => $title, 'content' => $content]) . '
                        ' . $this->deleteModal() . '
                    </div>
                </div>
            </article>
        </div>' . $this->formScript();
    }

    public function update(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            return ''; // Return empty for AJAX POST requests
        }
        return $this->modalForm($this->ctx->ary);
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->ary, EXTR_SKIP);

        $html = '<div class="container py-4">';

        // Add Create New button and search bar
        $html .= '
        <div class="d-flex justify-content-between align-items-center mb-4">
            <form class="d-flex" style="width: 300px;" method="get">
                <input type="hidden" name="o" value="News">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" placeholder="Search articles..." 
                        value="' . htmlspecialchars($_GET['q'] ?? '') . '">
                    <button class="btn btn-outline-secondary" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                    </button>' .
            (isset($_GET['q']) && $_GET['q'] !== '' ? '
                    <a href="?o=News" class="btn btn-outline-secondary" title="Clear search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </a>' : '') . '
                </div>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">Create New Article</button>
            ' . $this->modalForm() . '
        </div>';

        // Display news items in a 3-column grid
        $html .= '<div class="row row-cols-1 row-cols-md-3 g-4">';
        foreach ($items as $item)
        {
            $html .= '
            <div class="col">
                <article class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 card-title">
                            <a href="?o=News&m=read&id=' . $item['id'] . '" class="text-decoration-none">' .
                htmlspecialchars($item['title']) . '</a>
                        </h2>
                        <div class="text-muted small mb-3 d-flex flex-column">
                            <span>Published: ' . $item['created'] . '</span>
                            <span>Last updated: ' . $item['updated'] . '</span>
                        </div>
                        <div class="card-text mb-3 flex-grow-1">
                            ' . substr(strip_tags($item['content']), 0, 150) . '...
                        </div>
                        <a href="?o=News&m=read&id=' . $item['id'] . '" class="btn btn-primary btn-sm">Read more</a>
                    </div>
                </article>
            </div>';
        }
        $html .= '</div>';

        // Pagination container
        $html .= '<div class="d-flex justify-content-between align-items-center mt-4">';

        // Post count
        $startId = (($pagination['page'] - 1) * count($items)) + 1;
        $endId = $startId + count($items) - 1;
        $html .= '<div class="text-muted">Showing posts ' . $startId . '-' . $endId . ' of ' . $pagination['total'] . ' posts</div>';

        // Pagination controls
        if ($pagination['pages'] > 1)
        {
            $html .= '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

            // Get search query if exists
            $searchQuery = isset($_GET['q']) ? '&q=' . htmlspecialchars($_GET['q']) : '';

            // Previous page
            if ($pagination['page'] > 1)
            {
                $html .= '<li class="page-item">
                    <a class="page-link" href="?o=News&m=list&page=' . ($pagination['page'] - 1) . $searchQuery . '">&laquo; Previous</a>
                </li>';
            }

            // Page numbers
            for ($i = 1; $i <= $pagination['pages']; $i++)
            {
                if ($i == $pagination['page'])
                {
                    $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                }
                else
                {
                    $html .= '<li class="page-item"><a class="page-link" href="?o=News&m=list&page=' . $i . $searchQuery . '">' . $i . '</a></li>';
                }
            }

            // Next page
            if ($pagination['page'] < $pagination['pages'])
            {
                $html .= '<li class="page-item">
                    <a class="page-link" href="?o=News&m=list&page=' . ($pagination['page'] + 1) . $searchQuery . '">Next &raquo;</a>
                </li>';
            }

            $html .= '</ul></nav>';
        }

        $html .= '</div></div>' . $this->formScript();
        return $html;
    }

    private function deleteModal(): string
    {
        return '
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this news item?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmDelete">OK</button>
                    </div>
                </div>
            </div>
        </div>';
    }

    private function modalForm(array $data = []): string
    {
        $id = $data['id'] ?? 0;
        $title = htmlspecialchars($data['title'] ?? '');
        $content = htmlspecialchars($data['content'] ?? '');
        $action = $id ? "?o=News&m=update&id=$id" : "?o=News&m=create";
        $heading = $id ? 'Edit News Item' : 'Create News Item';
        $modalId = $id ? "editModal" : "createModal";

        return '
        <div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="' . $modalId . 'Label">' . $heading . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="news-form" method="post" action="' . $action . '">
                        <input type="hidden" name="id" value="' . $id . '">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title:</label>
                                <input type="text" id="title" name="title" value="' . $title . '" required class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content:</label>
                                <textarea id="content" name="content" required class="form-control" rows="10">' . $content . '</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">' . ($id ? 'Update' : 'Create') . '</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    }

    private function formScript(): string
    {
        return '
        <script>
        // Handle delete confirmation
        document.addEventListener("DOMContentLoaded", function() {
            const deleteModal = document.getElementById("deleteModal");
            if (deleteModal) {
                const confirmBtn = deleteModal.querySelector("#confirmDelete");
                if (confirmBtn) {
                    confirmBtn.addEventListener("click", function() {
                        const postId = this.getAttribute("data-post-id");
                        if (postId) {
                            fetch("?o=News&m=delete&i=" + postId, {
                                method: "POST"
                            })
                            .then(() => {
                                showToast("News item deleted successfully", "success");
                                setTimeout(() => {
                                    window.location.href = "?o=News&m=list";
                                }, 1000);
                            })
                            .catch(error => console.error("Error:", error));
                        }
                    });
                }
            }
        });

        // Just handle modal closing and toast message
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".news-form").forEach(form => {
                form.addEventListener("submit", function() {
                    // Find and hide the modal
                    const modalEl = this.closest(".modal");
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    // Show toast message
                    const isUpdate = this.action.includes("m=update");
                    showToast(
                        isUpdate ? "News item updated successfully" : "News item created successfully",
                        "success"
                    );
                });
            });
        });
        </script>';
    }
}
