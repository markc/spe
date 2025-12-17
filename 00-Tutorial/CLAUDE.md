# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SPE Tutorial Video Generator is a complete pipeline for creating 4K screencast tutorials with AI voiceover for the **Simple PHP Examples** (SPE) project. The pipeline uses bash scripts for orchestration and TypeScript/Bun with Playwright for automated browser capture. Designed for KDE Plasma Wayland on CachyOS/Arch Linux with Intel Arc GPU hardware acceleration.

### Project Location

This is **Chapter 00** of the SPE project at `~/Dev/spe`. The tutorial generation scripts live in `00-Tutorial/` while the PHP chapters (01-10) are sibling directories. Each video tutorial covers one chapter, demonstrating modern PHP features and the framework's architecture.

## System Environment

### Hardware
- **CPU**: Intel Core Ultra 5 125H
- **GPU**: Intel Arc (integrated)
- **Display**: 4K (3840x2160)
- **VAAPI Device**: `/dev/dri/renderD128`

### Software
- **OS**: CachyOS (Arch-based)
- **Desktop**: KDE Plasma 6 on Wayland
- **Display Server**: KWin Wayland (NOT wlroots)

### Critical Wayland Notes

**KDE Wayland uses KWin, not wlroots.** Many Linux screen capture tools only work with wlroots-based compositors (Sway, Hyprland). The following tools do NOT work on KDE Wayland:

| Tool | Reason |
|------|--------|
| grim | wlroots only |
| slurp | wlroots only |
| wf-recorder | wlroots only |
| wl-screenrec | wlroots only |
| maim | X11 only |
| scrot | X11 only |
| xdotool | X11 only |

**Use these instead:**
- **Screenshots**: `spectacle` (KDE native)
- **Screen Recording**: OBS Studio via XDG Desktop Portal
- **Terminal Recording**: `asciinema` (universal)

## Requirements

### Pacman Packages
```bash
sudo pacman -S melt kdenlive ffmpeg asciinema bc spectacle
```

### AUR Packages
```bash
yay -S piper-tts-bin piper-voices-en-us
```

### Bun Runtime (for Playwright capture)
```bash
# Install Bun
curl -fsSL https://bun.sh/install | bash

# Install project dependencies
cd ~/Dev/spe-tutorial
bun install

# Install Playwright browsers
bunx playwright install chromium
```

### Optional
```bash
# Terminal recording to video
cargo install --git https://github.com/asciinema/agg

# OBS for screen recording (alternative to screenshots)
sudo pacman -S obs-studio
```

### Verify Installation
```bash
# Check all tools
which melt kdenlive ffmpeg piper-tts spectacle asciinema bun

# Check VAAPI
vainfo 2>/dev/null | grep -i "Driver"

# Check Piper voices
ls /usr/share/piper-voices/en/en_US/

# Check Playwright
bunx playwright --version
```

## Directory Structure

```
~/Dev/spe/
├── 00-Tutorial/               # THIS DIRECTORY - video generation tooling
│   ├── CLAUDE.md              # This file
│   ├── README.md              # User documentation
│   ├── package.json           # Bun/Node dependencies (Playwright)
│   ├── tsconfig.json          # TypeScript configuration
│   ├── youtube-metadata.json  # Default YouTube metadata template
│   ├── scripts/               # Automation scripts
│   │   ├── spe-tutorial.sh    # Main pipeline (audio, slides, assembly, render)
│   │   ├── capture.ts         # URL-driven screenshot capture (GitHub/static pages)
│   │   ├── demo.ts            # Interactive demo capture (forms, CRUD, auth flows)
│   │   └── youtube-upload.sh  # YouTube upload with OAuth + playlist support
│   └── *_tutorial.mp4         # Final rendered videos (generated, gitignored)
│
├── 01-Simple/                 # PHP chapter with tutorial assets
│   ├── index.php              # PHP source code
│   ├── README.md              # Chapter documentation
│   ├── tutorial.txt           # Narration script (duration|URL|text|action format)
│   ├── youtube-metadata.json  # YouTube upload metadata for this chapter
│   ├── frames/                # Screenshots (generated, gitignored)
│   ├── audio/                 # Generated audio (gitignored)
│   │   ├── segment_*.wav      # Individual TTS segments
│   │   ├── narration.wav      # Concatenated full audio
│   │   ├── timings.txt        # Segment timing data (actual durations)
│   │   └── concat.txt         # FFmpeg concat list
│   ├── recordings/            # Terminal recordings (gitignored)
│   ├── frames.txt             # FFmpeg concat file for slideshow (generated)
│   └── slideshow.mp4          # Video from frames (gitignored)
├── 02-Styled/
├── ...
└── 09-Blog/
```

## Scripts Reference

### spe-tutorial.sh (Main Pipeline)

The central orchestrator script. All other scripts are helper utilities.

```bash
./spe-tutorial.sh [chapter] [command]
```

#### Chapters
- `01-Simple` through `09-Blog`
- Default: `01-Simple`

#### Commands

| Command | Description |
|---------|-------------|
| `all` | Full pipeline (default) |
| `init` | Create project directories |
| `script` | Edit narration script in $EDITOR |
| `audio` | Generate TTS audio from script |
| `capture` | Interactive screenshot capture |
| `record` | Terminal recording with asciinema |
| `slides` | Create video from frames |
| `assemble` | Combine video + audio with melt |
| `ffmpeg` | Combine video + audio with FFmpeg (no transitions) |
| `kdenlive` | Generate Kdenlive project file |
| `render` | Re-encode with VAAPI hardware acceleration |
| `help` | Show usage help |

#### Configuration Variables
```bash
# Directories (auto-detected from script location)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TUTORIAL_DIR="$(dirname "$SCRIPT_DIR")"
SPE_DIR="$(dirname "$TUTORIAL_DIR")"

# Video settings (4K)
WIDTH=3840
HEIGHT=2160
FPS=30

# Audio settings (lessac voice - clearer, more natural)
PIPER_MODEL="/usr/share/piper-voices/en/en_US/lessac/medium/en_US-lessac-medium.onnx"
PIPER_SPEAKER=0
PIPER_LENGTH_SCALE=1.12       # Slower, more deliberate pace for tutorials
PIPER_NOISE_SCALE=0.33        # Low noise
PIPER_NOISE_W=0.5             # Medium phoneme variation
PIPER_SENTENCE_SILENCE=0.8    # Longer pause between sentences

# Hardware acceleration
VAAPI_DEVICE="/dev/dri/renderD128"
```

### capture.ts (Playwright Browser Capture)

Automated screenshot capture using Playwright. Reads URLs from `tutorial.txt` and captures frames for each segment.

```bash
# From project root
bun run scripts/capture.ts 01-Simple

# Or using npm script
bun run capture 01-Simple
```

#### Features
- Headless Chromium browser automation
- 3x device scale factor (1280x720 virtual → 3840x2160 output)
- Auto-hides GitHub sticky headers for cleaner screenshots
- Scrolls to line anchors (e.g., `#L42`) in GitHub code views
- Forces fresh page loads for consistent GitHub line highlighting
- **Action support**: Execute clicks, waits, or JS before screenshot

#### Script File Format for Capture
```txt
# duration|URL|narration text|action (action is optional)
5|https://github.com/user/repo/blob/main/file.php#L1-L10|Introduction to the code
6|http://localhost:8080/|Demo of the running application
5|http://localhost:8080/|Click the theme toggle|click:.theme-toggle
5|http://localhost:8080/|Show toast notification|click:.btn-success
```

#### Supported Actions
- `click:.selector` - Click element matching CSS selector
- `wait:1000` - Wait milliseconds before screenshot
- `eval:SPE.toggleTheme()` - Execute arbitrary JavaScript

#### Output
- `<chapter>/frames/frame_001.png`, `frame_002.png`, etc.

### demo.ts (Interactive Demo Capture)

Capture interactive sequences (forms, CRUD, auth) with optional video recording. Use with codegen to generate interaction code.

```bash
# Generate code by recording your interactions
bun run codegen http://localhost:8080/08-Users

# Run a chapter demo
bun run demo 08-Users

# Run with video recording
bun run demo 09-Blog --video
```

#### Workflow

1. **Generate interaction code:**
   ```bash
   bunx playwright codegen http://localhost:8080/08-Users
   ```
2. **Copy generated code** from the Playwright Inspector
3. **Paste into demo.ts** under the chapter's demo function
4. **Run the demo** to capture frames at key points

#### Features
- Visible browser (`headless: false`) for debugging
- `slowMo: 100` for watchable pacing
- Continues frame numbering from existing captures
- Optional 4K video recording to `recordings/`
- `capture(page, description)` helper for screenshots
- `pause(page, ms)` helper for narration timing

#### Adding a new chapter demo

Edit `scripts/demo.ts` and add to the `demos` object:

```typescript
'08-Users': async (page) => {
  await page.goto(`${config.baseUrl}/08-Users`);
  await capture(page, 'Users list');
  await pause(page);

  // Paste codegen output here
  await page.getByRole('link', { name: 'Add User' }).click();
  await capture(page, 'Add user form');
  await page.getByLabel('Username').fill('alice');
  await page.getByRole('button', { name: 'Create' }).click();
  await capture(page, 'User created');
}
```

## Script File Format

Narration scripts use `duration|URL|text|action` format:

```txt
# Comments start with #
# Format: duration|URL|text|action (action is optional)

5|https://github.com/user/repo/blob/main/file.php|Welcome to Simple PHP Examples.
6|https://github.com/user/repo/blob/main/file.php#L5|The application is a single anonymous class.
8|http://localhost:8080/|Let's see the demo in action.
5|http://localhost:8080/|Toggle dark mode.|click:.theme-toggle
```

- **Duration**: seconds (used for capture timing; actual audio duration used for video sync)
- **URL**: page to capture (GitHub code or local demo)
- **Text**: narration for TTS audio generation (empty = silence segment)
- **Action**: optional - `click:.selector`, `wait:1000`, or `eval:code`
- Empty lines and lines starting with `#` are ignored
- Frame durations in final video match actual audio segment durations (ensures sync)

## Video Settings

| Setting | Value |
|---------|-------|
| Resolution | 3840x2160 (4K UHD) |
| Frame Rate | 30 fps |
| Video Codec | H.264 (libx264 or h264_vaapi) |
| Video Quality | CRF 18 (high quality) |
| Audio Codec | AAC 192kbps |
| Container | MP4 with faststart |
| Colorspace | BT.709 |

## Kdenlive Project Template

The template (`templates/spe-tutorial.kdenlive`) provides:

### Profile
- 4K UHD (3840x2160) @ 30fps
- 16:9 aspect ratio
- BT.709 colorspace

### Track Layout
| Track | Name | Purpose |
|-------|------|---------|
| V2 | Titles | Text overlays, lower thirds |
| V1 | Main | Screenshots, terminal recordings |
| A1 | Narration | TTS voiceover |
| A2 | Music | Background music, sound effects |

### Transitions
- V1↔V2: qtblend composite (for overlays)
- A1+A2: Audio mix with sum

### Guide Markers
- Intro: Frame 0
- Main Content: Frame 150 (5 seconds in)
- Outro: Frame 9000 (5 minutes)

## Workflow

### Standard Tutorial Creation

```bash
cd ~/Dev/spe/00-Tutorial

# 1. Initialize project
./scripts/spe-tutorial.sh 01-Simple init

# 2. Edit narration script (duration|URL|text|action format)
./scripts/spe-tutorial.sh 01-Simple script

# 3. Generate AI voiceover
./scripts/spe-tutorial.sh 01-Simple audio

# 4. Capture screenshots (requires PHP server running)
php -S localhost:8080 -t ..  # In another terminal
bun run capture 01-Simple

# 5. Assemble final video
./scripts/spe-tutorial.sh 01-Simple assemble

# 6. Review video before upload
# Open 01-Simple_tutorial.mp4 and verify quality

# 7. Upload to YouTube
./scripts/youtube-upload.sh 01-Simple_tutorial.mp4 ../01-Simple/youtube-metadata.json
```

### Quick Pipeline (All Steps)
```bash
./scripts/spe-tutorial.sh 01-Simple all
```

### Regenerating After Script Changes
```bash
# Audio regeneration cleans old segments automatically
./scripts/spe-tutorial.sh 01-Simple audio

# Re-capture if URLs changed (clean old frames first)
rm ../01-Simple/frames/*.png
bun run capture 01-Simple

# Reassemble
./scripts/spe-tutorial.sh 01-Simple assemble
```

### Terminal Recording Workflow

For code demonstrations:

```bash
cd ~/Dev/spe/01-Simple

# Record terminal session
asciinema rec --cols 140 --rows 40 terminal.cast

# Do your demo, then exit

# Convert to video (requires agg)
agg --font-size 32 --theme monokai terminal.cast terminal.gif
ffmpeg -i terminal.gif -vf "scale=3840:2160:flags=lanczos" terminal.mp4
```

### Manual Editing with Kdenlive

```bash
# Generate project file
./scripts/spe-tutorial.sh 01-Simple kdenlive

# Open in Kdenlive
kdenlive ~/Dev/spe/01-Simple/01-Simple.kdenlive
```

### Hardware-Accelerated Re-encode

```bash
# Re-encode existing video with VAAPI
./scripts/spe-tutorial.sh 01-Simple render
```

## Timings File Format

`audio/timings.txt` records exact segment timing for synchronization:

```txt
# Audio segment timings
# segment|start_ms|duration_ms|text
1|0|6383|Welcome to Simple PHP Examples.
2|6383|8496|This tutorial demonstrates modern PHP.
3|14879|6557|Let's look at the source code.
```

- Generated during `audio` step with actual audio segment durations
- Old segment files are cleaned before regenerating (prevents stale data)
- The slideshow creator uses these durations to ensure video matches audio exactly
- Duration values are from actual audio files, not script minimums

## FFmpeg Commands Reference

### Create Slideshow from Images
```bash
ffmpeg -f concat -safe 0 -i frames.txt \
    -vf "scale=3840:2160:force_original_aspect_ratio=decrease,pad=3840:2160:(ow-iw)/2:(oh-ih)/2" \
    -c:v libx264 -preset medium -crf 18 -r 30 \
    slideshow.mp4
```

### Combine Video + Audio
```bash
ffmpeg -i video.mp4 -i audio.wav \
    -c:v copy -c:a aac -b:a 192k \
    -movflags +faststart -shortest \
    output.mp4
```

### VAAPI Hardware Encode
```bash
ffmpeg -vaapi_device /dev/dri/renderD128 \
    -i input.mp4 \
    -vf "format=nv12,hwupload" \
    -c:v h264_vaapi -qp 20 \
    -c:a copy \
    output.mp4
```

### Get Video Duration
```bash
ffprobe -v error -show_entries format=duration \
    -of default=noprint_wrappers=1:nokey=1 video.mp4
```

## Melt (MLT) Commands Reference

### Basic Render with Transitions
```bash
melt \
    color:black out=30 \                    # Black intro (30 frames)
    video.mp4 in=0 out=$frames \            # Main video
    -mix 15 -mixer luma \                   # Crossfade in
    color:black out=30 \                    # Black outro
    -mix 15 -mixer luma \                   # Crossfade out
    -audio-track audio.wav \                # Audio track
    -consumer avformat:output.mp4 \         # Output
    vcodec=libx264 preset=medium crf=18 \
    acodec=aac ab=192k \
    width=3840 height=2160 \
    frame_rate_num=30 frame_rate_den=1
```

### Key Melt Concepts
- **Producers**: Media files or generators (color:black, video.mp4)
- **Mixers**: Transition effects (luma = crossfade)
- **Consumers**: Output destinations (avformat for files)
- **Tracks**: `-audio-track` adds separate audio layer

## Piper TTS Commands Reference

### Generate Single Audio File
```bash
echo "Hello world" | piper-tts \
    --model /usr/share/piper-voices/en/en_US/lessac/medium/en_US-lessac-medium.onnx \
    --length-scale 1.12 \
    --sentence-silence 0.8 \
    --output_file hello.wav
```

### Optimized Settings for Tutorials
```bash
echo "$text" | piper-tts \
    --model /usr/share/piper-voices/en/en_US/lessac/medium/en_US-lessac-medium.onnx \
    --speaker 0 \
    --length-scale 1.12 \
    --noise-scale 0.33 \
    --noise-w 0.5 \
    --sentence-silence 0.8 \
    --output_file segment.wav
```

### List Available Voices
```bash
ls /usr/share/piper-voices/en/en_US/
# Common voices: amy, arctic, hfc_female, joe, kathleen, kristin, lessac, libritts, ljspeech, ryan
```

### Model Path Pattern
```
/usr/share/piper-voices/{lang}/{locale}/{voice}/{quality}/
{locale}-{voice}-{quality}.onnx
```

## Spectacle Commands Reference

### Screenshot Types
```bash
spectacle -b -f -o file.png    # Full screen
spectacle -b -r -o file.png    # Region (interactive)
spectacle -b -a -o file.png    # Active window
spectacle -b -m -o file.png    # Current monitor
```

### With Delay
```bash
spectacle -b -f -d 2000 -o file.png    # 2 second delay
```

## Error Handling

### Common Issues

#### "piper-tts not found"
```bash
yay -S piper-tts-bin piper-voices-en-us
```

#### "spectacle: cannot connect to display"
Ensure running under KDE Plasma session, not TTY.

#### "VAAPI: DRM device not found"
```bash
# Check device exists
ls -la /dev/dri/renderD128

# Check permissions
groups | grep -E "(video|render)"

# Add user to groups if needed
sudo usermod -aG video,render $USER
```

#### "melt: command not found"
```bash
sudo pacman -S melt
# or
sudo pacman -S kdenlive  # includes melt
```

#### "agg: command not found"
```bash
cargo install --git https://github.com/asciinema/agg
```

#### Playwright browser not found
```bash
bunx playwright install chromium
```

#### FFmpeg "hwupload: No device available"
VAAPI not properly configured. Check:
```bash
vainfo
```

### Frame/Audio Mismatch

If you have more/fewer frames than audio segments:
1. Check `audio/timings.txt` segment count
2. Ensure frame files match pattern `frame_001.png`, `frame_002.png`, etc.
3. Re-capture missing frames or adjust script

### Checkered Pattern at Video End

If the video shows a checkered/tiled pattern at the end:
1. **Stale audio segments**: Old segment files weren't cleaned. Run `audio` command again (now cleans automatically)
2. **Duration mismatch**: Video duration doesn't match audio. Check `audio/timings.txt` uses actual durations
3. **Extra frames**: More frames than segments. Delete old frames before re-capture:
   ```bash
   rm ../01-Simple/frames/*.png
   bun run capture 01-Simple
   ```

### Audio/Video Out of Sync

If narration doesn't match what's on screen:
1. Verify `audio/timings.txt` has actual audio durations (not script minimums)
2. Regenerate audio: `./scripts/spe-tutorial.sh 01-Simple audio`
3. Reassemble: `./scripts/spe-tutorial.sh 01-Simple assemble`

### PHP Server Not Running

If capture shows "Not Found" error pages:
```bash
# Start PHP server in another terminal
cd ~/Dev/spe
php -S localhost:8080
```

## YouTube Upload

### youtube-upload.sh

Upload videos to YouTube with OAuth authentication and playlist management.

```bash
./scripts/youtube-upload.sh <video_file> <metadata.json>

# Example
./scripts/youtube-upload.sh 01-Simple_tutorial.mp4 /path/to/01-Simple/youtube-metadata.json
```

#### Requirements
- OAuth credentials in `~/.config/google/client_secret.json`
- Refresh token in `~/.config/google/youtube_token.json`
- `jq` and `curl` installed

#### Metadata JSON Format
Each chapter should have a `youtube-metadata.json` file:

```json
{
  "title": "SPE Chapter 01 - Simple: Single-File PHP 8.5 Application",
  "description": "Chapter description with links and timestamps...",
  "tags": ["php", "php 8.5", "pipe operator", "tutorial"],
  "categoryId": "28",
  "privacyStatus": "public",
  "playlistId": "PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B",
  "playlistPosition": 1
}
```

#### Features
- Resumable uploads for large files
- Automatic playlist insertion at specified position
- Progress bar during upload
- Returns video URL and Studio link

### Deleting Videos
```bash
# Get access token and delete video
curl -X DELETE "https://www.googleapis.com/youtube/v3/videos?id=VIDEO_ID" \
    -H "Authorization: Bearer $ACCESS_TOKEN"
```

## Future Enhancements

### Planned Features
- [ ] Auto-generate title cards with chapter name
- [ ] Background music integration
- [ ] Animated code highlighting
- [ ] Picture-in-picture webcam overlay
- [ ] Auto-sync captions/subtitles
- [ ] Batch processing for all chapters
- [ ] OBS integration for live recording
- [x] YouTube upload automation (implemented)

### Script Templates for Remaining Chapters
- [ ] `templates/script-02-Styled.txt`
- [ ] `templates/script-03-Plugins.txt`
- [ ] `templates/script-04-Themes.txt`
- [ ] `templates/script-05-Autoload.txt`
- [ ] `templates/script-06-Session.txt`
- [ ] `templates/script-07-PDO.txt`
- [ ] `templates/script-08-Users.txt`
- [ ] `templates/script-09-Blog.txt`

## SPE Chapter Content Guide

Brief summary of what each tutorial should cover:

| Chapter | Duration | Key Points |
|---------|----------|------------|
| 01-Simple | ~2 min | Anonymous class, pipe operator, typed constants, asymmetric visibility |
| 02-Styled | ~3 min | Custom CSS, dark mode, CSS variables, toast notifications |
| 03-Plugins | ~4 min | Plugin architecture, CRUDL pattern, separation of concerns |
| 04-Themes | ~4 min | Model/View separation, theme switching, nav generation |
| 05-Autoload | ~3 min | PSR-4 autoloading, Composer, namespace organization |
| 06-Session | ~3 min | PHP sessions, flash messages, theme persistence |
| 07-PDO | ~5 min | SQLite, PDO wrapper, QueryType enum, prepared statements |
| 08-Users | ~5 min | User CRUDL, password hashing, admin features |
| 09-Blog | ~8 min | Full CMS: auth, posts, pages, categories, markdown, docs |

## Code Style for Scripts

### Bash Conventions Used
```bash
#!/bin/bash
set -euo pipefail          # Strict mode

# Uppercase for configuration
TUTORIAL_DIR="/path/to/dir"

# Lowercase for local variables
local frame_count=0

# Functions use snake_case
generate_audio() { ... }

# Color output
RED='\033[0;31m'
NC='\033[0m'
echo -e "${RED}Error${NC}"
```

### Logging Functions
```bash
log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }
```

## License

MIT License - Copyright (C) 2025 Mark Constable <mc@netserva.org>

## Related Documentation

- SPE Project: `~/Dev/spe/CLAUDE.md`
- Playwright: https://playwright.dev/docs/intro
- Bun: https://bun.sh/docs
- Kdenlive Manual: https://docs.kdenlive.org/
- MLT Framework: https://www.mltframework.org/docs/
- Piper TTS: https://github.com/rhasspy/piper
- FFmpeg Wiki: https://trac.ffmpeg.org/wiki
- Asciinema: https://docs.asciinema.org/
