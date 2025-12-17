# SPE::09 Blog

_Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)_

A full-featured blog/CMS system demonstrating the complete SPE framework with authentication, content management, and documentation features.

## PHP 8.x Features Demonstrated

### PHP 8.1
- First-class callables: `array_keys(...)`, `http_build_query(...)`
- Enums: `enum QueryType { case All; case One; case Column; }`
- Readonly properties in value objects

### PHP 8.2
- Readonly classes: `final readonly class PluginMeta`
- `true`, `false`, `null` as standalone types

### PHP 8.3
- Typed class constants: `private const string NS = 'SPE\\Blog\\'`
- Typed array constants: `private const array OPTIONS = [...]`
- `#[\Override]` attribute on all overridden methods

### PHP 8.4
- Asymmetric visibility: `public private(set) string $prop`
- `new` without parentheses: `new Ctx`

### PHP 8.5
- Pipe operator `|>` for data transformation chains throughout
- First-class callables with pipe: `$_GET |> http_build_query(...)`

## Quick Start

```bash
composer install
cd 09-Blog/public
php -S localhost:8080
# Open http://localhost:8080
```

## Features

### Content Management
- **Posts**: Blog posts with categories, excerpts, featured images
- **Pages**: Static pages (Home, About, Contact) with custom icons
- **Categories**: Organize content with category tagging
- **Docs**: Documentation system reading markdown files from filesystem

### Authentication
- User login/logout with session management
- "Remember me" cookie-based persistence
- Password reset via email with OTP tokens
- Role-based access control (admin/user)

### Themes
- **Simple**: Minimal single-column layout
- **TopNav**: Fixed top navigation bar
- **SideBar**: Left sidebar with grouped navigation

### Plugin System
- Auto-discovery via `meta.json` files
- Route protection (auth required, admin only)
- Grouped navigation based on plugin metadata

## Architecture

```
09-Blog/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Core/
│   │   ├── Ctx.php            # Context with nav building
│   │   ├── Db.php             # PDO wrapper with QueryType enum
│   │   ├── Init.php           # Request dispatch and auth
│   │   ├── Plugin.php         # Base CRUDL plugin
│   │   ├── PluginLoader.php   # Auto-discovery from meta.json
│   │   ├── PluginMeta.php     # Immutable plugin metadata
│   │   ├── Theme.php          # Base theme with nav helpers
│   │   └── Util.php           # Helpers + Markdown parser
│   ├── Plugins/
│   │   ├── Auth/              # Login, logout, password reset
│   │   ├── Blog/              # Public blog view
│   │   ├── Categories/        # Category CRUD
│   │   ├── Contact/           # Contact form
│   │   ├── Docs/              # Documentation viewer
│   │   ├── Home/              # Home page
│   │   ├── Pages/             # Page CRUD
│   │   ├── Posts/             # Post CRUD + blog.db
│   │   ├── Profile/           # User profile editing
│   │   └── Users/             # User management + users.db
│   └── Themes/
│       ├── Simple.php         # Minimal theme
│       ├── TopNav.php         # Top navigation theme
│       └── SideBar.php        # Sidebar theme
└── docs/                      # Documentation markdown files
```

## URL Parameters

```
?o=Blog        - Plugin/Object name
?m=list        - Method (create, read, update, delete, list)
?t=TopNav      - Theme (Simple, TopNav, SideBar)
?p=about       - Page slug shortcut (redirects to Pages plugin)
?id=1          - Record ID for CRUD operations
```

## Databases

### blog.db (SQLite)
- `posts` - Blog posts and pages (type: 'post'|'page'|'doc')
- `categories` - Content categories
- `post_categories` - Many-to-many relationship

### users.db (SQLite)
- `users` - User accounts with roles and authentication

## Custom Markdown Parser

`Util::md()` provides a ~70 line GFM-compatible parser supporting:
- Headings, bold, italic, strikethrough
- Links, images, code blocks (with syntax highlighting class)
- Blockquotes, ordered/unordered lists
- Horizontal rules
- **GFM Tables** with alignment support

## What's New from 08-Users

1. **Blog Plugin**: Public-facing blog with card grid, pagination, prev/next navigation
2. **Pages Plugin**: Database-driven pages with custom icons
3. **Categories Plugin**: Content categorization with many-to-many relationships
4. **Docs Plugin**: Hybrid database/filesystem documentation system
5. **Profile Plugin**: User self-service profile editing
6. **Markdown Parser**: Custom GFM-compatible parser in Util.php
7. **Dropdown Navigation**: Admin and Theme dropdowns with URL preservation
8. **Theme-specific Spacing**: CSS handles content spacing per theme

## Production Deployment

09-Blog is the only chapter complete enough for real-world deployment. The other chapters are learning exercises meant for local development.

### Requirements

- PHP 8.5+ with extensions: `pdo_sqlite`, `mbstring`, `session`
- Web server: Nginx (recommended) or Apache
- Write access to database directory

### Deployment Steps

#### 1. Transfer Files

```bash
# From your local machine
rsync -avz --exclude='.git' --exclude='vendor' \
    ~/Dev/spe/09-Blog/ user@server:/var/www/spe-blog/

# On server: install dependencies
cd /var/www/spe-blog && composer install --no-dev --optimize-autoloader
```

#### 2. Set Permissions

```bash
# Web server needs write access to databases
chown -R www-data:www-data /var/www/spe-blog/src/Plugins/Posts/
chown -R www-data:www-data /var/www/spe-blog/src/Plugins/Users/
chmod 664 /var/www/spe-blog/src/Plugins/Posts/blog.db
chmod 664 /var/www/spe-blog/src/Plugins/Users/users.db
```

#### 3. Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/spe-blog/public;
    index index.php;

    # Route all requests through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to sensitive files
    location ~ /\.(git|env|sqlite|db) {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|ico|png|jpg|gif|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 4. Apache Configuration (.htaccess)

The `public/` directory needs this `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Block access to databases
<FilesMatch "\.(db|sqlite)$">
    Require all denied
</FilesMatch>
```

#### 5. PHP Configuration

Recommended `php.ini` settings for production:

```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
```

### Security Considerations

1. **Database location**: SQLite files are in `src/Plugins/*/`. Consider moving them outside the web root:
   ```php
   // In Db.php, change path to:
   private const string DB_PATH = '/var/data/spe/';
   ```

2. **Default credentials**: Change the default admin password immediately after deployment.

3. **HTTPS**: Always use HTTPS in production. Update `session.cookie_secure = On`.

4. **File uploads**: If enabling file uploads, configure upload directory outside web root.

### Database Migration

To deploy with fresh databases:

```bash
# Copy empty database schemas (if available)
# Or let the application create them on first run

# To migrate existing data:
scp local:~/Dev/spe/09-Blog/src/Plugins/Posts/blog.db server:/var/www/spe-blog/src/Plugins/Posts/
scp local:~/Dev/spe/09-Blog/src/Plugins/Users/users.db server:/var/www/spe-blog/src/Plugins/Users/
```

### Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 error | Check `error_log`, ensure PHP 8.5+, verify permissions |
| Database locked | Ensure `www-data` has write access to db files AND parent directory |
| Sessions not working | Check `session.save_path` is writable |
| CSS/JS not loading | Verify `/spe.css` and `/spe.js` paths, check Nginx `root` directive |

## License

MIT License
