# 00-Tutorial: Video Generation System

This is **Chapter 00** — the prerequisite tooling for generating 4K screencast tutorials with AI voiceover.

## Why Chapter 00?

The SPE project is designed as a **local-first learning environment**:

- **PHP 8.5 required** — Uses pipe operator, asymmetric visibility, and other bleeding-edge features not available on typical Debian/Ubuntu servers
- **Run locally** — All chapters use `php -S localhost:8080` built-in server
- **Learn by doing** — Clone, run, modify, experiment
- **Document as you learn** — This tooling lets you create video tutorials of your journey

If you want to deploy a real application, see **09-Blog** — it's the only chapter complete enough for production use.

## Published Tutorials

Watch the tutorials on YouTube:
- [NetServa on YouTube](https://www.youtube.com/@NetServa)

## The current pipeline: episodes + series

The scripts below supersede the `spe-tutorial.sh` / Piper workflow documented further
down (kept for reference). Everything renders at 3840×2160, 30 fps, and lands in `/tmp/ep/`.

| Piece | What it does |
|-------|--------------|
| `episodes/XX-Name.json` | One chapter's scenes: a narration beat + a visual (`app` path or `code` file + highlighted lines). Vet this before rendering. |
| `scripts/make-episode.sh XX-Name` | Synthesises the narration (Google Chirp 3 HD), drives a chromeless kiosk Firefox through the scenes with geckodriver, captures the screen with `gpu-screen-recorder`, syncs and muxes → `/tmp/ep/XX-Name/episode-XX-Name.mp4`. Needs a live desktop session. |
| `episodes/intro.json`, `episodes/outro.json` | Narration beats for the series intro (what SPE is) and outro (how the video was made). |
| `cards/intro.html`, `cards/outro.html`, `cards/title.html` | The animated cards: plain HTML/CSS on a deterministic Web-Animations timeline (`cards.js`), laid out from the measured narration lengths. |
| `scripts/render-card.mjs` | Renders a card page frame-by-frame in headless Chromium (`seek(t)` + screenshot, piped into ffmpeg/VAAPI). No screen capture, no desktop needed. |
| `scripts/make-series.sh` | Builds the single tutorial: intro → (3 s title card dissolving into each episode, first second frozen to hide the desktop) ×9 → outro → `/tmp/ep/spe-tutorial.mp4`, plus `spe-tutorial-chapters.txt` with YouTube chapter timestamps. |

```bash
cd 00-Tutorial
bash scripts/make-episode.sh 07-PDO        # one episode (in your own session)
bash scripts/make-series.sh                # cards + segments + concat (headless)
bash scripts/make-series.sh titles         # or one step: titles | intro-outro | chapters | cardsegs | concat
RESYNTH=1 bash scripts/make-series.sh intro-outro   # after editing intro/outro narration
PREVIEW=2,8,14 node scripts/render-card.mjs cards/intro.html /tmp/x   # PNG stills to check a card
```

Card rendering is the slow part (~3 fps at 4K; the intro and outro take ~6 min each, a
title card ~30 s). Segments are encoded with `h264_vaapi` and joined with a stream copy.

## Requirements

### System Dependencies

```bash
# Arch/CachyOS
sudo pacman -S melt ffmpeg bc

# AUR packages
yay -S piper-tts-bin piper-voices-en-us
```

### Bun Runtime

```bash
# Install Bun
curl -fsSL https://bun.sh/install | bash

# Install dependencies (from 00-Tutorial/ directory)
cd 00-Tutorial
bun install

# Install Playwright browsers
bunx playwright install chromium
```

## Directory Structure

```
spe/
├── 00-Tutorial/            # THIS DIRECTORY - video generation tooling
│   ├── scripts/
│   │   ├── spe-tutorial.sh # Main pipeline orchestrator
│   │   ├── capture.ts      # Playwright screenshot automation
│   │   └── demo.ts         # Interactive demo capture
│   ├── package.json
│   ├── tsconfig.json
│   ├── README.md           # This file
│   └── *_tutorial.mp4      # Final videos (generated, gitignored)
│
├── 01-Simple/
│   ├── index.php           # PHP code
│   ├── README.md           # Chapter documentation
│   ├── tutorial.txt        # Narration script (versioned, PRs welcome!)
│   ├── frames/             # Screenshots (generated, gitignored)
│   └── audio/              # TTS audio (generated, gitignored)
├── ...
└── 09-Blog/
```

## Quick Start

```bash
cd spe/00-Tutorial

# Install dependencies
bun install

# Start PHP server (in another terminal)
cd ../09-Blog/public && php -S localhost:8080

# Generate a tutorial
./scripts/spe-tutorial.sh 01-Simple all
```

## Workflow

### 1. Edit Narration Script

Each chapter has a `tutorial.txt` file in its directory:

```bash
# Edit the script
nano ../01-Simple/tutorial.txt
```

Format: `duration|URL|narration text`

```txt
# Comments start with #
7|http://localhost:8080/01-Simple|Welcome to Simple PHP Engine.
6|https://github.com/markc/spe/blob/main/01-Simple/index.php#L5|The application uses PHP 8.5 features.
```

### 2. Generate Audio

```bash
./scripts/spe-tutorial.sh 01-Simple audio
```

Creates `01-Simple/audio/narration.wav` with TTS voiceover.

### 3. Capture Screenshots

```bash
bun run capture 01-Simple
```

Reads URLs from `tutorial.txt` and captures frames to `01-Simple/frames/`.

### 4. Assemble Video

```bash
./scripts/spe-tutorial.sh 01-Simple assemble
```

Creates `00-Tutorial/01-Simple_tutorial.mp4`.

### All-in-One

```bash
./scripts/spe-tutorial.sh 01-Simple all
```

## Interactive Demo Capture

For chapters with interactive elements (forms, CRUD, auth):

```bash
# Generate interaction code
bunx playwright codegen http://localhost:8080/08-Auth

# Run demo with video recording
bun run demo 08-Auth --video
```

Edit `scripts/demo.ts` to add chapter-specific interaction sequences.

## Commands Reference

| Command | Description |
|---------|-------------|
| `./scripts/spe-tutorial.sh CH all` | Full pipeline |
| `./scripts/spe-tutorial.sh CH audio` | Generate TTS audio |
| `./scripts/spe-tutorial.sh CH slides` | Create slideshow from frames |
| `./scripts/spe-tutorial.sh CH assemble` | Combine video + audio |
| `./scripts/spe-tutorial.sh CH kdenlive` | Generate Kdenlive project |
| `./scripts/spe-tutorial.sh CH render` | VAAPI hardware re-encode |
| `bun run capture CH` | Automated screenshot capture |
| `bun run demo CH` | Interactive demo capture |
| `bun run codegen URL` | Generate Playwright code |

## Video Settings

| Setting | Value |
|---------|-------|
| Resolution | 3840x2160 (4K UHD) |
| Frame Rate | 30 fps |
| Video Codec | H.264 (libx264) |
| Audio Codec | AAC 192kbps |
| TTS Voice | Piper hfc_male |

## Contributing

**Narration script improvements are welcome!**

Each chapter's `tutorial.txt` is versioned and visible right next to the PHP code. If you see a way to improve the explanations:

1. Edit the `tutorial.txt` file
2. Submit a PR

No PHP knowledge required — just improve the prose!

## License

MIT License - Copyright (C) 2025 Mark Constable <mc@netserva.org>
