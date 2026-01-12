# 11-HCP: Lightweight Hosting Control Panel

Chapter 11 marks a departure from the progressive tutorial structure of earlier chapters. Where chapters 01-10 built incrementally toward a blog CMS, this chapter applies the accumulated patterns to a fundamentally different domain: remote server management. HCP (Hosting Control Panel) demonstrates how the SPE architecture—plugins, models, views, and themes—adapts to orchestrating SSH commands across remote Linux servers. The result is a unified system where both CLI tools and web interfaces share identical library code, enabling administrators to manage virtual hosts, mailboxes, and SSH configurations through whichever interface suits their workflow.

## Architecture: Three-Layer Separation

The defining characteristic of HCP is its strict separation into three distinct layers, each with a precise responsibility. This separation emerged from a fundamental constraint: filesystem operations on remote servers require SSH execution as root, while database operations run as the web user on the local machine. Rather than conflating these concerns, HCP isolates them completely.

### The Transport Layer: Remote.php

At the foundation sits `Remote.php`, a pure SSH transport layer that knows nothing about hosting concepts. Its sole responsibility is maintaining connections to remote servers and executing arbitrary commands. The class uses phpseclib3 for SSH communication, avoiding the need for shell-based SSH invocations that would complicate error handling and output parsing.

The transport layer implements connection pooling to enable efficient sequential operations. When managing a virtual host, the system might execute dozens of SSH commands—creating directories, setting permissions, writing configuration files, reloading services. Opening a new SSH connection for each command would introduce unacceptable latency. Instead, `Remote.php` maintains a pool of active connections indexed by host name. The `connect()` method first checks whether a valid connection already exists in the pool before establishing a new one.

What makes this design particularly elegant is how it resolves target hosts. When code calls `Remote::exec("ls /srv")` without specifying a host, the transport layer consults `SshHostOps` to find the currently active vnode from the database. This allows administrators to set a target once with `chssh myserver -a` and have all subsequent operations route to that server automatically. The lookup cascades through three sources: an explicit `TARGET_HOST` environment variable, the active vnode in the database (marked with `is_active = 1`), and finally a fallback to localhost.

### The Operations Layer: *Ops.php Classes

Above the transport layer sits a collection of operations classes—`VhostOps`, `VmailOps`, `SshHostOps`, and `SshKeyOps`—that encode domain knowledge without touching the database. Each class translates high-level hosting operations into sequences of SSH commands.

Consider `VhostOps::add()`, which creates a new virtual host. The method orchestrates a complex sequence: first checking whether the path already exists, then finding the next available UID, creating the system user and group, building the directory structure (`/srv/domain/{.ssh,var,msg,web}`), generating an index.html placeholder, writing a PHP-FPM pool configuration, creating an nginx server block, and symlinking to enable the site. Each step uses the transport layer's `Remote::run()` or `Remote::exec()` methods. The operations class knows the hosting domain's rules—what directories exist, what permissions they need, what configuration formats nginx and PHP-FPM expect—but delegates all actual execution to the transport layer.

The key insight is that these operations classes work identically whether called from a CLI script or a web interface. The binary `bin/addvhost` is a thin wrapper:

```php
$result = VhostOps::add($domain, $uname);
```

The web interface's `VhostsModel::create()` calls the same method through the orchestration layer. This architectural decision eliminates the classic problem of drift between CLI and web tools—they cannot diverge because they share the same code.

### The Orchestration Layer: Vhosts.php, Vmails.php

The orchestration layer bridges filesystem operations with database persistence. Classes like `Vhosts.php` and `Vmails.php` coordinate between the operations layer (SSH commands on remote servers) and local SQLite databases that track what exists.

The method `Vhosts::add()` demonstrates this coordination. It first validates the domain format using a regex pattern, then checks the local database to prevent duplicates, generates a username from the domain if not provided, calls `VhostOps::add()` to create the filesystem structure remotely, and only after successful filesystem creation inserts a record into the database. If the database insert fails, the method rolls back by calling `VhostOps::del()` to remove what was just created. This transactional approach maintains consistency between the remote filesystem state and the local database record.

The orchestration layer also handles operations that span both domains. Deleting a virtual host means removing the database record, but also calling `VhostOps::del()` to clean up the filesystem. More subtly, deleting a vhost cascades to delete associated mailboxes and aliases from the database, since those records reference the domain.

## Configuration: The .env Pattern

HCP adopts the `.env` file pattern familiar from Laravel and other frameworks, but implements it with a minimal custom parser rather than pulling in external dependencies. The `Config` class loads `.env` from the project root on first access, parsing key-value pairs and expanding tilde-prefixed paths to the user's home directory.

The configuration resolves through a clear priority chain. Environment variables set externally take precedence, then values from `.env`, and finally hardcoded defaults. This allows deployment flexibility—a production server might set `SYSADM_DB` in the environment while development uses the `.env` file.

The most interesting configuration is `TARGET_HOST`, which determines which remote server receives SSH commands. Rather than storing this in a file, HCP uses a database-driven approach. The `vnodes` table stores SSH host definitions (name, hostname, port, user, SSH key), and exactly one row has `is_active = 1`. When `Config::targetHost()` is called, it queries this table to find the active host. This means changing targets is a database update, and the current target is visible alongside other metadata like last connection time.

## SSH Host Management: SshHostOps and SshKeyOps

Managing multiple remote servers requires tracking their connection details. `SshHostOps` provides full CRUD operations for SSH host definitions stored in the `vnodes` table. Each host record contains the name (a short identifier like "ns3" or "web1"), the actual hostname or IP, the SSH port, the connecting user, and optionally an SSH key name from `~/.ssh/keys/`.

What distinguishes this from a simple database table is the bidirectional synchronization with OpenSSH configuration. After any add, update, or delete operation, `SshHostOps::generateHostFile()` writes a configuration fragment to `~/.ssh/hosts/`. These fragments follow the standard SSH config format:

```
Host ns3
    Hostname 192.168.1.100
    Port 22
    User root
    IdentityFile ~/.ssh/keys/mykey
```

The user's `~/.ssh/config` includes these fragments via `Include ~/.ssh/hosts/*`, enabling native SSH access with `ssh ns3`. This design means HCP is not a replacement for SSH—it enhances the standard tooling while providing a management layer.

`SshKeyOps` manages the key pairs themselves, operating directly on the filesystem rather than using a database. SSH keys are files, and trying to database-ify them adds complexity without benefit. The class can generate new key pairs using `ssh-keygen`, import existing keys, rename keys (updating any vnodes that reference them), and copy public keys to remote hosts. The `copyToHost()` method implements the essential `ssh-copy-id` workflow programmatically.

## Group Management and Multi-Host Operations

Real server management often requires targeting multiple hosts simultaneously. HCP supports this through vnode groups—a many-to-many relationship between hosts and named groups stored in `vnode_groups` and `vnode_group_members` tables. A host can belong to multiple groups ("webservers" and "production"), and groups can contain any number of hosts.

The `bin/runssh` script leverages groups for parallel command execution. The invocation `runssh -g production -- "apt update && apt upgrade -y"` finds all enabled hosts in the "production" group and executes the command on each. The script manages parallelism using `proc_open()`, limiting concurrent SSH sessions to prevent resource exhaustion while still running commands across many servers simultaneously. Each session is monitored for completion or timeout, and results are collected and summarized.

The parallel execution in `runssh` uses process-based concurrency rather than the transport layer's connection pool. This is intentional: running the same command on 20 servers benefits from true parallelism (separate SSH processes), while running 20 different commands on the same server benefits from connection reuse (single SSH session). The architecture supports both patterns.

## CLI Tools: Thin Wrappers

The `bin/` directory contains eighteen scripts following a consistent naming convention: verb + noun. Virtual hosts use `addvhost`, `delvhost`, `shvhost`, `chvhost`. Mailboxes use `addvmail`, `delvmail`, `shvmail`, `chvmail`. SSH hosts use `addssh`, `delssh`, `shssh`, `chssh`. SSH keys use `addkey`, `delkey`, `shkey`, `chkey`. Finally, `runssh` handles multi-host execution and `ssh-import` brings existing SSH configurations into the database.

Each script is deliberately minimal—parse arguments, call library methods, format output. The `addvhost` script demonstrates this pattern in under 40 lines. It validates that it's running in CLI mode, requires the Composer autoloader, parses positional arguments for domain and optional username, optionally sets `TARGET_HOST` from the command line, calls `VhostOps::add()`, and reports the result. All business logic lives in the library layer.

The "sh" (show) scripts are slightly more complex because they handle both single-item display and list formatting. `shssh` without arguments lists all hosts in a formatted table; `shssh ns3` shows detailed information about one host; `shssh -g production` filters by group; `shssh ns3 -t` tests connectivity. But even with these variations, the script remains a thin dispatcher over library functionality.

## Web Interface: Plugin Architecture

The web interface follows the plugin architecture established in earlier SPE chapters. `Init.php` routes requests based on the `o` parameter (plugin name) to model/view pairs in the `src/Plugins/` directory. The HCP-specific plugins—Vhosts, Vmails, Auth, System—each contain a model class that calls orchestration layer methods and a view class that renders HTML.

The model layer adds HTTP-specific concerns. `VhostsModel::create()` handles both GET (show form) and POST (process submission). It validates input, calls `Vhosts::add()`, and on success redirects with a flash message. This is glue code that mediates between HTTP semantics and the domain library.

What's notable is how little the web interface differs from the CLI. Both ultimately call the same `Vhosts::add()` or `VhostOps::show()` methods. The web interface adds authentication (via `Acl::check(Acl::Admin)`), HTML rendering, and session-based navigation state, but the core operations are identical.

## Database Schema

HCP uses SQLite for local persistence, with two database files. `sysadm.db` contains the hosting data:

**vhosts** tracks virtual hosts with domain, username, uid, gid, active status, aliases (comma-separated), and timestamps. The domain serves as the natural key, though an integer id exists for foreign key relationships.

**vmails** tracks mailboxes with email address (user), password hash, home path, uid, gid, active status, and timestamps. The password hash uses SHA512-CRYPT format for Dovecot compatibility.

**valias** manages email aliases with source address, target addresses (comma-separated for multiple recipients), and active status.

**vnodes** stores SSH host definitions with name, hostname, port, user, ssh_key, label, notes, is_active (only one should be 1), enabled, last_seen_at, and timestamps.

**vnode_groups** and **vnode_group_members** implement the many-to-many relationship for grouping hosts.

`hcp.db` stores authentication data for the web interface using the same schema as chapter 08-Users.

## PHP Features in Use

HCP requires PHP 8.2 or higher and uses several modern features. The pipe operator appears in `Ctx.php` for session value processing: `trim($_REQUEST[$k]) |> htmlspecialchars(...)`. Typed class constants (PHP 8.3) define configuration defaults in `Config.php`. The `readonly` modifier on `Init` prevents accidental state mutation after construction.

The codebase consistently uses strict types, declared at the top of every file. Array type hints with union types like `?string` and `?array` document nullable returns. The `final` modifier on library classes prevents inheritance that would complicate the clean layer separation.

## Running HCP

Development requires a remote Linux server accessible via SSH key authentication. Configure `.env` with the appropriate database paths and ensure `~/.ssh/hosts/` and `~/.ssh/keys/` directories exist.

For CLI access:
```bash
cd 11-HCP
composer install
bin/ssh-import              # Import existing SSH configs
bin/addssh myserver 192.168.1.100 -a  # Add and activate a host
bin/addvhost example.com    # Create virtual host on active server
```

For web access:
```bash
cd 11-HCP/public
php -S localhost:8080
# Navigate to http://localhost:8080 and log in
```

The web interface requires authentication. Create an admin user in `hcp.db` or use the Auth plugin's registration flow if enabled.

## Design Philosophy

HCP embodies several architectural principles worth noting. The separation of transport, operations, and orchestration layers creates testable, maintainable code—you can mock `Remote` to test `VhostOps` logic without actual SSH connections. The shared library between CLI and web eliminates duplication and ensures consistency. Database-driven configuration (like the active vnode) provides visibility and queryability that file-based approaches lack.

The system assumes a specific hosting structure (`/srv/domain/{web,msg,var}`) and technology stack (nginx, PHP-FPM, Dovecot). This opinionation makes the tool useful out of the box for its target environment while remaining modifiable for others. The configuration templates in `VhostOps` and `VmailOps` are explicit and editable rather than hidden behind abstractions.

HCP represents the natural evolution of the SPE framework from a learning exercise to a practical tool. The patterns established in earlier chapters—plugins, models, views, context objects, database abstraction—scale to handle real operational complexity while remaining comprehensible to developers familiar with the simpler examples.
