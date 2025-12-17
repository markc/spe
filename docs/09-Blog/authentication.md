# Authentication

SPE's authentication system uses PHP sessions with optional "remember me" cookies and OTP-based password reset.

## Architecture

```
Auth Plugin (AuthModel.php)
├── list()   → Login form + processing
├── create() → Forgot password form
├── read()   → OTP link verification
├── update() → Password reset form
└── delete() → Logout
```

## Session Structure

```php
// Logged-in user session
$_SESSION['usr'] = [
    'id' => 1,
    'login' => 'user@example.com',
    'fname' => 'John',
    'lname' => 'Doe',
    'acl' => 1,    // 0=admin, 1=user, 9=disabled
    'grp' => 0
];

// Password reset session (temporary)
$_SESSION['resetpw'] = [
    'id' => 1,
    'login' => 'user@example.com'
];

// Flash messages
$_SESSION['log'] = ['msg' => 'Message text', 'type' => 'success'];
```

## Util Auth Helpers

```php
// Check if user is logged in
if (Util::is_usr()) { ... }

// Check if user is admin (acl = 0)
if (Util::is_adm()) { ... }

// Check if request is POST
if (Util::is_post()) { ... }

// Set flash message
Util::log('Error message');              // danger (default)
Util::log('Success message', 'success');

// Get and clear flash message
$log = Util::get_log();  // Returns ['msg' => '...', 'type' => '...'] or []

// Redirect (never returns)
Util::redirect('?o=Home');

// Generate secure random token
$token = Util::random_token(16);  // 32-char hex string

// Set cookie with secure defaults
Util::set_cookie('remember', $token, 604800);  // 7 days
```

## Login Flow

```php
// 1. Show login form
GET ?o=Auth
→ AuthModel::list() returns ['action' => 'login']
→ AuthView::list() renders login form

// 2. Process login
POST ?o=Auth (login=email, webpw=password, remember=1)
→ AuthModel::list()
   → Validate credentials
   → Check ACL not disabled (9)
   → Set remember cookie if requested
   → Create $_SESSION['usr']
   → Redirect to Home
```

## Password Reset Flow

```php
// 1. Request reset
GET ?o=Auth&m=create
→ Shows forgot password form

POST ?o=Auth&m=create (login=email)
→ Generates OTP token
→ Stores in users.otp with otpttl timestamp
→ Emails reset link

// 2. Click reset link
GET ?o=Auth&m=read&otp=<token>
→ Validates OTP exists and not expired (1 hour)
→ Creates $_SESSION['resetpw']
→ Redirects to update form

// 3. Set new password
GET ?o=Auth&m=update
→ Shows password form

POST ?o=Auth&m=update (passwd1, passwd2)
→ Validates passwords match and length >= 8
→ Updates password hash
→ Clears OTP and resetpw session
→ Redirects to login
```

## Remember Me

```php
// On login with "remember" checkbox
$cookie = Util::random_token(32);
$db->update('users', ['cookie' => $cookie], ...);
Util::set_cookie('remember', $cookie, 604800);  // 7 days

// On page load (via Init.php)
if (!Util::is_usr() && isset($_COOKIE['remember'])) {
    $auth = new AuthModel($ctx);
    $auth->checkRemember();  // Restores session from cookie
}

// On logout
$db->update('users', ['cookie' => ''], ...);
Util::set_cookie('remember', '', -3600);  // Delete cookie
```

## ACL Levels

| Level | Constant | Description |
|-------|----------|-------------|
| 0 | Admin | Full access to all plugins |
| 1 | User | Standard user access |
| 9 | Disabled | Cannot login |

## Plugin Auth Requirements

Plugins declare auth requirements in `meta.json`:

```json
{
    "name": "Posts",
    "auth": true,    // Requires login
    "admin": true    // Requires admin (acl = 0)
}
```

Init.php checks these before dispatching:

```php
// In Init::__construct()
if ($meta->auth && !Util::is_usr()) {
    Util::redirect('?o=Auth');
}
if ($meta->admin && !Util::is_adm()) {
    Util::log('Admin access required');
    Util::redirect('?o=Home');
}
```

## Security Features

1. **Password Hashing**: Uses `password_hash(PASSWORD_DEFAULT)`
2. **CSRF-like Protection**: Session-based state management
3. **Secure Cookies**: HttpOnly, SameSite=Lax
4. **OTP Expiry**: Reset tokens expire after 1 hour
5. **Session Regeneration**: On logout with `session_regenerate_id(true)`
6. **Constant-time Comparison**: Via `password_verify()`

## Database Schema

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT UNIQUE NOT NULL,    -- Email address
    fname TEXT,
    lname TEXT,
    webpw TEXT,                    -- password_hash()
    cookie TEXT,                   -- Remember me token
    otp TEXT,                      -- Password reset token
    otpttl INTEGER,                -- OTP expiry timestamp
    acl INTEGER DEFAULT 1,         -- Access control level
    grp INTEGER DEFAULT 0,
    created DATETIME,
    updated DATETIME
);
```

## Usage Examples

### Protecting Routes

```php
// In any Model method
public function list(): array {
    if (!Util::is_usr()) {
        Util::redirect('?o=Auth');
    }
    // ... protected code
}

// Admin-only action
public function delete(): array {
    if (!Util::is_adm()) {
        Util::log('Admin access required');
        return ['error' => true];
    }
    // ... admin code
}
```

### Getting Current User

```php
// Access user data
$userId = $_SESSION['usr']['id'];
$name = $_SESSION['usr']['fname'];
$isAdmin = (int)$_SESSION['usr']['acl'] === 0;

// In views
$userName = $_SESSION['usr']['fname'] ?? 'Guest';
```
