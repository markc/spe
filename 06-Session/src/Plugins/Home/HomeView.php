<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Home;

use SPE\Session\Core\View;

final class HomeView extends View
{
    #[\Override]
    public function list(): string
    {
        $a = $this->ary;
        $keys = implode(', ', array_map(fn($k) => "<code>{$k}</code>", $a['session_keys']));
        return <<<HTML
<div class="card">
    <h2>{$a['head']}</h2>
    {$a['main']}

    <h3>Why This Chapter Exists</h3>
    <p>Chapter 05-Autoload organized code into files but had no state between requests. This chapter adds <b>PHP sessions</b> to remember user data, preferences, and navigation history across page loads.</p>

    <h3>What Changed from 05-Autoload</h3>
    <table class="data-table mt-2">
        <thead><tr><th>Aspect</th><th>05-Autoload</th><th>06-Session</th></tr></thead>
        <tbody>
            <tr><td>State</td><td>Stateless (each request is fresh)</td><td>Stateful (data persists in session)</td></tr>
            <tr><td>Parameters</td><td>URL only</td><td>Sticky (URL → session → default)</td></tr>
            <tr><td>Messages</td><td>None</td><td>Flash messages (show once)</td></tr>
            <tr><td>Ctx class</td><td>Basic input handling</td><td>+ ses() and flash() methods</td></tr>
        </tbody>
    </table>

    <h3>Live Session Data</h3>
    <table class="data-table mt-2">
        <tbody>
            <tr><td><b>Session ID</b></td><td><code>{$a['session_id']}</code></td></tr>
            <tr><td><b>Session Name</b></td><td><code>{$a['session_name']}</code></td></tr>
            <tr><td><b>First Visit</b></td><td>{$a['time_ago']}</td></tr>
            <tr><td><b>Page Views</b></td><td>{$a['visit_count']}</td></tr>
            <tr><td><b>Session Size</b></td><td>{$a['session_size']}</td></tr>
            <tr><td><b>Stored Keys</b></td><td>{$keys}</td></tr>
        </tbody>
    </table>
    <p class="text-muted mt-2">Navigate between pages - visit count increases. Close browser, return later - session persists.</p>

    <h3>Session Features</h3>
    <ul>
        <li><b>Sticky Plugin</b> — Current plugin (<code>o</code>) persists in session; method (<code>m</code>) resets to <code>list</code></li>
        <li><b>Clean URLs</b> — Query params stored in session, then URL cleaned via <code>history.replaceState()</code></li>
        <li><b>Flash Messages</b> — Set a message, display once, auto-clear (try Reset below)</li>
        <li><b>Session Regeneration</b> — Generate new session ID (security best practice)</li>
        <li><b>Visit Tracking</b> — Count page views across the session lifetime</li>
    </ul>

    <h3>Key Code Patterns</h3>
<pre>// Ctx::ses() - Sticky parameter: URL overrides session, session persists
public function ses(string \$k, mixed \$v = ''): mixed {
    return \$_SESSION[\$k] = isset(\$_REQUEST[\$k])
        ? trim(\$_REQUEST[\$k]) |> htmlspecialchars(...)
        : \$_SESSION[\$k] ?? \$v;
}

// Ctx::flash() - Set once, retrieve once, then auto-clear
public function flash(string \$k, ?string \$msg = null): ?string {
    if (\$msg !== null) { \$_SESSION["_flash_{\$k}"] = \$msg; return \$msg; }
    \$val = \$_SESSION["_flash_{\$k}"] ?? null;
    unset(\$_SESSION["_flash_{\$k}"]);
    return \$val;
}</pre>

    <div class="flex justify-center gap-2 mt-4">
        <a class="btn" href="?m=regenerate">🔑 Regenerate ID</a>
        <a class="btn btn-danger" href="?m=reset">🗑️ Reset Session</a>
    </div>
</div>
HTML;
    }

    public function reset(): string { return $this->list(); }
    public function regenerate(): string { return $this->list(); }
}
