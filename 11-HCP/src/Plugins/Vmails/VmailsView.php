<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Vmails;

use SPE\HCP\Core\{Ctx, Plugin};

/**
 * Virtual mailbox view - HTML rendering for mailbox management.
 */
final class VmailsView extends Plugin
{
    public function __construct(
        protected Ctx $ctx,
        private array $data = []
    ) {
        parent::__construct($ctx);
    }

    public function create(): string
    {
        $error = $this->data['error'] ?? '';
        $domains = $this->data['domains'] ?? [];

        $domainOptions = '';
        foreach ($domains as $d) {
            $domainOptions .= "<option value=\"{$d}\">{$d}</option>";
        }

        return <<<HTML
        <div class="card">
            <h2>Add Mailbox</h2>
            {$this->error($error)}
            <form method="post" action="?o=Vmails&m=create">
                <div class="form-group">
                    <label for="user">Username</label>
                    <div class="inline-form">
                        <input type="text" id="user" name="user" required
                               placeholder="username" pattern="[a-z0-9._-]+">
                        <span>@</span>
                        <select id="domain" name="domain_select" required>
                            <option value="">Select domain...</option>
                            {$domainOptions}
                        </select>
                        <input type="hidden" id="email" name="email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password (leave blank to generate)</label>
                    <input type="text" id="password" name="password"
                           placeholder="Auto-generated if blank">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" onclick="combineEmail()">Create Mailbox</button>
                    <a href="?o=Vmails" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        <script>
        function combineEmail() {
            const user = document.getElementById('user').value;
            const domain = document.getElementById('domain_select').value;
            document.getElementById('email').value = user + '@' + domain;
        }
        </script>
        HTML;
    }

    public function read(): string
    {
        $d = $this->data;
        if (isset($d['error'])) {
            return $this->error($d['error']);
        }

        $status = $d['active']
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-warning">Disabled</span>';

        $newPassAlert = '';
        if (!empty($d['new_password'])) {
            $newPassAlert = <<<HTML
            <div class="alert alert-success">
                <strong>Mailbox created!</strong> Password: <code>{$d['new_password']}</code>
                <br><small>Save this password - it will not be shown again.</small>
            </div>
            HTML;
        }

        $aliases = '';
        if (!empty($d['aliases'])) {
            foreach ($d['aliases'] as $alias) {
                $aliases .= "<li>{$alias['source']} &rarr; {$alias['target']}</li>";
            }
            $aliases = "<ul>{$aliases}</ul>";
        } else {
            $aliases = '<em>No aliases</em>';
        }

        return <<<HTML
        <div class="card">
            <h2>{$d['email']}</h2>
            {$newPassAlert}
            <table class="table">
                <tr><th>Status</th><td>{$status}</td></tr>
                <tr><th>Home Directory</th><td><code>{$d['home']}</code></td></tr>
                <tr><th>UID/GID</th><td>{$d['uid']} / {$d['gid']}</td></tr>
                <tr><th>Disk Usage</th><td>{$d['size_human']}</td></tr>
                <tr><th>Created</th><td>{$d['created_at']}</td></tr>
                <tr><th>Updated</th><td>{$d['updated_at']}</td></tr>
                <tr><th>Aliases</th><td>{$aliases}</td></tr>
            </table>
            <div class="form-actions">
                <a href="?o=Vmails&m=update&email={$d['email']}" class="btn">Change Password</a>
                <a href="?o=Vmails&m=delete&email={$d['email']}" class="btn btn-danger">Delete</a>
                <a href="?o=Vmails" class="btn">Back</a>
            </div>
        </div>
        HTML;
    }

    public function update(): string
    {
        $d = $this->data;

        if (isset($d['error'])) {
            return $this->error($d['error']);
        }
        if (isset($d['success'])) {
            return $this->success($d['success']) . '<a href="?o=Vmails" class="btn">Back to List</a>';
        }

        return <<<HTML
        <div class="card">
            <h2>Update Mailbox: {$d['email']}</h2>
            <form method="post" action="?o=Vmails&m=update&email={$d['email']}">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="text" id="password" name="password" required
                           placeholder="Enter new password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <a href="?o=Vmails&m=read&email={$d['email']}" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        HTML;
    }

    public function delete(): string
    {
        $d = $this->data;

        if (isset($d['error'])) {
            return $this->error($d['error']);
        }

        return <<<HTML
        <div class="card">
            <h2>Delete Mailbox</h2>
            <p class="warning">Are you sure you want to delete <strong>{$d['email']}</strong>?</p>
            <p>This will remove:</p>
            <ul>
                <li>All email messages</li>
                <li>Mailbox configuration</li>
                <li>Associated aliases</li>
            </ul>
            <form method="post" action="?o=Vmails&m=delete&email={$d['email']}">
                <input type="hidden" name="confirm" value="1">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="?o=Vmails" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        HTML;
    }

    public function list(): string
    {
        $items = $this->data['items'] ?? [];
        $total = $this->data['total'] ?? 0;
        $domains = $this->data['domains'] ?? [];
        $filterDomain = $this->data['filter_domain'] ?? '';

        // Domain filter dropdown
        $domainFilter = '<option value="">All Domains</option>';
        foreach ($domains as $d) {
            $sel = $d === $filterDomain ? 'selected' : '';
            $domainFilter .= "<option value=\"{$d}\" {$sel}>{$d}</option>";
        }

        $rows = '';
        foreach ($items as $m) {
            $status = $m['active']
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-warning">Disabled</span>';

            $rows .= <<<HTML
            <tr>
                <td><a href="?o=Vmails&m=read&email={$m['email']}">{$m['email']}</a></td>
                <td>{$m['home']}</td>
                <td>{$status}</td>
                <td>{$m['created_at']}</td>
                <td class="actions">
                    <a href="?o=Vmails&m=read&email={$m['email']}" title="View">👁</a>
                    <a href="?o=Vmails&m=update&email={$m['email']}" title="Password">🔑</a>
                    <a href="?o=Vmails&m=delete&email={$m['email']}" title="Delete">🗑</a>
                </td>
            </tr>
            HTML;
        }

        if (!$rows) {
            $rows = '<tr><td colspan="5">No mailboxes found.</td></tr>';
        }

        return <<<HTML
        <div class="card">
            <div class="card-header">
                <h2>Virtual Mailboxes ({$total})</h2>
                <a href="?o=Vmails&m=create" class="btn btn-primary">+ Add Mailbox</a>
            </div>
            <div class="table-controls inline-form">
                <input type="text" id="table-search" placeholder="Search..." class="search-input">
                <select onchange="window.location='?o=Vmails&domain='+this.value">
                    {$domainFilter}
                </select>
            </div>
            <table class="table sortable" id="vmails-table">
                <thead>
                    <tr>
                        <th data-sort="email">Email</th>
                        <th data-sort="home">Home</th>
                        <th>Status</th>
                        <th data-sort="created">Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
        HTML;
    }

    private function error(string $msg): string
    {
        return $msg ? "<div class=\"alert alert-error\">{$msg}</div>" : '';
    }

    private function success(string $msg): string
    {
        return $msg ? "<div class=\"alert alert-success\">{$msg}</div>" : '';
    }
}
