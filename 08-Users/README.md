# SPE::08 Users

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

Adds user management with full CRUDL operations and a separate users database.

## PHP 8.x Features Demonstrated

### PHP 8.1
- Enums: `enum QueryType { case All; case One; case Column; }`
- First-class callables

### PHP 8.2
- Readonly classes

### PHP 8.3
- Typed class constants: `private const int DEFAULT_PER_PAGE = 10`
- `#[\Override]` attribute on plugin methods

### PHP 8.4
- Asymmetric visibility
- `new` without parentheses

### PHP 8.5
- Pipe operator for data transformation and nav rendering

## Quick Start

```bash
composer install
cd 08-Users/public
php -S localhost:8000
# Open http://localhost:8000
```

## What's New from 07-PDO

1. **Users Plugin**: Full CRUDL for user management
2. **users.db**: Separate SQLite database for users
3. **Password Hashing**: Using `password_hash()` / `password_verify()`
4. **ACL Field**: Access control levels (0=admin, 1=user, 9=disabled)

## User Management

```php
// User table structure
$user = [
    'id' => 1,
    'login' => 'admin@example.com',
    'fname' => 'Admin',
    'lname' => 'User',
    'acl' => 0,           // 0=admin, 1=user, 9=disabled
    'grp' => 0,
    'webpw' => '...',     // password_hash()
    'cookie' => '',       // Remember me token
    'otp' => '',          // Password reset token
    'otpttl' => 0,        // OTP expiry timestamp
];
```

## Architecture

```
08-Users/
├── public/
│   └── index.php
└── src/
    ├── Core/
    │   ├── Ctx.php
    │   ├── Db.php
    │   ├── Init.php
    │   ├── Plugin.php
    │   ├── Theme.php
    │   └── Util.php
    ├── Plugins/
    │   ├── About/
    │   ├── Blog/
    │   │   └── blog.db
    │   ├── Contact/
    │   ├── Home/
    │   └── Users/         # NEW: User management
    │       ├── UsersModel.php
    │       ├── UsersView.php
    │       └── users.db   # User database
    └── Themes/
        ├── Simple.php
        ├── TopNav.php
        └── SideBar.php
```

## CRUDL Operations

- **Create**: Add new user with password hashing
- **Read**: View user details
- **Update**: Edit user profile and password
- **Delete**: Remove user (with confirmation)
- **List**: Paginated user listing with search

## License

MIT License
