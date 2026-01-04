<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Valias;

use SPE\HCP\Core\{Ctx, Plugin};

/**
 * Mail alias view - HTML rendering for alias management.
 */
final class ValiasView extends Plugin
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
            <h2>Add Mail Alias</h2>
            {$this->error($error)}
            <form method="post" action="?o=Valias&m=create">
                <div class="form-group">
                    <label for="source">Source Address</label>
                    <div class="inline-form">
                        <input type="text" id="user" name="user"
                               placeholder="user or leave empty for catch-all" pattern="[a-z0-9._-]*">
                        <span>@</span>
                        <select id="domain_select" name="domain_select" required>
                            <option value="">Select domain...</option>
                            {$domainOptions}
                        </select>
                        <input type="hidden" id="source" name="source">
                    </div>
                    <small>Leave username empty for catch-all alias (@domain.com)</small>
                </div>
                <div class="form-group">
                    <label for="target">Target Address(es)</label>
                    <textarea id="target" name="target" rows="3" required
                              placeholder="user@example.com&#10;another@example.com"></textarea>
                    <small>One address per line, or comma-separated</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" onclick="combineSource()">Create Alias</button>
                    <a href="?o=Valias" class="btn">Cancel</a>
                </div>
            </form>
        </div>
        <script>
        function combineSource() {
            const user = document.getElementById('user').value;
            const domain = document.getElementById('domain_select').value;
            document.getElementById('source').value = (user ? user : '') + '@' + domain;
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

        $targets = is_array($d['target']) ? implode('<br>', $d['target']) : $d['target'];

        return <<<HTML
        <div class="card">
            <h2>{$d['source']}</h2>
            <table class="table">
                <tr><th>Status</th><td>{$status}</td></tr>
                <tr><th>Forwards To</th><td>{$targets}</td></tr>
                <tr><th>Created</th><td>{$d['created_at']}</td></tr>
                <tr><th>Updated</th><td>{$d['updated_at']}</td></tr>
            </table>
            <div class="form-actions">
                <a href="?o=Valias&m=update&source={$d['source']}" class="btn">Edit</a>
                <a href="?o=Valias&m=delete&source={$d['source']}" class="btn btn-danger">Delete</a>
                <a href="?o=Valias" class="btn">Back</a>
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
            return $this->success($d['success']) . '<a href="?o=Valias" class="btn">Back to List</a>';
        }

        $targets = is_array($d['target']) ? implode("\n", $d['target']) : $d['target'];

        return <<<HTML
        <div class="card">
            <h2>Edit Alias: {$d['source']}</h2>
            <form method="post" action="?o=Valias&m=update&source={$d['source']}">
                <input type="hidden" name="action" value="targets">
                <div class="form-group">
                    <label for="target">Target Address(es)</label>
                    <textarea id="target" name="target" rows="3" required>{$targets}</textarea>
                    <small>One address per line, or comma-separated</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Alias</button>
                    <a href="?o=Valias&m=read&source={$d['source']}" class="btn">Cancel</a>
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
            <h2>Delete Alias</h2>
            <p class="warning">Are you sure you want to delete <strong>{$d['source']}</strong>?</p>
            <form method="post" action="?o=Valias&m=delete&source={$d['source']}">
                <input type="hidden" name="confirm" value="1">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="?o=Valias" class="btn">Cancel</a>
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
        foreach ($items as $a) {
            $status = $a['active']
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-warning">Disabled</span>';

            $targets = is_array($a['target']) ? implode(', ', $a['target']) : $a['target'];
            if (strlen($targets) > 50) {
                $targets = substr($targets, 0, 47) . '...';
            }

            $rows .= <<<HTML
            <tr>
                <td><a href="?o=Valias&m=read&source={$a['source']}">{$a['source']}</a></td>
                <td>{$targets}</td>
                <td>{$status}</td>
                <td class="actions">
                    <a href="?o=Valias&m=read&source={$a['source']}" title="View">👁</a>
                    <a href="?o=Valias&m=update&source={$a['source']}" title="Edit">✏️</a>
                    <a href="?o=Valias&m=delete&source={$a['source']}" title="Delete">🗑</a>
                </td>
            </tr>
            HTML;
        }

        if (!$rows) {
            $rows = '<tr><td colspan="4">No aliases found.</td></tr>';
        }

        return <<<HTML
        <div class="card">
            <div class="card-header">
                <h2>Mail Aliases ({$total})</h2>
                <a href="?o=Valias&m=create" class="btn btn-primary">+ Add Alias</a>
            </div>
            <div class="table-controls inline-form">
                <input type="text" id="table-search" placeholder="Search..." class="search-input">
                <select onchange="window.location='?o=Valias&domain='+this.value">
                    {$domainFilter}
                </select>
            </div>
            <table class="table sortable" id="valias-table">
                <thead>
                    <tr>
                        <th data-sort="source">Source</th>
                        <th data-sort="target">Target(s)</th>
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

    private function success(string $msg): string
    {
        return $msg ? "<div class=\"alert alert-success\">{$msg}</div>" : '';
    }
}
