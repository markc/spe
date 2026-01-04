<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\System;

use SPE\HCP\Core\{Ctx, Plugin};

/**
 * System dashboard view - stats, services, quick actions.
 */
final class SystemView extends Plugin
{
    public function __construct(
        protected Ctx $ctx,
        private array $data = []
    ) {
        parent::__construct($ctx);
    }

    public function list(): string
    {
        $d = $this->data;
        $stats = $d['stats'];
        $os = $d['os'];
        $counts = $d['counts'];

        // Service status badges
        $services = '';
        foreach ($d['services'] as $svc) {
            $badge = $svc['active']
                ? '<span class="badge badge-success">Running</span>'
                : '<span class="badge badge-danger">Stopped</span>';
            $services .= "<tr><td>{$svc['name']}</td><td>{$badge}</td><td><a href=\"?o=System&m=read&service={$svc['name']}\">Details</a></td></tr>";
        }

        // Progress bar helper
        $bar = fn($pct, $label) => sprintf(
            '<div class="progress"><div class="progress-bar" style="width:%d%%">%s: %d%%</div></div>',
            min(100, $pct), $label, $pct
        );

        $load = implode(' / ', array_map(fn($l) => number_format($l, 2), $stats['load']));

        return <<<HTML
        <div class="dashboard">
            <div class="card">
                <h2>System Overview</h2>
                <table class="table">
                    <tr><th>Hostname</th><td>{$d['hostname']}</td></tr>
                    <tr><th>OS</th><td>{$os['name']}</td></tr>
                    <tr><th>Kernel</th><td>{$os['kernel']}</td></tr>
                    <tr><th>Uptime</th><td>{$stats['uptime_days']} days</td></tr>
                    <tr><th>Load Average</th><td>{$load}</td></tr>
                </table>
            </div>

            <div class="card">
                <h2>Resources</h2>
                {$bar($stats['disk_used_pct'], 'Disk')}
                {$bar($stats['mem_used_pct'], 'Memory')}
            </div>

            <div class="card">
                <h2>Quick Stats</h2>
                <div class="stat-grid">
                    <div class="stat">
                        <span class="stat-value">{$counts['vhosts']}</span>
                        <span class="stat-label">Vhosts</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">{$counts['mailboxes']}</span>
                        <span class="stat-label">Mailboxes</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">{$counts['databases']}</span>
                        <span class="stat-label">Databases</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Services</h2>
                <table class="table">
                    <thead>
                        <tr><th>Service</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        {$services}
                    </tbody>
                </table>
            </div>
        </div>
        HTML;
    }

    public function read(): string
    {
        $d = $this->data;
        $badge = $d['active']
            ? '<span class="badge badge-success">Running</span>'
            : '<span class="badge badge-danger">Stopped</span>';

        return <<<HTML
        <div class="card">
            <div class="card-header">
                <h2>Service: {$d['service']}</h2>
                {$badge}
            </div>
            <pre class="code">{$d['status']}</pre>
            <form method="post" action="?o=System&m=service" class="inline-form">
                <input type="hidden" name="service" value="{$d['service']}">
                <button type="submit" name="action" value="restart" class="btn">Restart</button>
                <button type="submit" name="action" value="reload" class="btn">Reload</button>
                <button type="submit" name="action" value="stop" class="btn btn-danger">Stop</button>
                <a href="?o=System" class="btn">Back</a>
            </form>
        </div>
        HTML;
    }
}
