# SPE::06 Session

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 06 gives the application a memory. Until now every request was independent; here `Ctx` starts a session, and with that come the three things a real form needs: **flash messages** that survive a redirect, a **CSRF token** that proves a form came from this site, and the rule that **anything which changes state is a POST**. The Contact form, which has been a `mailto:` link since chapter 02, finally submits to the server — safely — and the pattern it establishes is the one every write in chapters 07–09 follows.

## The one idea

**State between requests, done safely.** Sessions carry data across requests; CSRF tokens make writes trustworthy; the Post/Redirect/Get pattern makes a submitted form safe to reload.

## What's on the screen

The same pages, plus behaviour: submit the Contact form and a green toast appears *after* a redirect; reload the page and it does not repeat. Submit without a valid token (which the tests do directly) and the write is refused.

## Walkthrough

### Ctx starts the session and mints a token

```php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}
$this->token = $_SESSION['token'] ??= bin2hex(random_bytes(16));
```

The session cookie is configured defensively: `httponly` keeps it out of reach of JavaScript, `SameSite=Lax` stops other sites sending it on cross-site requests, and `use_strict_mode` makes PHP reject a session id it did not itself issue. The CSRF token is a random 16-byte value stored once per session (`??=` assigns only if unset).

### post() — the one guarded way to read a submission

```php
public function post(): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }
    if (!hash_equals($this->token, (string) ($_POST['csrf'] ?? ''))) {
        $this->flash(Flash::Danger, 'That form has expired. Please try again.');
        return null;
    }
    return $_POST;
}
```

This is the chapter's core. A model never touches `$_POST` directly; it calls `$this->ctx->post()`, which returns the submission **only** for a real POST carrying the matching token, and `null` otherwise. The comparison uses `hash_equals()` — a constant-time compare that does not leak, through timing, how much of the token was right. A wrong or missing token is not a silent failure: it flashes an error and returns `null`, so the guard reads as one honest line in every model:

```php
if ($p = $this->ctx->post()) { /* write */ }
```

### Flash — an enum with behaviour

```php
enum Flash: string
{
    case Success = 'success';
    case Danger  = 'danger';
    case Warning = 'warning';
    public function icon(): string { return match ($this) { … }; }
}
```

`Flash` is a **backed enum with a method**. The cases are the three toast styles; `icon()` maps each to a Lucide icon. Messages are queued in the session (`flash()`), drained by the theme after the page loads (`takeFlash()`), and replayed as toasts — so a message set just before a redirect appears on the *next* page and then disappears.

### Post/Redirect/Get

`Plugin::redirect()` sends a `Location` header and exits. `ContactModel::create()` handles the submission, flashes a result, and redirects back to the form:

```php
public function create(): array
{
    if ($p = $this->ctx->post()) {
        $subject = trim((string) ($p['subject'] ?? ''));
        $subject === ''
            ? $this->ctx->flash(Flash::Warning, 'Please enter a subject.')
            : $this->ctx->flash(Flash::Success, "Thanks — …");
    }
    $this->redirect('?o=Contact');
}
```

Because the response to a POST is a redirect, the browser's address bar ends on a GET, so reloading re-fetches the form rather than resubmitting it — no duplicate submissions, no "resend?" dialog. The Contact form in the view is now a real `method="post"` form carrying `{$this->csrf()}`, the hidden token field the base `View` renders.

## PHP features introduced

- **Backed enums with methods** — `Flash` models a small closed set of values *and* the behaviour attached to them.
- **`clone()`-with on immutable objects** and **null-safe access** appear in the chapters that need them (07–09); here the focus is sessions.
- **Session configuration** — secure cookie flags and strict mode set at `session_start`.

## Security

Chapter 06 adds the write-path guarantees the rest of the series relies on: the session cookie is `HttpOnly`, `SameSite=Lax` and strict-mode; every state change is a POST; every form carries a CSRF token that is checked with `hash_equals`; and `Ctx::post()` is the *only* way `$_POST` is read, so it is impossible to forget the check. A GET never mutates anything. These are exactly the holes the previous version of this project had (CSRF tokens emitted but never verified) — closed here and kept closed.

## Try it

```bash
php -S localhost:8006 -t 06-Session/public
```

Submit the Contact form (toast after redirect), reload (no repeat), and inspect the form to see the hidden `csrf` field.

## Next

Chapter 07 adds persistence: a `Db` wrapper over PDO, a `QueryType` enum, a `schema.sql`, and the first real CRUDL plugin — Posts — that reads and writes SQLite entirely through prepared statements, using the very `post()` guard established here.
