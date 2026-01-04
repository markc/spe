<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Vhosts;

use SPE\HCP\Core\{Ctx, Plugin};

/**
 * Virtual hosts view - HTML rendering for vhost management.
 */
final class VhostsView extends Plugin
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

        return <<<HTML
        <div class="card">
            <h2>Add Virtual Host</h2>
            {$this->error($error)}
            <form method="post" action="?o=Vhosts&m=create">
                <div class="form-group">
                    <label for="domain">Domain Name</label>
                    <input type="text" id="domain" name="domain" required
                           placeholder="example.com" pattern="[a-z0-9.-]+">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ssl" value="1" checked>
                        Enable SSL (Let's Encrypt)
                    </label>
                </div>
                <div class="form-group">
                    <label for="cms">CMS (optional)</label>
                    <select id="cms" name="cms">
                        <option value="">None - Static HTML</option>
                        <option value="wp">WordPress</option>
                        <option value="drupal">Drupal</option>
                        <option value="laravel">Laravel</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Vhost</button>
                    <a href="?o=Vhosts" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        HTML;
    }

    public function read(): string
    {
        $d = $this->data;
        if (isset($d['error'])) {
            return $this->error($d['error']);
        }

        $ssl = $d['ssl'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">None</span>';

        return <<<HTML
        <div class="card">
            <h2>{$d['domain']}</h2>
            <table class="table">
                <tr><th>Document Root</th><td><code>{$d['docroot']}</code></td></tr>
                <tr><th>Log Directory</th><td><code>{$d['logdir']}</code></td></tr>
                <tr><th>SSL Certificate</th><td>{$ssl}</td></tr>
            </table>
            <div class="form-actions">
                <a href="?o=Vhosts&m=ssl&domain={$d['domain']}" class="btn">Renew SSL</a>
                <a href="?o=Vhosts&m=delete&domain={$d['domain']}" class="btn btn-danger">Delete</a>
                <a href="?o=Vhosts" class="btn">Back</a>
            </div>
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
            <h2>Delete Virtual Host</h2>
            <p class="warning">Are you sure you want to delete <strong>{$d['domain']}</strong>?</p>
            <p>This will remove:</p>
            <ul>
                <li>All website files</li>
                <li>Nginx configuration</li>
                <li>SSL certificates</li>
                <li>Log files</li>
            </ul>
            <form method="post" action="?o=Vhosts&m=delete&domain={$d['domain']}">
                <input type="hidden" name="confirm" value="1">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="?o=Vhosts" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        HTML;
    }

    public function list(): string
    {
        $items = $this->data['items'] ?? [];
        $total = $this->data['total'] ?? 0;

        $rows = '';
        foreach ($items as $v) {
            $ssl = $v['ssl']
                ? '<span class="badge badge-success">SSL</span>'
                : '<span class="badge badge-muted">-</span>';
            $status = $v['enabled']
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-warning">Disabled</span>';

            $rows .= <<<HTML
            <tr>
                <td><a href="?o=Vhosts&m=read&domain={$v['domain']}">{$v['domain']}</a></td>
                <td>{$v['user']}</td>
                <td>{$v['size']}</td>
                <td>{$ssl}</td>
                <td>{$status}</td>
                <td class="actions">
                    <a href="?o=Vhosts&m=read&domain={$v['domain']}" title="View">👁</a>
                    <a href="?o=Vhosts&m=delete&domain={$v['domain']}" title="Delete">🗑</a>
                </td>
            </tr>
            HTML;
        }

        if (!$rows) {
            $rows = '<tr><td colspan="6">No virtual hosts found.</td></tr>';
        }

        return <<<HTML
        <div class="card">
            <div class="card-header">
                <h2>Virtual Hosts ({$total})</h2>
                <a href="?o=Vhosts&m=create" class="btn btn-primary">+ Add Vhost</a>
            </div>
            <div class="table-controls">
                <input type="text" id="table-search" placeholder="Search..." class="search-input">
            </div>
            <table class="table sortable" id="vhosts-table">
                <thead>
                    <tr>
                        <th data-sort="domain">Domain</th>
                        <th data-sort="user">User</th>
                        <th data-sort="size">Size</th>
                        <th>SSL</th>
                        <th>Status</th>
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
}
