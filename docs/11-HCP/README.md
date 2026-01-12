# 11-HCP: Lightweight Hosting Control Panel

Chapter 11 represents a significant departure from the progressive tutorial structure that defined chapters 01-10. Where earlier chapters built incrementally toward understanding PHP architecture through a blog CMS, this chapter applies those accumulated patterns to an entirely different domain: remote server management. The Hosting Control Panel (HCP) demonstrates that the SPE framework—its plugins, models, views, themes, and context objects—can orchestrate real operational complexity while remaining comprehensible to developers familiar with the simpler examples. What makes HCP particularly instructive is how it solves a fundamental problem in systems administration: the drift between command-line tools and web interfaces. By sharing library code between both interfaces, HCP ensures they cannot diverge because they execute the same underlying methods.

## The Entry Point: Minimal Bootstrapping

The `public/index.php` file embodies the minimalism that characterizes SPE entry points, but introduces a new pattern for managing environment configuration. The file begins with the familiar strict types declaration and copyright notice, then immediately suppresses deprecation warnings with `error_reporting(E_ALL & ~E_DEPRECATED)`. This suppression exists because phpseclib3, the SSH library HCP depends on, generates deprecation notices on PHP 8.5 that would interfere with HTTP headers if output buffering isn't configured. Rather than configuring output buffering project-wide, the pragmatic solution is to silence these specific warnings at the entry point.

The dual autoloader requirement on lines 8-9 reveals HCP's relationship to the broader SPE ecosystem. The first require loads `11-HCP/vendor/autoload.php` for HCP-specific dependencies like phpseclib3. The second loads the root `vendor/autoload.php` which provides access to `SPE\App\Env`, `SPE\App\Acl`, and other shared classes. This dual-autoloader pattern allows HCP to function both as a standalone application and as part of the SPE repository. The `Env::load(__DIR__ . '/..')` call parses the `.env` file from the HCP root, populating `$_ENV` with configuration values before any other code executes.

The final line `echo new Init(new Ctx);` demonstrates the pattern established in chapter 01-Simple and refined through subsequent chapters. A `Ctx` object captures all request context—input parameters, session state, navigation structure, database connection—and the `Init` class processes that context into output. The `echo` statement invokes `Init::__toString()`, which renders either HTML or JSON depending on the `x` parameter. This single statement represents the entire request lifecycle: context creation, authentication checking, plugin routing, model execution, view rendering, and theme application.

## Context Construction: The Ctx Class

The `Ctx` class in `src/Core/Ctx.php` demonstrates how a context object can encapsulate both request state and application configuration. The constructor accepts three parameters with defaults: an email address, an output array structure, and a themes array. These defaults matter because they establish HCP's behavior when no configuration overrides them—defaulting to `noreply@localhost` for system emails and offering `TopNav` and `SideBar` as theme choices.

The session initialization on line 25 uses PHP's short-circuit evaluation: `session_status() === PHP_SESSION_NONE && session_start()`. This expression starts a session only if one isn't already active, preventing the "headers already sent" warning that would occur if `session_start()` were called unconditionally when output buffering isn't configured. The pattern appears frequently in code that might be included from multiple paths or where session state could already exist from middleware.

Flash message processing follows immediately. When a redirect includes `l` (message) and optionally `lt` (type) parameters, lines 28-29 extract these values and pass them to `Util::log()`. This enables the pattern `header('Location: ?o=Vhosts&l=Domain+created&lt=success')` to display feedback on the destination page. The `htmlspecialchars()` call prevents XSS through the flash message mechanism—an attacker could craft a malicious URL with script tags in the `l` parameter, and without escaping, the message would render as executable JavaScript.

The input array construction on lines 33-39 establishes the URL parameter contract for HCP. The `o` parameter selects the plugin (defaulting to `System` for the dashboard), `m` selects the method (defaulting to `list`), `t` selects the theme, `x` selects the output format, and `i` provides an integer identifier for CRUD operations. The `ses()` method handles parameters that should persist in the session—switching themes with `?t=SideBar` should make that choice sticky across subsequent requests.

The `ses()` method on lines 71-76 implements session-persistent parameters using the PHP 8.5 pipe operator. The expression `trim($_REQUEST[$k]) |> htmlspecialchars(...)` reads naturally as "get the request value, trim whitespace, then escape HTML." The conditional assignment stores this processed value in `$_SESSION[$k]` only if the request parameter exists; otherwise, it falls back to the existing session value or the provided default. This pattern ensures that once a user selects a theme, that selection persists until explicitly changed.

The navigation array construction in `buildNav()` demonstrates authorization-aware UI generation. The method checks `Acl::current()->can(Acl::Admin)` before populating the navigation. If the current user lacks admin privileges, the navigation array remains empty, effectively hiding all administrative functions. This approach ensures the navigation accurately reflects what the user can access—there's no point showing "Vhosts" to someone who would be redirected to login when clicking it.

## Initialization and Routing: The Init Class

The `Init` class in `src/Core/Init.php` orchestrates the request lifecycle with a focus on access control and plugin routing. The class is marked `final readonly`, preventing inheritance and ensuring immutability after construction. The `private const string NS` on line 12 establishes the namespace prefix for plugin classes, typed as `string` using PHP 8.3 syntax.

The constructor performs authorization checking before any plugin execution. Lines 27-37 implement a two-branch structure: requests to the `Auth` plugin proceed without authentication (necessary for login), while all other requests require admin privileges. The `Acl::check(Acl::Admin)` call returns false for unauthenticated users, triggering a redirect to `?o=Auth&m=login`. This pattern ensures that HCP's administrative functions are inaccessible without proper authentication.

The `routePlugin()` method handles plugin discovery and method dispatch. Rather than using filesystem scanning or autoloader inspection, it maintains an explicit whitelist of valid plugins on lines 47-55. This design choice trades dynamism for security—even if an attacker somehow created a malicious class file in the plugins directory, it wouldn't be routable unless added to this array. The explicit mapping also allows for plugin aliases if needed.

Plugin instantiation follows the SPE convention of separate model and view classes. Lines 62-63 construct the fully-qualified class names using string interpolation: `SPE\HCP\Plugins\Vhosts\VhostsModel`. The model class receives the context object and executes the requested method. If the model returns a string (perhaps an error message or redirect), that string becomes the output directly. Otherwise, the return value passes to the corresponding view method for HTML rendering.

The `__toString()` method handles output format selection. When `$x === 'json'`, the method sets the appropriate content type header and returns the JSON-encoded output array. For HTML output, it instantiates the selected theme class and calls `render()`. The theme selection includes a fallback—if the requested theme class doesn't exist, it defaults to `TopNav`. The `Util::perfLog()` call at the end writes performance metrics to the log, useful for identifying slow plugins or database queries.

## The Database Layer: HcpDb

The `HcpDb` class in `src/Core/HcpDb.php` extends PDO directly, providing the same CRUD interface established in chapter 07-PDO but with a different database path. The class exists because HCP needs two databases: `sysadm.db` for hosting data (vhosts, vmails, vnodes) and `hcp.db` for authentication (users, sessions). The authentication system expects `SPE\App\Db`, but that class connects to the blog database. Rather than complicating the shared code with conditional database selection, HCP provides its own database class.

The constructor demonstrates a subtle dependency management pattern. Line 30 calls `class_exists(Db::class)` apparently doing nothing with the result. However, this triggers the autoloader to load `SPE\App\Db`, which defines the `QueryType` enum. Without this call, using `QueryType::All` would fail with "class not found" because HcpDb itself doesn't define the enum. The `self::$dbLoaded` flag ensures this happens only once.

The database path resolution on line 34 shows environment variable precedence: `$_ENV['HCP_DB'] ?? getenv('HCP_DB') ?: __DIR__ . '/../../hcp.db'`. This three-level fallback checks `$_ENV` (populated by Env::load), then `getenv()` (for system environment variables), then defaults to a path relative to the class file. The `?:` operator differs from `??` in that it checks truthiness, not just null—an empty string from `getenv()` triggers the fallback.

The CRUD methods mirror those in `SPE\App\Db` exactly. This interface consistency means code written against the blog database can work with the HCP database by simply changing the class. The `qry()` method on lines 93-105 provides raw SQL execution when the structured CRUD methods prove insufficient. The `QueryType` enum controls whether the result is all rows, a single row, or a single column value—a pattern that eliminates the common error of calling `fetch()` when `fetchAll()` was intended.

## The Transport Layer: Remote.php

At the foundation of HCP's server management capabilities sits `Remote.php` in the `lib/` directory, a pure SSH transport layer that knows nothing about hosting concepts. Its responsibility is maintaining connections to remote servers and executing arbitrary commands. The class uses phpseclib3 for SSH communication, avoiding shell-based SSH invocations that would complicate error handling and output parsing.

The connection pool on line 19 (`private static array $pool = []`) enables efficient sequential operations. When managing a virtual host, the system might execute dozens of SSH commands—creating directories, setting permissions, writing configuration files. Opening a new SSH connection for each command would introduce unacceptable latency. Instead, `Remote` maintains active connections indexed by host name. The `connect()` method on line 30 first checks whether a valid connection already exists before establishing a new one.

Target host resolution demonstrates the layered configuration approach. When code calls `Remote::exec("ls /srv")` without specifying a host, line 34 calls `Config::activeVnode()` to find the currently selected server from the database. This allows administrators to set a target once with `chssh myserver -a` and have all subsequent operations route to that server automatically. The lookup cascades through three sources: an explicit host parameter, the active vnode in the database (where `is_active = 1`), and finally a fallback to localhost.

SSH key resolution on lines 63-78 implements a priority chain that mirrors real-world SSH configuration patterns. If the vnode record specifies an SSH key, that key takes precedence. Otherwise, the code looks for a default key named `lan` in the configured keys directory. Failing that, it falls back to the standard Ed25519 and RSA key locations in the user's `.ssh` directory. This approach allows specific keys for specific servers while maintaining sensible defaults.

The `exec()` and `run()` methods serve different use cases. `exec()` returns command output as a string, suitable for commands like `hostname -f` where you need the result. `run()` returns a boolean indicating success, suitable for commands like `mkdir -p /srv/domain` where you only care whether it worked. The distinction prevents the common error of checking a truthy string when you meant to check an exit status.

The `execMulti()` method on lines 117-138 provides sequential execution across multiple hosts. The TODO comment acknowledges that true parallel execution would be more efficient, but sequential execution is correct and simple. For operations that benefit from parallelism, the `bin/runssh` script uses process-based concurrency instead.

## The Operations Layer: VhostOps.php

Above the transport layer sits `VhostOps.php`, which encodes domain knowledge about web hosting without touching any database. Each method translates high-level hosting operations into sequences of SSH commands. Consider `add()` on lines 21-73, which creates a new virtual host. The method orchestrates a complex sequence: normalizing the domain to lowercase, generating a username from the domain if not provided, checking whether the path already exists on the remote server, finding the next available UID, creating the system user and group, building the directory structure, generating placeholder content, writing PHP-FPM and nginx configurations, and symlinking to enable the site.

The username generation on line 24 (`preg_replace('/[^a-z0-9]/', '', explode('.', $domain)[0])`) extracts the first component of the domain name and strips non-alphanumeric characters. For `example.com`, this produces `example`. For `my-site.org`, it produces `mysite`. This heuristic handles most cases sensibly while producing valid Unix usernames.

The existence check on line 29 demonstrates the transport layer abstraction. `Remote::exists($home)` hides the underlying implementation (`[ -e "/srv/domain" ]` executed via SSH), allowing the operations code to express intent rather than implementation. This separation means operations code remains readable while transport details can change—perhaps eventually supporting local operations for development.

The directory structure creation on line 44 follows a hosting convention: `{$home}/.ssh` for authorized keys, `{$home}/var/log` for access and error logs, `{$home}/var/run` for PHP-FPM sockets, `{$home}/msg` for mailboxes, and `{$home}/web/app/public` as the document root. This structure supports typical web application deployments where the public directory is distinct from application code.

The configuration templates on lines 185-241 embed nginx and PHP-FPM configurations directly in the code rather than using external template files. This approach trades flexibility for simplicity—the templates are visible and editable without understanding a template engine. The HEREDOC syntax with leading whitespace removal (PHP 7.3+) produces clean configuration files despite the indentation in the source code.

## The Orchestration Layer: Vhosts.php

The `Vhosts` class bridges filesystem operations with database persistence. Methods like `add()` on lines 41-79 coordinate between the operations layer (SSH commands on remote servers) and local SQLite databases that track what exists. The method first validates the domain format using a regex that enforces the DNS label structure, then checks the local database to prevent duplicates, generates a username if not provided, calls `VhostOps::add()` for filesystem creation, and finally inserts a database record.

The transactional approach on lines 72-76 handles partial failures. If the database insert fails after successful filesystem creation, the method calls `VhostOps::del()` to remove what was just created. This maintains consistency between the remote filesystem state and the local database record. Without this rollback, a database constraint violation would leave orphaned filesystem structures that the web interface couldn't see.

The `del()` method on lines 88-121 demonstrates cascade deletion. Removing a virtual host means deleting not just the vhost record but also associated mailboxes and aliases. The SQL patterns on lines 114-118 use LIKE queries with domain suffix matching (`%@{$domain}`) to find all related records. This approach assumes mailbox addresses follow the `user@domain` convention—a reasonable assumption for a hosting control panel.

The distinction between database-only and hybrid operations appears throughout the class. `list()` on lines 126-147 queries only the database, requiring no SSH connection. `show()` on lines 153-203 combines database records with filesystem information fetched via `VhostOps::show()`. This distinction matters for performance—listing 100 domains from the database takes milliseconds, while fetching disk usage for each would require 100 SSH command executions.

## SSH Host Management: SshHostOps

Managing multiple remote servers requires tracking their connection details. `SshHostOps` in `lib/SshHostOps.php` provides full CRUD operations for SSH host definitions stored in the `vnodes` table. Each host record contains a name (a short identifier like "ns3" or "web1"), the actual hostname or IP, the SSH port, the connecting user, and optionally an SSH key name.

What distinguishes this from a simple database table is the bidirectional synchronization with OpenSSH configuration. After any add, update, or delete operation, `generateHostFile()` on lines 204-225 writes a configuration fragment to `~/.ssh/hosts/`. These fragments follow the standard SSH config format:

```
Host ns3
    Hostname 192.168.1.100
    Port 22
    User root
    IdentityFile ~/.ssh/keys/mykey
```

The user's `~/.ssh/config` includes these fragments via `Include ~/.ssh/hosts/*`, enabling native SSH access with `ssh ns3`. This design means HCP enhances rather than replaces standard tooling—administrators can use HCP's web interface while colleagues prefer direct SSH.

The `importFromFiles()` method on lines 244-286 handles the reverse direction: parsing existing SSH host configuration files and importing them into the database. The `parseSshConfig()` private method on lines 291-319 uses regex patterns to extract Hostname, Port, User, and IdentityFile values from the standard format. This enables migration from manual SSH configuration to HCP management without losing existing setups.

The `setActive()` method on lines 171-189 implements the "current target" concept central to HCP's CLI workflow. Calling `bin/chssh ns3 -a` sets ns3 as the active host, and subsequent commands like `bin/addvhost example.com` automatically target that server. The implementation clears all `is_active` flags first (line 183), then sets the specified host active. Only one host can be active at a time.

Group management on lines 359-415 supports organizing hosts into named collections. A host can belong to multiple groups ("webservers" and "production"), and groups can contain any number of hosts. The many-to-many relationship uses `vnode_groups` and `vnode_group_members` tables. The `list()` method on lines 119-133 accepts an optional group filter, enabling queries like "all hosts in the staging group."

## CLI Tools: The Thin Wrapper Pattern

The `bin/` directory contains eighteen scripts following a consistent naming convention: verb + noun. Virtual hosts use `addvhost`, `delvhost`, `shvhost`, `chvhost`. Mailboxes use `addvmail`, `delvmail`, `shvmail`, `chvmail`. SSH hosts use `addssh`, `delssh`, `shssh`, `chssh`. SSH keys use `addkey`, `delkey`, `shkey`, `chkey`. Finally, `runssh` handles multi-host execution and `ssh-import` migrates existing configurations.

Each script is deliberately minimal. The `bin/addvhost` file demonstrates this pattern in 37 lines. After the CLI-only check and autoloader require, it parses positional arguments for domain and optional username, optionally sets `TARGET_HOST` from the command line, calls `VhostOps::add()`, and reports the result. All business logic lives in the library layer—the script is pure interface.

The "sh" (show) scripts like `bin/shssh` are more complex because they handle multiple output modes. Without arguments, the script lists all hosts in a formatted table. With a host name, it shows detailed information about that specific host. With `-g production`, it filters by group. With `-t`, it tests connectivity. But even with these variations, the script remains a dispatcher over library functionality—the actual work happens in `SshHostOps::list()`, `SshHostOps::get()`, and `SshHostOps::test()`.

## Web Interface: Plugin Integration

The web interface follows the plugin architecture established in earlier SPE chapters, but with HCP-specific plugins—Vhosts, Vmails, Auth, System—rather than blog content types. Each plugin contains a model class that calls orchestration layer methods and a view class that renders HTML.

The `VhostsModel` class in `src/Plugins/Vhosts/VhostsModel.php` demonstrates how web-specific concerns layer on top of the shared library. The `create()` method on lines 33-52 handles both GET (display form) and POST (process submission). On POST, it validates the domain using `filter_var()` with `FILTER_VALIDATE_DOMAIN`, calls `Vhosts::add()`, and redirects with a flash message on success. This is glue code mediating between HTTP semantics and the domain library.

What's notable is how little the web interface differs from CLI. Both ultimately call `Vhosts::add()`. The web interface adds form handling, HTML rendering, and session-based navigation state, but the core operation is identical. This architectural choice means any fix to domain validation in the library benefits both interfaces immediately.

## Configuration: The .env Pattern

HCP adopts the `.env` file pattern familiar from Laravel and other frameworks, but implements it with the minimal custom parser in `Config.php` rather than pulling in external dependencies. The `loadEnv()` method on lines 35-67 parses key-value pairs, skipping comments and blank lines, and expanding tilde-prefixed paths to the user's home directory.

The configuration resolves through a clear priority chain in the `get()` method: `$_ENV[$key] ?? getenv($key) ?: self::DEFAULTS[$key]`. Environment variables set externally take precedence, then values from `.env`, and finally hardcoded defaults. This allows deployment flexibility—a production server might set `SYSADM_DB` in the environment while development uses the `.env` file.

The `targetHost()` method on lines 104-128 demonstrates database-driven configuration. Rather than storing the current target in a file, HCP queries the `vnodes` table for the row with `is_active = 1`. This means changing targets is a database update visible in any tool that queries the database, and the current target appears alongside other metadata like last connection time.

## Database Schema

HCP uses SQLite for local persistence with two database files. `sysadm.db` contains hosting data:

The **vhosts** table tracks virtual hosts with domain (the natural key), username, UID/GID, active status, aliases (comma-separated for multiple server names), and timestamps. The integer ID exists primarily for foreign key relationships.

The **vmails** table tracks mailboxes with the email address as `user`, password hash in SHA512-CRYPT format for Dovecot compatibility, home path, UID/GID, active status, and timestamps.

The **valias** table manages email aliases with source address, target addresses (comma-separated for multiple recipients), and active status.

The **vnodes** table stores SSH host definitions with name, hostname, port, user, ssh_key reference, label, notes, is_active flag (only one should be true), enabled flag, last_seen_at timestamp, and standard timestamps.

The **vnode_groups** and **vnode_group_members** tables implement the many-to-many relationship for organizing hosts into groups.

The `hcp.db` file stores authentication data for the web interface using the same schema as chapter 08-Users.

## PHP Features in Use

HCP requires PHP 8.2 or higher and demonstrates several modern features. The pipe operator appears in `Ctx::ses()` for session value processing: `trim($_REQUEST[$k]) |> htmlspecialchars(...)`. This reads naturally as a data transformation pipeline, more readable than nested function calls.

Typed class constants (PHP 8.3) define configuration in `Config.php` with `private const array DEFAULTS` specifying the default values and their types. The `readonly` modifier on `Init` prevents accidental state mutation after construction. The `final` modifier on library classes prevents inheritance that would complicate the clean layer separation.

The codebase consistently uses strict types, declared at the top of every file. Nullable type hints like `?string` and `?array` document which parameters accept null. Match expressions in `HcpDb::bind()` provide exhaustive type-based binding without verbose switch statements.

## Running HCP

Development requires access to a remote Linux server accessible via SSH key authentication. Configure `.env` with appropriate database paths and ensure `~/.ssh/hosts/` and `~/.ssh/keys/` directories exist.

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

HCP embodies architectural principles that emerged through the progressive refinement of earlier chapters. The separation of transport, operations, and orchestration layers creates testable, maintainable code—you can mock `Remote` to test `VhostOps` logic without actual SSH connections. The shared library between CLI and web eliminates duplication and ensures consistency. Database-driven configuration provides visibility and queryability that file-based approaches lack.

The system assumes a specific hosting structure (`/srv/domain/{web,msg,var}`) and technology stack (nginx, PHP-FPM, Dovecot). This opinionation makes the tool useful immediately for its target environment while remaining modifiable for others. The configuration templates in `VhostOps` are explicit and editable rather than hidden behind abstraction layers.

HCP represents the natural evolution of the SPE framework from a learning exercise to a practical tool. The patterns established in earlier chapters—plugins, models, views, context objects, database abstraction—scale to handle real operational complexity while remaining comprehensible to developers who worked through the simpler examples. The same developer who understood `echo new class {}` in chapter 01-Simple can trace how that pattern evolved into `echo new Init(new Ctx)` managing remote servers.
