# SPE::08 Auth

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 08 adds identity and authorization. It introduces a `Role` enum whose `can()` method *is* the entire access-control system, a `User` value object, a login flow that treats a session as something you create and destroy, an admin-only Users CRUDL, and an optional remember-me cookie implemented the correct way — a random selector with a hashed, rotating validator. Posts writes become admin-only while reading stays public. This is the chapter where the framework learns who is asking, and decides what they may do — with the security-sensitive parts kept in one place so they are easy to audit.

## The one idea

**Identity and roles.** A signed-in `User` has a `Role`; each plugin declares the minimum role per method; the front controller checks it before the plugin runs.

## What's on the screen

A Login link in the settings sidebar. Sign in as `admin@example.com` / `admin` to get a Users page and the New/Edit/Delete controls on Posts; sign in as `user@example.com` / `user` to be a signed-in non-admin. "Remember me" keeps you signed in after the session cookie is gone. Logout is a button (a POST), not a link.

## Walkthrough

### Role — an ordered enum that is the ACL

```php
enum Role: string
{
    case Anon = 'Anon';
    case User = 'User';
    case Admin = 'Admin';
    public function can(self $needed): bool { return $this->level() >= $needed->level(); }
    private function level(): int { return match ($this) { self::Anon => 0, self::User => 1, self::Admin => 2 }; }
}
```

Authorization is one comparison: `$role->can(Role::Admin)`. There is no separate permissions table, no middleware stack — the enum carries both the values and the rule relating them.

### User — a readonly value object

```php
final readonly class User
{
    public function __construct(public int $id, public string $email, public string $name, public Role $role) {}
    public static function fromRow(array $row): self { … }
}
```

`User` is immutable: it is built once from a database row and never changes. `fromRow()` is the single place a row becomes a `User`, converting the stored `role` string into a `Role` enum.

### Init enforces access before the plugin runs

```php
} elseif (!$ctx->role()->can($model::guard($m))) {
    $ctx->flash(Flash::Warning, 'Please sign in to continue.');
    header('Location: ?o=Auth&m=create');
    exit;
}
```

Each plugin declares the minimum role for a method with a static `guard()`; `Init` checks the current user's role against it *before* constructing the plugin. Anonymous visitors to an admin-only page are redirected to login, not shown a half-rendered page. `Plugin::guard()` defaults to `Role::Anon` (public); `PostsModel` tightens writes to `Admin`; `UsersModel` requires `Admin` for everything.

### Auth — a session is a resource

The Auth plugin models login and logout as CRUDL on a session: **`create()` is login, `delete()` is logout**. Login verifies the password and rotates the session id:

```php
if ($row && password_verify((string) ($p['password'] ?? ''), $row['password'])) {
    $this->ctx->login(User::fromRow($row), !empty($p['remember']));
    …
}
$this->ctx->flash(Flash::Danger, 'Those credentials did not match.');
```

Passwords are checked with `password_verify()` against a `password_hash(PASSWORD_DEFAULT)` hash. The failure message is the same whether the email or the password was wrong, so it does not reveal which emails exist. `Ctx::login()` calls **`session_regenerate_id(true)`** — issuing a fresh session id on privilege change defeats session fixation — then stores the user id; `logout()` clears the session and regenerates again.

### Remember-me, done properly

`Ctx` keeps the whole session lifecycle in one place, including a remember-me cookie that is safe to persist:

```php
[$selector, $validator] = [bin2hex(random_bytes(9)), bin2hex(random_bytes(32))];
$this->db->create('user_tokens', [
    'selector' => $selector,
    'validator_hash' => hash('sha256', $validator),
    …
]);
setcookie('remember', "$selector:$validator", ['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']), …]);
```

The cookie carries a random selector and a random validator; only a **hash** of the validator is stored, so a leak of the database does not hand out working cookies. On the next visit `restoreRemember()` looks the selector up, compares the validator with `hash_equals`, checks expiry, then **rotates** the token (deletes it and issues a new one) so a captured cookie is single-use. The cookie is `Secure` over HTTPS.

### Users — admin CRUDL

`UsersModel` (admin-only via `guard()`) manages the users table: passwords are hashed on create, optional on update (blank keeps the existing one), the role comes through `Role::tryFrom()`, and an admin cannot delete their own account. `UsersView` renders the list, single view and form; `Ctx::buildNav()` shows the Users link only to admins, and `Theme` shows the signed-in name with a logout button or, when anonymous, the login link.

## PHP features introduced

- **Enum methods as an ACL** — `Role::can()` is the whole authorization rule.
- **Readonly value objects** — `User` is built once and never mutated.
- **Nullable types and `?->`** — `$this->ctx->user?->id`, `Role::tryFrom()` returning `null`.
- **`password_hash` / `password_verify`** and **`session_regenerate_id(true)`** — correct credential and session handling.

## Security

Every guarantee from earlier chapters holds, and this chapter adds the identity ones: passwords are hashed with `PASSWORD_DEFAULT` and checked with `password_verify`; the session id is regenerated on login and logout; authorization is checked in `Init` from the `Role` enum before any plugin runs; the login error is neutral; remember-me stores only a hashed, rotating validator and sends the cookie `Secure` over HTTPS; and an admin cannot delete themselves. The whole session lifecycle lives in `Ctx`, so there is one place to review.

## Try it

```bash
php -S localhost:8008 -t 08-Auth/public
```

Sign in as admin (`admin@example.com` / `admin`) and as a normal user (`user@example.com` / `user`); visit `?o=Users` as each; tick "remember me", then delete the session cookie in your browser and reload to see it restore you.

## Next

Chapter 09 is the finished application: one content table with a `Type` enum for posts and docs, Markdown rendering, property hooks on the `Post` object, many-to-many tags, pagination, and a docs section served by the very engine that documents it.
