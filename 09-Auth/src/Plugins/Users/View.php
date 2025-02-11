<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250211
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\Users;

use SPE\Auth\Core\Util;
use SPE\Auth\Themes\Base;

final class View extends Base
{
    /*
    public function foot(): string
    {
        return '<footer class="text-center">THE ALTERNATE NEWS FOOTER EXAMPLE</footer>';
    }
    */
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
                        Are you sure you want to delete this user?
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
        $id = $data['id'] ?? '';
        $grp = htmlspecialchars((string)($data['grp'] ?? '0'));
        $acl = htmlspecialchars((string)($data['acl'] ?? '0'));
        $login = htmlspecialchars($data['login'] ?? '');
        $fname = htmlspecialchars($data['fname'] ?? '');
        $lname = htmlspecialchars($data['lname'] ?? '');
        $altemail = htmlspecialchars($data['altemail'] ?? '');
        $webpw = htmlspecialchars($data['webpw'] ?? '');
        $otp = htmlspecialchars($data['otp'] ?? '');
        $otpttl = htmlspecialchars((string)($data['otpttl'] ?? '0'));
        $cookie = htmlspecialchars($data['cookie'] ?? '');
        $anote = htmlspecialchars($data['anote'] ?? '');

        $action = $id ? "?o=Auth&m=update&i=$id" : "?o=Auth&m=create";
        $heading = $id ? 'Edit User' : 'Create User';
        $modalId = $id ? "editModal" . $id : "createModal";

        return '
        <div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="' . $modalId . 'Label">' . $heading . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="users-form" method="post" action="' . $action . '">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="login-' . $modalId . $id . '" class="form-label">Login:</label>
                                <input type="text" id="login-' . $modalId . $id . '" name="login" value="' . $login . '" required class="form-control" autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <label for="fname-' . $modalId . $id . '" class="form-label">First Name:</label>
                                <input type="text" id="fname-' . $modalId . $id . '" name="fname" value="' . $fname . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="lname-' . $modalId . $id . '" class="form-label">Last Name:</label>
                                <input type="text" id="lname-' . $modalId . $id . '" name="lname" value="' . $lname . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="altemail-' . $modalId . $id . '" class="form-label">Alternate Email:</label>
                                <input type="email" id="altemail-' . $modalId . $id . '" name="altemail" value="' . $altemail . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="grp-' . $modalId . $id . '" class="form-label">Group:</label>
                                <input type="number" id="grp-' . $modalId . $id . '" name="grp" value="' . $grp . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="acl-' . $modalId . $id . '" class="form-label">ACL:</label>
                                <input type="number" id="acl-' . $modalId . $id . '" name="acl" value="' . $acl . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="webpw-' . $modalId . $id . '" class="form-label">Web Password:</label>
                                <input type="password" id="webpw-' . $modalId . $id . '" name="webpw" value="' . $webpw . '" class="form-control" autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label for="otp-' . $modalId . $id . '" class="form-label">OTP:</label>
                                <input type="text" id="otp-' . $modalId . $id . '" name="otp" value="' . $otp . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="otpttl-' . $modalId . $id . '" class="form-label">OTP TTL:</label>
                                <input type="number" id="otpttl-' . $modalId . $id . '" name="otpttl" value="' . $otpttl . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="cookie-' . $modalId . $id . '" class="form-label">Cookie:</label>
                                <input type="text" id="cookie-' . $modalId . $id . '" name="cookie" value="' . $cookie . '" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="anote-' . $modalId . $id . '" class="form-label">Admin Note:</label>
                                <textarea id="anote-' . $modalId . $id . '" name="anote" class="form-control" rows="3">' . $anote . '</textarea>
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
                            fetch("?o=Auth&m=delete&i=" + postId, {
                                method: "POST"
                            })
                            .then(() => {
                                showToast("User deleted successfully", "success");
                                setTimeout(() => {
                                    window.location.href = "?o=Auth&m=list";
                                }, 1000);
                            })
                            .catch(error => console.error("Error:", error));
                        }
                    });
                }
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Handle all users forms
            document.querySelectorAll(".users-form").forEach(form => {
                form.addEventListener("submit", function(e) {
                    e.preventDefault();
                    fetch(this.action, {
                        method: "POST",
                        body: new FormData(this)
                    })
                    .then(response => response.text())
                    .then(() => {
                        // Find the parent modal and hide it
                        const modalEl = this.closest(".modal");
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                        // Redirect back to users list
                        // Show appropriate toast message based on form action
                        const isUpdate = this.action.includes("m=update");
                        showToast(
                            isUpdate ? "User updated successfully" : "User created successfully",
                            "success"
                        );
                        // Delay redirect to allow toast to show
                        setTimeout(() => {
                            window.location.href = "?o=Auth&m=list";
                        }, 1000);
                    })
                    .catch(error => console.error("Error:", error));
                });
            });
        });
        </script>';
    }

    public function create(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            return ''; // Return empty for AJAX POST requests
        }
        return $this->modalForm();
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

        // Add Create New button and search
        $html .= '
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <button type="button" class="btn btn-primary w-100 w-sm-auto" style="max-width: 200px;" data-bs-toggle="modal" data-bs-target="#createModal">Create New User</button>
            <div class="d-flex align-items-center gap-2 w-100" style="max-width: 400px;">
                <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search...">
                <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5 entries</option>
                    <option value="10" selected>10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                    <option value="100">100 entries</option>
                </select>
            </div>
            ' . $this->modalForm() . '
        </div>';

        // Add search and entries per page functionality script
        $html .= '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("userSearch");
            const entriesSelect = document.getElementById("entriesPerPage");
            const table = document.querySelector("table");
            const rows = table.querySelectorAll("tbody tr");
            
            // Initialize entries per page from URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentEntries = urlParams.get("perpage") || "10";
            entriesSelect.value = currentEntries;
            
            searchInput.addEventListener("keyup", function() {
                const searchText = this.value.toLowerCase();
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? "" : "none";
                });
            });

            entriesSelect.addEventListener("change", function() {
                const entries = this.value;
                const url = new URL(window.location.href);
                const currentParams = new URLSearchParams(window.location.search);
                
                // Preserve only sort parameters
                const sort = currentParams.get("sort");
                const dir = currentParams.get("dir");
                if (sort) url.searchParams.set("sort", sort);
                if (dir) url.searchParams.set("dir", dir);
                
                url.searchParams.set("perpage", entries);
                // Only reset page if entries per page is changed to a smaller number
                const currentEntries = parseInt(currentParams.get("perpage") || "10");
                const newEntries = parseInt(entries);
                if (newEntries < currentEntries) {
                    url.searchParams.delete("p"); // Reset to first page when showing fewer entries
                } else {
                    const currentPage = currentParams.get("p");
                    if (currentPage) url.searchParams.set("p", currentPage);
                }
                window.location.href = url.toString();
            });
        });
        </script>';

        // Display users table with mobile-friendly scrolling
        $html .= '
        <div class="table-responsive-sm position-relative" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table table-striped table-hover" style="min-width: 800px;">
                <thead>
                    <tr>';

        // Get current parameters
        $currentSort = $_GET['sort'] ?? 'updated';
        $currentDir = strtoupper($_GET['dir'] ?? 'DESC');
        $currentPerPage = $_GET['perpage'] ?? $pagination['perPage'];

        // Helper function to generate sort URL
        $getSortUrl = function ($field) use ($currentSort, $currentDir, $currentPerPage)
        {
            $newDir = ($field === $currentSort && $currentDir === 'ASC') ? 'DESC' : 'ASC';
            return '?o=Auth&m=list&sort=' . $field . '&dir=' . $newDir . '&perpage=' . $currentPerPage;
        };

        // Helper function to get sort icon
        $getSortIcon = function ($field) use ($currentSort, $currentDir)
        {
            $upColor = ($field === $currentSort && $currentDir === 'ASC') ? '#212529' : '#dee2e6';
            $downColor = ($field === $currentSort && $currentDir === 'DESC') ? '#212529' : '#dee2e6';

            return '<span class="float-end" style="margin-left: 8px;">
                <i class="bi bi-triangle-fill" style="font-size: 8px; display: block; color: ' . $upColor . ';"></i>
                <i class="bi bi-triangle-fill" style="font-size: 8px; display: block; transform: rotate(180deg); color: ' . $downColor . ';"></i>
            </span>';
        };

        // Define sortable columns
        $columns = [
            'id' => 'ID',
            'login' => 'Login',
            'fname' => 'First&nbsp;Name',
            'lname' => 'Last&nbsp;Name',
            'created' => 'Created&nbsp;At',
            'updated' => 'Updated&nbsp;At'
        ];

        // Generate sortable column headers
        foreach ($columns as $field => $label)
        {
            $html .= '
                        <th style="white-space: nowrap; cursor: pointer;" onclick="window.location=\'' . $getSortUrl($field) . '\'">
                            ' . $label . $getSortIcon($field) . '
                        </th>';
        }

        // Add non-sortable Actions column
        $html .= '
                        <th style="white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($items as $item)
        {
            $html .= '
                <tr>
                    <td>' . $item['id'] . '</td>
                    <td>
                        <a href="?o=Auth&m=read&i=' . $item['id'] . '" class="text-decoration-none">' .
                htmlspecialchars($item['login']) . '</a>
                    </td>
                    <td>' . htmlspecialchars($item['fname']) . '</td>
                    <td>' . htmlspecialchars($item['lname']) . '</td>
                    <td style="white-space: nowrap;">' . $item['created'] . '</td>
                    <td style="white-space: nowrap;">' . $item['updated'] . '</td>
                    <td>
                        <button type="button" class="btn btn-link p-0 me-2" data-bs-toggle="modal" data-bs-target="#editModal' . $item['id'] . '">
                            <i class="bi bi-pencil-square text-primary" style="font-size: 1.2rem;"></i>
                        </button>
                        <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#deleteModal"
                            onclick="document.getElementById(\'confirmDelete\').setAttribute(\'data-post-id\', \'' . $item['id'] . '\')">
                            <i class="bi bi-trash text-danger" style="font-size: 1.2rem;"></i>
                        </button>
                    </td>
                </tr>' .
                $this->modalForm([
                    'id' => $item['id'],
                    'login' => $item['login'],
                    'fname' => $item['fname'],
                    'lname' => $item['lname'],
                    'altemail' => $item['altemail'] ?? '',
                    'grp' => $item['grp'] ?? '0',
                    'acl' => $item['acl'] ?? '0',
                    'webpw' => '',
                    'otp' => $item['otp'] ?? '',
                    'otpttl' => $item['otpttl'] ?? '0',
                    'cookie' => $item['cookie'] ?? '',
                    'anote' => $item['anote'] ?? ''
                ]);
        }

        $html .= '
                </tbody>
            </table>
        </div>';

        // Add pagination controls
        if ($pagination['pages'] > 1)
        {
            $currentPage = $pagination['p'];
            $totalPages = $pagination['pages'];
            $recordsPerPage = $pagination['perPage'];
            $startRecord = (($currentPage - 1) * $recordsPerPage) + 1;
            $endRecord = min($startRecord + $recordsPerPage - 1, $pagination['total']);

            $html .= '<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">';

            // Page info on the left/top
            $html .= '<div class="text-muted text-center text-sm-start">
                <small>
                    Showing records ' . $startRecord . ' - ' . $endRecord . ' of ' . $pagination['total'] . ' 
                    (Page ' . $currentPage . ' of ' . $totalPages . ')
                </small>
            </div>';

            // Pagination controls on the right
            $html .= '<nav aria-label="Page navigation">
                <ul class="pagination mb-0">';

            // Previous button
            $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
            $html .= '<li class="page-item' . $prevDisabled . '">
                <a class="page-link" href="?o=Auth&m=list&p=' . ($currentPage - 1) . '&perpage=' . $pagination['perPage'] . '&sort=' . $currentSort . '&dir=' . $currentDir . '" tabindex="-1">Previous</a>
            </li>';

            // Page numbers
            for ($i = 1; $i <= $totalPages; $i++)
            {
                $active = $i === $currentPage ? ' active' : '';
                $html .= '<li class="page-item' . $active . '">
                    <a class="page-link" href="?o=Auth&m=list&p=' . $i . '&perpage=' . $pagination['perPage'] . '&sort=' . $currentSort . '&dir=' . $currentDir . '">' . $i . '</a>
                </li>';
            }

            // Next button
            $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
            $html .= '<li class="page-item' . $nextDisabled . '">
                <a class="page-link" href="?o=Auth&m=list&p=' . ($currentPage + 1) . '&perpage=' . $pagination['perPage'] . '&sort=' . $currentSort . '&dir=' . $currentDir . '">Next</a>
            </li>';

            $html .= '</ul></nav></div>';
        }

        $html .= '</div>' . $this->deleteModal() . $this->formScript();
        return $html;
    }

    public function read(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->ary, EXTR_SKIP);
        //<span class="me-3">Group: ' . htmlspecialchars($grp) . '</span>

        return '
        <div class="container py-4">
            <article class="card shadow-sm mx-auto" style="max-width: 800px;">
                <div class="card-body">
                    <h1 class="card-title h2 mb-3">' . htmlspecialchars($login) . '</h1>
                    <div class="mb-4">
                        <div class="mb-2"><strong>First Name:</strong> ' . htmlspecialchars($fname) . '</div>
                        <div class="mb-2"><strong>Last Name:</strong> ' . htmlspecialchars($lname) . '</div>
                        <div class="mb-2"><strong>Alternate Email:</strong> ' . htmlspecialchars($altemail) . '</div>
                        <div class="mb-2"><strong>Group:</strong> ' . htmlspecialchars((string)$grp) . '</div>
                        <div class="mb-2"><strong>ACL:</strong> ' . htmlspecialchars((string)$acl) . '</div>
                        <div class="mb-2"><strong>Published:</strong> ' . $created . '</div>
                        <div class="mb-2"><strong>Last Updated:</strong> ' . $updated . '</div>
                    </div>
                    <div class="card-text mb-4">
                        <strong>Admin Note:</strong> ' . Util::nlbr(htmlspecialchars($anote)) . '
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                        <div>
                            <small class="text-muted">User ID: ' . $id . '</small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                onclick="document.getElementById(\'confirmDelete\').setAttribute(\'data-post-id\', \'' . $id . '\')">
                                Delete
                            </button>
                            <a href="?o=Auth&m=list" class="btn btn-secondary me-2">Cancel</a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                                Edit
                            </button>
                        </div>
                        ' . $this->modalForm(['id' => $id, 'grp' => $grp, 'acl' => $acl, 'login' => $login, 'fname' => $fname, 'lname' => $lname, 'altemail' => $altemail, 'webpw' => $webpw, 'otp' => $otp, 'otpttl' => $otpttl, 'cookie' => $cookie, 'anote' => $anote]) . '
                        ' . $this->deleteModal() . '
                    </div>
                </div>
            </article>
        </div>' . $this->formScript();
    }
}
