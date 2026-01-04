# 11-HCP - Simple Hosting Control Panel

Lightweight PHP hosting control panel built on the SPE (Simple PHP Examples) framework.
Wraps shell scripts for vhost/mail/dns management with a minimal web UI.

## Architecture

```
11-HCP/
├── public/
│   ├── index.php          # Entry point
│   └── tables.js          # Vanilla JS sorting/filtering (~60 lines)
├── src/
│   ├── Core/
│   │   ├── Ctx.php        # Context (copy from 09-Blog)
│   │   ├── Init.php       # Router (copy from 09-Blog, modify)
│   │   ├── Plugin.php     # Base CRUD plugin (copy from 09-Blog)
│   │   ├── Shell.php      # ★ NEW - sudo command wrapper
│   │   └── Theme.php      # Base theme (copy from 09-Blog)
│   ├── Plugins/
│   │   ├── System/        # ★ Dashboard + services
│   │   ├── Vhosts/        # ★ Virtual hosts (addvhost/delvhost)
│   │   ├── Vmails/        # TODO: Mail accounts (addvmail/delvmail)
│   │   ├── Vdns/          # TODO: DNS records (addvdns/delvdns)
│   │   ├── Ssl/           # TODO: Certificates (addssl/newssl)
│   │   └── Stats/         # TODO: Traffic/disk stats
│   └── Themes/
│       └── HCP.php        # HCP-specific theme (copy + modify TopNav)
└── docs/
```

## Key Components

### Shell.php - Command Wrapper
```php
// Execute hosting scripts via sudo
Shell::run('addvhost', ['example.com', 'ssl']);
Shell::run('delvmail', ['user@example.com']);

// Get system stats
Shell::systemStats();  // disk, memory, load, uptime
Shell::services();     // nginx, php-fpm, mariadb, postfix, dovecot
```

### Plugin Pattern
Each plugin has:
- `{Name}Model.php` - Business logic, calls Shell::run()
- `{Name}View.php` - HTML rendering
- `meta.json` - Navigation config

## Shell Scripts Required

Install these to `/usr/local/bin/` with sudo access:

| Script | Purpose |
|--------|---------|
| `addvhost` | Create virtual host |
| `delvhost` | Delete virtual host |
| `shvhost` | Show vhost details |
| `addvmail` | Create mailbox |
| `delvmail` | Delete mailbox |
| `shvmail` | List mailboxes |
| `addvdns` | Add DNS record |
| `delvdns` | Delete DNS record |
| `shvdns` | List DNS records |
| `addssl` | Install SSL cert |

## TODO

- [ ] Copy Core files from 09-Blog (Ctx, Init, Plugin, Theme, Util)
- [ ] Adapt Init.php router for HCP plugins
- [ ] Create HCP theme (TopNav based)
- [ ] Complete Vmails plugin
- [ ] Complete Vdns plugin
- [ ] Complete Ssl plugin
- [ ] Complete Stats plugin
- [ ] Add Auth plugin (copy from 09-Blog)
- [ ] Add basic CSS (hcp.css)
- [ ] sudoers config for www-data
- [ ] Install shell scripts

## Sudoers Configuration

```bash
# /etc/sudoers.d/hcp
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/addvhost
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/delvhost
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/shvhost
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/addvmail
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/delvmail
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/shvmail
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/addssl
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/addvdns
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/delvdns
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/shvdns
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl is-active *
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl status *
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart nginx
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx
```

## License

MIT License - Copyright (C) 2015-2025 Mark Constable <mc@netserva.org>
