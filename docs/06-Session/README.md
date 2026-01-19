# SPE::06 Session

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter Six adds memory to the application. Where previous chapters treated each HTTP request as an isolated event—forgetting everything the moment the response was sent—this chapter introduces PHP sessions to maintain state across requests. The user's theme selection persists. Navigation choices stick. Visit counts accumulate. The application remembers who you are and what you were doing, transforming a stateless protocol into a stateful experience. This chapter demonstrates why sessions remain one of PHP's most valuable features for building interactive web applications.

## The Stateless Problem

HTTP is fundamentally stateless. Each request arrives fresh, with no memory of previous interactions. Click a link from Home to About, and the server has no inherent knowledge that you just came from Home. Select the TopNav theme, navigate to Contact, and without sessions the theme selection would vanish—the server wouldn't know you had made a choice. Early web applications worked around this by embedding state in every URL: `?o=Contact&t=TopNav&user=123&preference=dark`. URLs became unwieldy, bookmarks captured transient state, and the back button created confusion.

PHP sessions solve this elegantly. The server generates a unique session ID, stores it in a cookie, and maintains a server-side data store keyed by that ID. Subsequent requests from the same browser include the cookie, allowing the server to retrieve the stored state. From the application's perspective, `$_SESSION` behaves like a persistent array that survives across requests. The complexity of cookie management, session file storage, and ID generation happens invisibly.

## Starting the Session

The `Ctx` class initializes the session in its constructor using a compact conditional expression:

```php
session_status() === PHP_SESSION_NONE && session_start();
```

This pattern avoids the error that occurs when calling `session_start()` on an already-active session. The `session_status()` function returns one of three constants: `PHP_SESSION_DISABLED` if sessions are unavailable, `PHP_SESSION_NONE` if sessions are available but not started, or `PHP_SESSION_ACTIVE` if a session is already running. The short-circuit `&&` operator only calls `session_start()` when the status is `PHP_SESSION_NONE`, making the initialization idempotent—safe to call multiple times without side effects.

## Sticky URL Parameters

The most powerful session technique in this chapter is the "sticky parameter" pattern. URL parameters like `o` (object/plugin), `m` (method), and `t` (theme) persist in the session once set, eliminating the need to include them in every link:

```php
public function ses(string $k, mixed $v = ''): mixed {
    return $_SESSION[$k] = isset($_REQUEST[$k])
        ? (is_array($_REQUEST[$k]) ? $_REQUEST[$k] : trim($_REQUEST[$k]) |> htmlspecialchars(...))
        : ($_SESSION[$k] ?? $v);
}
```

This method implements a three-tier fallback: if a URL parameter exists in `$_REQUEST`, sanitize it and store it in the session; otherwise, use the existing session value; if neither exists, use the provided default. The pipe operator chains `trim()` and `htmlspecialchars()` for input sanitization in a single expression.

The practical effect is profound. Visit `?t=TopNav` once, and every subsequent page load uses TopNav without requiring `?t=TopNav` in the URL. Navigate to `?o=Contact`, and the Contact page remains active until you explicitly visit a different plugin. URLs become cleaner—often just `?o=About` instead of `?o=About&t=TopNav&m=list`—while the application maintains full awareness of user preferences.

The constructor applies this pattern to all input parameters:

```php
$this->in = array_map(fn($k, $v) => $this->ses($k, $v), array_keys($in), $in)
    |> (fn($v) => array_combine(array_keys($in), $v));
```

Each default parameter passes through `ses()`, which checks for URL overrides, falls back to session values, and ultimately uses defaults. The pipe operator reassembles the resulting values into an associative array matching the original keys.

## Flash Messages

Flash messages are session values that exist for exactly one request. Set a flash message before a redirect, retrieve it on the destination page, and it automatically disappears. This pattern handles the common need to display confirmation messages ("Session reset!") or error notifications ("Invalid input") after form submissions:

```php
public function flash(string $k, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION["_flash_{$k}"] = $msg;
        return $msg;
    }
    $val = $_SESSION["_flash_{$k}"] ?? null;
    unset($_SESSION["_flash_{$k}"]);
    return $val;
}
```

The method serves dual purposes based on its arguments. Called with two arguments—`$ctx->flash('msg', 'Success!')`—it stores the message and returns it for immediate use. Called with one argument—`$ctx->flash('msg')`—it retrieves the stored value, deletes it from the session, and returns it. The deletion ensures the message appears only once; refreshing the page won't show it again.

The Theme base class automatically renders flash messages as toast notifications:

```php
protected function flash(): string {
    $msg = $this->ctx->flash('msg');
    $type = $this->ctx->flash('type') ?? 'success';
    return $msg ? "<script>showToast('$msg', '$type');</script>" : '';
}
```

When a flash message exists, this generates a JavaScript call to the `showToast()` function from the shared `spe.js`. The message type—success, danger, warning—determines the toast's color. The pattern keeps flash message handling out of individual plugins; they simply set the message and let the theme display it.

## Visit Tracking

The Home plugin demonstrates practical session usage with visit tracking:

```php
$_SESSION['first_visit'] ??= time();
$_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
```

The null coalescing assignment operator `??=` sets `first_visit` only if it doesn't already exist—perfect for recording the initial visit timestamp without overwriting it on subsequent visits. The visit count increments on every page load, providing a simple but effective demonstration of accumulating state.

The `Util::timeAgo()` helper converts the stored timestamp into human-readable text:

```php
public static function timeAgo(int $ts): string {
    $d = time() - $ts;
    return match (true) {
        $d < 10 => 'just now',
        default => self::fmt($d)
    };
}
```

The match expression handles the edge case of very recent visits, while `fmt()` breaks larger durations into natural language: "2 hours 15 mins ago" or "3 days 4 hours ago". This transforms a Unix timestamp into something meaningful to users.

## Session Reset

The Home plugin includes a reset action that demonstrates session lifecycle management:

```php
public function reset(): array {
    $this->ctx->flash('msg', 'Session has been reset!');
    $this->ctx->flash('type', 'success');
    session_destroy();
    session_start();
    $_SESSION['first_visit'] = time();
    $_SESSION['visit_count'] = 1;
    $_SESSION['o'] = 'Home';
    $_SESSION['t'] = $this->ctx->in['t'];
    // ...
}
```

The sequence matters: set flash messages before destroying the session (they're stored in the old session), destroy the session to clear all data, start a fresh session to get a new ID, then reinitialize required values. The current theme is preserved by reading it from `$this->ctx->in['t']` before destruction and restoring it afterward.

## Session Information

The `Util::sessionInfo()` helper exposes session internals for debugging and demonstration:

```php
public static function sessionInfo(): array {
    return [
        'id' => session_id(),
        'name' => session_name(),
        'status' => match (session_status()) {
            PHP_SESSION_DISABLED => 'disabled',
            PHP_SESSION_NONE => 'none',
            PHP_SESSION_ACTIVE => 'active'
        },
        'save_path' => session_save_path(),
        'data' => $_SESSION
    ];
}
```

The session ID is the unique identifier stored in the browser cookie. The session name defaults to `PHPSESSID` but can be configured. The save path shows where session files are stored on the server—typically `/tmp` or a configured directory. Exposing this information helps developers understand what's happening beneath the abstraction.

## The Directory Structure

Chapter Six maintains the same file organization as Chapter Five, with fifteen files across the familiar directory structure:

```
06-Session/src/
├── Core
│   ├── Ctx.php      # Session initialization, ses(), flash()
│   ├── Init.php     # Dispatch chain (unchanged from 05)
│   ├── Plugin.php   # Abstract base (unchanged)
│   ├── Theme.php    # Abstract base with flash() helper
│   ├── Util.php     # timeAgo(), sessionInfo(), esc()
│   └── View.php     # Base view (unchanged)
├── Plugins
│   ├── About/       # AboutModel.php, AboutView.php
│   ├── Contact/     # ContactModel.php, ContactView.php
│   └── Home/        # HomeModel.php (tracking), HomeView.php (display)
└── Themes
    ├── SideBar.php  # Sidebar layout
    ├── Simple.php   # Simple layout
    └── TopNav.php   # Top navigation layout
```

The session functionality concentrates in `Ctx.php` (initialization and helpers), `Theme.php` (flash message rendering), `Util.php` (time formatting and session info), and `Home/HomeModel.php` (visit tracking demonstration). Other files remain largely unchanged from Chapter Five, demonstrating how sessions integrate cleanly into existing architecture.

## URL Parameter Behavior

The sticky parameter system changes how URLs work throughout the application. Navigation links no longer need to repeat the current theme:

```php
protected function nav(): string {
    ['o' => $o, 't' => $t] = $this->ctx->in;
    return $this->ctx->nav
        |> (fn($n) => array_map(fn($p) => sprintf(
            '<a href="?o=%s"%s>%s</a>',
            $p[1], $o === $p[1] ? ' class="active"' : '', $p[0]
        ), $n))
        |> (fn($a) => implode(' ', $a));
}
```

Each navigation link includes only `?o=PluginName`. The theme persists from the session. Similarly, theme links include only `?t=ThemeName`—the current plugin persists. This creates the cleanest possible URLs while maintaining full state awareness.

Users can still override any parameter explicitly. Visiting `?o=Contact&t=SideBar` sets both values, overriding whatever was stored. The URL takes precedence, then the session, then the default. This hierarchy gives users control while providing sensible persistence.

## Running the Application

Start the PHP development server and observe session behavior:

```bash
cd /path/to/spe
composer install
cd 06-Session/public
php -S localhost:8080
```

Navigate to `http://localhost:8080` and note the visit count at 1. Click to About, then back to Home—the count increases. Select TopNav from the themes dropdown, navigate to Contact, then back to Home—the theme persists without appearing in the URL. Click "Reset Session" and observe the flash message confirmation, the count resetting to 1, and the theme remaining (because the reset preserves it).

Open the browser's developer tools and examine cookies. You'll see `PHPSESSID` with a long hexadecimal value—that's the session ID linking your browser to the server-side session data. Delete that cookie and refresh; you'll get a new session with a fresh visit count and default settings.

This chapter establishes session handling patterns that become essential in later chapters. Chapter Seven will use sessions for database connection caching. Chapter Eight adds user authentication—impossible without sessions to track login state. Chapter Nine builds a full CMS where sessions manage editor preferences, draft content, and access control. The simple patterns introduced here—sticky parameters, flash messages, visit tracking—scale to power sophisticated stateful applications.
