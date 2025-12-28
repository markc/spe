# SPE Interapp Roadmap: Chapters 11-30

**Vision:** Resurrect ARexx-style inter-application scripting for modern Linux desktops, with PHP as the orchestration layer, D-Bus as the messaging backbone, and Claude Code as an intelligent composer.

```
┌────────────────────────────────────────────────────────────────┐
│                         INTERAPP                                │
│                   "The Local Internet"                          │
│                                                                 │
│    Every app is a service. Every action is an endpoint.        │
│    Your desktop is your API.                                   │
└────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Real-Time Web Foundation (Chapters 11-14)

Before touching D-Bus, establish persistent connections and event-driven PHP.

### Chapter 11: WebSocket
**Key Addition:** Persistent bidirectional browser connections

- PHP WebSocket server using `Ratchet` or native sockets
- Browser connects, stays connected, receives push updates
- Simple chat example: multiple browser tabs, shared state
- Foundation for real-time anything

```php
// The moment PHP stops being request/response
$server->on('message', fn($conn, $msg) =>
    $server->broadcast("User said: {$msg}")
);
```

### Chapter 12: Events
**Key Addition:** Server-Sent Events (SSE) for simpler real-time

- Lightweight alternative to WebSocket (one-way push)
- Live dashboard: CPU, memory, disk updated every second
- Demonstrates PHP as long-running process
- When to use SSE vs WebSocket

### Chapter 13: Queue
**Key Addition:** Background job processing

- SQLite-backed job queue (builds on existing DB)
- Producer/consumer pattern in PHP
- Delayed tasks, retries, failure handling
- Worker daemon that processes jobs asynchronously

### Chapter 14: Daemon
**Key Addition:** PHP as a proper system service

- Supervisor/systemd integration
- Signal handling (SIGTERM, SIGHUP)
- PID files, graceful shutdown
- Logging to journald
- PHP becomes a first-class citizen of the Linux service ecosystem

---

## Phase 2: Local IPC Fundamentals (Chapters 15-18)

Introduce inter-process communication concepts before D-Bus complexity.

### Chapter 15: UnixSocket
**Key Addition:** Local process communication via Unix sockets

- PHP daemon listens on `/run/spe/socket`
- CLI client sends commands, receives responses
- Faster than TCP, proper permissions via filesystem
- The simplest form of local IPC

```php
// Server
$socket = stream_socket_server("unix:///run/spe/control.sock");
// Client
$client = stream_socket_client("unix:///run/spe/control.sock");
```

### Chapter 16: Protocol
**Key Addition:** Designing a message protocol

- JSON-RPC over Unix socket
- Request/response structure with IDs
- Error handling conventions
- Introspection: "What commands do you support?"
- First taste of service discoverability

```php
// Request
{"jsonrpc": "2.0", "method": "screenshot", "params": {"window": "active"}, "id": 1}

// Response
{"jsonrpc": "2.0", "result": "/tmp/shot-2025-01-15.png", "id": 1}
```

### Chapter 17: Registry
**Key Addition:** Service registration and discovery

- Central registry daemon tracks available services
- Services announce themselves on startup
- Clients query registry: "Who can resize images?"
- Capability-based discovery (not just named endpoints)

```php
$registry->register('imagemagick', [
    'capabilities' => ['resize', 'convert', 'annotate'],
    'socket' => '/run/spe/imagemagick.sock'
]);
```

### Chapter 18: Bridge
**Key Addition:** HTTP/WebSocket bridge to local services

- Web browser talks to local PHP services
- Authenticate via session cookie
- Route web requests to Unix socket services
- The web becomes a UI for local services

---

## Phase 3: D-Bus Integration (Chapters 19-23)

Now introduce D-Bus, but as "just another transport" - the patterns are already established.

### Chapter 19: DBusObserve
**Key Addition:** Reading the D-Bus message bus

- PHP FFI bindings to libdbus (or sd-bus)
- Monitor mode: watch messages flow
- Understand bus structure: session vs system bus
- See what KDE apps are saying to each other

```php
// Just watching - no interaction yet
$bus->observe(function($message) {
    echo "{$message->sender} → {$message->destination}: {$message->member}\n";
});
```

### Chapter 20: DBusNotify
**Key Addition:** Send desktop notifications via D-Bus

- Call `org.freedesktop.Notifications.Notify`
- First D-Bus method call from PHP
- Handle response (notification ID)
- Actions and callbacks

```php
$notifications->notify(
    summary: "SPE Alert",
    body: "Your job completed",
    actions: ["Open" => "xdg-open /path/to/result"]
);
```

### Chapter 21: DBusDolphin
**Key Addition:** Control KDE Dolphin file manager

- Open windows, navigate to paths
- Get selected files
- Trigger file operations
- Real desktop app control from PHP

```php
$dolphin = new DolphinClient();
$dolphin->openWindow('/home/user/Downloads');
$selected = $dolphin->getSelectedFiles();
```

### Chapter 22: DBusKate
**Key Addition:** Control KDE Kate text editor

- Open files at specific lines
- Insert text, run macros
- Get current document content
- Bi-directional: Kate can call PHP too

### Chapter 23: DBusPort
**Key Addition:** PHP as a D-Bus service

- Register on session bus as `org.spe.Interapp`
- Export methods that KDE apps can call
- The circle completes: PHP is now a peer

```php
// Other apps can now call us
$service->export('org.spe.Interapp', '/Screenshot', [
    'Capture' => fn($window) => $this->captureWindow($window)
]);
```

---

## Phase 4: Orchestration Layer (Chapters 24-27)

Build the interapp framework itself - tying everything together.

### Chapter 24: Ports
**Key Addition:** Unified port abstraction

- Common interface for all service types
- D-Bus services, Unix socket services, HTTP services
- Same client code regardless of transport
- Port adapters handle translation

```php
interface Port {
    public function call(string $method, array $params): mixed;
    public function introspect(): array;
}

class DBusPort implements Port { ... }
class UnixSocketPort implements Port { ... }
class HttpPort implements Port { ... }
```

### Chapter 25: Compose
**Key Addition:** Scripting across services

- Pipeline syntax for chaining operations
- Data flows between ports
- Error handling across boundaries
- The ARexx moment arrives

```php
$result = interapp()
    ->desktop()->capture(window: 'active')
    ->pipe(gimp()->resize(800, 600))
    ->pipe(nextcloud()->upload('Screenshots'))
    ->run();
```

### Chapter 26: Discover
**Key Addition:** Automatic capability discovery

- Merge D-Bus introspection with SPE registry
- "Who can handle images?" queries
- Semantic matching (resize → imagemagick OR gimp)
- The foundation for AI-driven composition

```php
$handlers = $interapp->discover('image.resize');
// Returns: [ImageMagickPort, GimpPort]
```

### Chapter 27: Record
**Key Addition:** Script generation from actions

- Watch D-Bus traffic during manual operations
- Infer command sequences
- Generate PHP script scaffolding
- "Do it once, script it forever"

---

## Phase 5: AI Integration (Chapters 28-30)

Make Claude Code a first-class citizen of the interapp ecosystem.

### Chapter 28: Introspect
**Key Addition:** Machine-readable capability export

- OpenAPI-style schema for all ports
- Argument types, return types, descriptions
- Claude Code can query: "What's available?"
- JSON schema for every discoverable action

```php
// Returns structured data Claude Code can parse
$schema = $interapp->schemaForPort('gimp');
// {
//   "name": "gimp",
//   "methods": {
//     "resize": {
//       "params": {"width": "int", "height": "int"},
//       "returns": "string (path)",
//       "description": "Resize image to given dimensions"
//     }
//   }
// }
```

### Chapter 29: Compose
**Key Addition:** AI-assisted workflow creation

- Natural language → interapp script
- Claude Code discovers available ports
- Composes script to satisfy request
- Human reviews before execution

```
User: "Take a screenshot and upload it to Nextcloud"
Claude: *queries interapp for available ports*
Claude: *finds desktop, nextcloud ports*
Claude: *composes script*
Claude: *presents for approval*
```

### Chapter 30: Agents
**Key Addition:** Persistent AI-driven automation

- Claude Code as an interapp service
- Trigger on file changes, notifications, events
- Autonomous within defined boundaries
- The Amiga dream, realized

---

## Technical Architecture

### Core Components

```
┌──────────────────────────────────────────────────────────┐
│                    interappd (daemon)                     │
│                                                           │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐     │
│  │ Registry│  │ D-Bus   │  │ HTTP    │  │ Unix    │     │
│  │ Manager │  │ Bridge  │  │ Bridge  │  │ Socket  │     │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘     │
│       │            │            │            │           │
│       └────────────┴────────────┴────────────┘           │
│                         │                                 │
│              ┌──────────┴──────────┐                     │
│              │    Port Router      │                     │
│              └──────────┬──────────┘                     │
│                         │                                 │
│       ┌─────────────────┼─────────────────┐              │
│       ▼                 ▼                 ▼              │
│  ┌─────────┐      ┌─────────┐      ┌─────────┐          │
│  │ Desktop │      │  Gimp   │      │Nextcloud│          │
│  │  Port   │      │  Port   │      │  Port   │          │
│  └─────────┘      └─────────┘      └─────────┘          │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### Directory Structure (Post Chapter 30)

```
XX-Chapter/
├── public/index.php
└── src/
    ├── Core/
    │   ├── Init.php
    │   ├── Ctx.php
    │   ├── Db.php
    │   └── Util.php
    ├── Interapp/
    │   ├── Daemon.php        # Main daemon process
    │   ├── Registry.php      # Service registry
    │   ├── Port.php          # Port interface
    │   ├── Pipeline.php      # Composition engine
    │   └── Schema.php        # Introspection schemas
    ├── Ports/
    │   ├── DBus/
    │   │   ├── DBusPort.php
    │   │   ├── DolphinPort.php
    │   │   ├── KatePort.php
    │   │   └── NotifyPort.php
    │   ├── Local/
    │   │   ├── DesktopPort.php
    │   │   └── ImageMagickPort.php
    │   └── Remote/
    │       └── NextcloudPort.php
    ├── Plugins/              # Web UI plugins
    └── Themes/               # Web UI themes
```

### Key PHP Dependencies (New)

| Chapter | Package | Purpose |
|---------|---------|---------|
| 11 | `cboden/ratchet` | WebSocket server |
| 13 | `bernard/bernard` | Job queues (or custom SQLite) |
| 19 | `php-ffi` | D-Bus bindings via libdbus |
| 24 | Custom | Port abstraction layer |

### D-Bus Services to Target

Well-documented KDE apps with rich D-Bus interfaces:

| App | D-Bus Service | Capabilities |
|-----|---------------|--------------|
| Dolphin | `org.kde.dolphin` | File browsing, selection |
| Kate | `org.kde.kate` | Text editing, documents |
| Spectacle | `org.kde.Spectacle` | Screenshots |
| KDE Connect | `org.kde.kdeconnect` | Phone integration |
| Plasma Shell | `org.kde.plasmashell` | Desktop control |

---

## Example: The Full Vision

By Chapter 30, this script becomes possible:

```php
#!/usr/bin/env interapp
<?php
// screenshot-and-share.php
// Take screenshot, annotate, upload, notify, send to phone

$result = interapp()
    ->discover('screenshot')->capture(window: 'active')
    ->pipe(discover('image.annotate')->addText(
        "Draft - " . date('Y-m-d'),
        position: 'top-right',
        color: '#ff0000'
    ))
    ->pipe(discover('cloud.upload')->upload(
        folder: 'Screenshots',
        public: true
    ))
    ->tap(fn($url) => discover('notify')->send(
        title: "Screenshot shared",
        body: $url,
        urgency: 'normal'
    ))
    ->tap(fn($url) => discover('phone')->sendClipboard($url))
    ->run();

echo "Shared at: {$result->url}\n";
```

And Claude Code could generate this from:

> "Take a screenshot of my window, add today's date in red at the top right, upload it to my cloud as a public link, show me a notification with the link, and send it to my phone's clipboard"

---

## Success Criteria

### Per-Chapter
- Each chapter is self-contained and runnable
- Clear "before and after" demonstration
- Builds on previous chapter without requiring future ones
- README explains the concept, not just the code

### Overall
- Any developer can stop at any chapter and have something useful
- D-Bus complexity is introduced gradually, not all at once
- Web UI remains functional throughout (not abandoned for CLI)
- Claude Code integration is opt-in, not required
- Works on any modern Linux with KDE Plasma

---

## Open Questions

1. **FFI vs Extension:** Write D-Bus bindings in pure PHP/FFI, or create a proper PHP extension? FFI is more portable and educational; extension is faster and cleaner.

2. **Async Model:** Fibers, ReactPHP, Amp, or raw select()? Each has trade-offs for the teaching context.

3. **Security Model:** How to handle port permissions? User-only vs system-wide? Authentication between services?

4. **Mobile Story:** KDE Connect is D-Bus accessible. Worth a dedicated chapter for phone integration?

5. **COSMIC/Wayland:** Monitor COSMIC development - their D-Bus story may diverge from KDE's.

---

## Timeline Philosophy

This plan intentionally contains no time estimates. Each chapter represents a complete idea, not a sprint. The progression is:

1. Build it
2. Document it
3. Record video (per 00-Tutorial pipeline)
4. Move on when it feels right

The Amiga scene built amazing things without deadlines. This can too.

---

*"Your desktop is your API. Your AI is your shell. Your apps are your tools. You are the orchestrator."*
