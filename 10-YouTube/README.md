# Chapter 10 - YouTube Manager

A web GUI for managing YouTube channels, videos, and playlists using the YouTube Data API v3.

## Features

- Google OAuth authentication
- Channel statistics dashboard
- Video listing with thumbnails
- Playlist management (CRUD)
- Video upload support

## PHP 8.5 Features Demonstrated

- **Pipe operator** (`|>`) for functional transformations
- **Readonly classes** for immutable DTOs
- **Enums with methods** (Privacy)
- **First-class callables** (`...`)
- **Match expressions**
- **Constructor property promotion**
- **Typed constants**
- **`#[\Override]` attribute**

## Architecture

### Services Layer (Shared)

```
src/Services/
├── YouTubeService.php    # Main API client
├── VideoDTO.php          # Video data transfer object
├── PlaylistDTO.php       # Playlist data transfer object
├── ChannelDTO.php        # Channel data transfer object
├── Privacy.php           # Privacy enum
└── YouTubeException.php  # Custom exception
```

### Plugins

```
src/Plugins/
├── Auth/          # Google OAuth flow
├── Dashboard/     # Channel overview
├── Videos/        # Video list/view/upload
├── Playlists/     # Playlist CRUD
└── Channel/       # Channel statistics
```

## Requirements

- PHP 8.5+
- Composer
- Google API credentials

## Setup

1. Create Google Cloud project at https://console.cloud.google.com
2. Enable YouTube Data API v3
3. Create OAuth 2.0 credentials (Desktop app type)
4. Download `client_secret.json` to `~/.config/google/`

## Running

```bash
cd 10-YouTube
composer install
cd public
php -S localhost:8010
```

Open http://localhost:8010 and sign in with Google.

## Key Code Examples

### Pipe Operator with DTOs

```php
public function formattedDate(): string
{
    return $this->publishedAt
        |> strtotime(...)
        |> (fn($ts) => date('M j, Y', $ts));
}
```

### Privacy Enum

```php
enum Privacy: string
{
    case Private = 'private';
    case Unlisted = 'unlisted';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Unlisted => 'Unlisted',
            self::Public => 'Public',
        };
    }
}
```

### Readonly DTO

```php
readonly class VideoDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $thumbnail,
        public Privacy $privacy,
        public int $viewCount = 0,
    ) {}
}
```

## License

MIT License - Copyright (C) 2015-2025 Mark Constable
