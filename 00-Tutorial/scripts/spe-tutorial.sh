#!/bin/bash
# spe-tutorial.sh - Complete tutorial generation pipeline for SPE project
# Copyright (C) 2025 Mark Constable <mc@netserva.org> (MIT License)
#
# Usage: ./spe-tutorial.sh [chapter] [command]
#   chapter: 01-Simple, 02-Styled, etc. (default: 01-Simple)
#   command: all, script, audio, record, assemble, render (default: all)
#
# Requirements: melt, kdenlive, piper-tts, spectacle, asciinema, ffmpeg, agg

set -euo pipefail

# Configuration - SPE repo is the parent of tutorial/
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TUTORIAL_DIR="$(dirname "$SCRIPT_DIR")"
SPE_DIR="$(dirname "$TUTORIAL_DIR")"
CHAPTER="${1:-01-Simple}"
COMMAND="${2:-all}"
TTS_ENGINE="${3:-piper}"  # piper or google

# Directories
CHAPTER_DIR="$SPE_DIR/$CHAPTER"
FRAMES_DIR="$CHAPTER_DIR/frames"
AUDIO_DIR="$CHAPTER_DIR/audio"
RECORDINGS_DIR="$CHAPTER_DIR/recordings"
OUTPUT_DIR="$TUTORIAL_DIR"

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

# Hardware acceleration (Intel Arc via VAAPI)
VAAPI_DEVICE="/dev/dri/renderD128"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Initialize project directories
init_project() {
    log_info "Initializing project: $CHAPTER"
    mkdir -p "$FRAMES_DIR" "$AUDIO_DIR" "$RECORDINGS_DIR" "$OUTPUT_DIR"

    # Check for tutorial script file
    if [[ ! -f "$CHAPTER_DIR/tutorial.txt" ]]; then
        log_warn "No tutorial script found. Create $CHAPTER_DIR/tutorial.txt"
    fi
    log_success "Project initialized: $CHAPTER_DIR"
}

# Generate audio narration from script
generate_audio() {
    log_info "Generating audio narration (TTS: $TTS_ENGINE)..."

    local script_file="$CHAPTER_DIR/tutorial.txt"
    [[ -f "$script_file" ]] || log_error "Tutorial script not found: $script_file"

    # Clean old segment files to prevent stale data when script changes
    log_info "Cleaning old audio segments..."
    rm -f "$AUDIO_DIR"/segment_*.wav

    local segment=0
    local total_duration=0

    # Create timing file for later sync
    echo "# Audio segment timings" > "$AUDIO_DIR/timings.txt"
    echo "# segment|start_ms|duration_ms|text" >> "$AUDIO_DIR/timings.txt"

    # Process each line as a separate audio segment (format: duration|URL|text|action)
    while IFS='|' read -r duration url text action || [[ -n "$duration" ]]; do
        # Skip comments and lines without proper format
        [[ -z "$duration" || "$duration" =~ ^# ]] && continue
        [[ -z "$url" ]] && continue

        segment=$((segment + 1))
        local segment_file=$(printf "%s/segment_%03d.wav" "$AUDIO_DIR" "$segment")
        local display_duration_ms=$((duration * 1000))

        # Trim whitespace from text (action field is ignored for audio)
        text="${text#"${text%%[![:space:]]*}"}"
        text="${text%"${text##*[![:space:]]}"}"

        if [[ -z "$text" ]]; then
            # Empty text = visual pause, generate silence
            log_info "  Segment $segment: [silence ${duration}s]"
            ffmpeg -y -f lavfi -i anullsrc=r=48000:cl=mono -t "$duration" \
                -c:a pcm_s16le "$segment_file" 2>/dev/null
        else
            log_info "  Segment $segment: ${text:0:50}..."

            if [[ "$TTS_ENGINE" == "google" ]]; then
                # Generate audio with Google Cloud Neural2
                "$SCRIPT_DIR/google-tts.sh" "$text" "$segment_file" 2>/dev/null
            else
                # Generate audio with Piper (optimized settings for clear narration)
                echo "$text" | piper-tts \
                    --model "$PIPER_MODEL" \
                    --speaker "$PIPER_SPEAKER" \
                    --length-scale "$PIPER_LENGTH_SCALE" \
                    --noise-scale "$PIPER_NOISE_SCALE" \
                    --noise-w "$PIPER_NOISE_W" \
                    --sentence-silence "$PIPER_SENTENCE_SILENCE" \
                    --output_file "$segment_file" 2>/dev/null
            fi

            # Get actual duration of generated audio (ALWAYS use this for sync)
            local audio_duration
            audio_duration=$(ffprobe -v error -show_entries format=duration \
                -of default=noprint_wrappers=1:nokey=1 "$segment_file" 2>/dev/null)
            local audio_duration_ms
            audio_duration_ms=$(echo "$audio_duration * 1000" | bc | cut -d. -f1)

            # Always use actual audio duration for timing (ensures video matches audio)
            display_duration_ms=$audio_duration_ms
        fi

        # Record timing with actual audio duration
        echo "$segment|$total_duration|$display_duration_ms|$text" >> "$AUDIO_DIR/timings.txt"
        total_duration=$((total_duration + display_duration_ms))

    done < "$script_file"

    log_info "Concatenating audio segments..."

    # Create concat file
    : > "$AUDIO_DIR/concat.txt"
    for f in "$AUDIO_DIR"/segment_*.wav; do
        [[ -f "$f" ]] && echo "file '$f'" >> "$AUDIO_DIR/concat.txt"
    done

    # Concatenate all segments with professional audio processing
    ffmpeg -y -f concat -safe 0 -i "$AUDIO_DIR/concat.txt" \
        -af "acompressor=threshold=0.5:ratio=3:attack=20:release=250:makeup=2,alimiter=limit=0.95:attack=5:release=50,loudnorm=I=-16:TP=-1.5:LRA=11" \
        -ar 48000 -c:a pcm_s16le "$AUDIO_DIR/narration.wav" 2>/dev/null

    log_success "Audio generated: $AUDIO_DIR/narration.wav"
    log_info "Total segments: $segment, Duration: ${total_duration}ms"
}

# Capture screenshots using Spectacle (KDE Wayland)
capture_screenshots() {
    log_info "Screenshot capture mode"
    log_info "This will capture screenshots interactively."
    log_info "Press Enter when ready for each frame, or 'q' to quit."

    local frame=0

    while true; do
        read -rp "Capture frame $((frame + 1))? [Enter/q]: " response
        [[ "$response" == "q" ]] && break

        frame=$((frame + 1))
        local frame_file=$(printf "%s/frame_%03d.png" "$FRAMES_DIR" "$frame")

        # Use spectacle for KDE Wayland
        spectacle -b -f -o "$frame_file"

        log_success "Captured: $frame_file"
    done

    log_success "Total frames captured: $frame"
}

# Record terminal session with asciinema
record_terminal() {
    log_info "Recording terminal session..."
    log_info "This will record your terminal. Type 'exit' or Ctrl+D to stop."

    local cast_file="$RECORDINGS_DIR/terminal.cast"

    # Record with asciinema
    cd "$CHAPTER_DIR"
    asciinema rec --cols 140 --rows 40 "$cast_file"

    log_success "Terminal recording saved: $cast_file"

    # Check if agg is available for conversion
    if command -v agg &>/dev/null; then
        log_info "Converting to video..."
        agg --font-size 32 --theme monokai "$cast_file" "$RECORDINGS_DIR/terminal.gif"

        # Convert to MP4
        ffmpeg -y -i "$RECORDINGS_DIR/terminal.gif" \
            -vf "scale=${WIDTH}:${HEIGHT}:flags=lanczos,format=yuv420p" \
            -c:v libx264 -preset medium -crf 18 \
            "$RECORDINGS_DIR/terminal.mp4" 2>/dev/null

        log_success "Terminal video: $RECORDINGS_DIR/terminal.mp4"
    else
        log_warn "agg not found. Install with: cargo install --git https://github.com/asciinema/agg"
    fi
}

# Create slideshow from frames (static images with proper timing)
create_slideshow() {
    log_info "Creating slideshow from frames..."

    local frame_count
    frame_count=$(find "$FRAMES_DIR" -name "frame_*.png" 2>/dev/null | wc -l)

    [[ $frame_count -eq 0 ]] && log_error "No frames found in $FRAMES_DIR"

    # Read timings if available, otherwise use default duration
    local timings_file="$AUDIO_DIR/timings.txt"

    # Create FFmpeg concat file with durations
    : > "$CHAPTER_DIR/frames.txt"

    if [[ -f "$timings_file" ]]; then
        local frame=0
        local frame_file duration_ms duration_sec
        while IFS='|' read -r seg start dur txt; do
            [[ "$seg" =~ ^# ]] && continue
            frame=$((frame + 1))
            frame_file=$(printf "%s/frame_%03d.png" "$FRAMES_DIR" "$frame")
            if [[ -f "$frame_file" ]]; then
                duration_ms="${dur:-5000}"
                duration_sec=$(awk -v ms="$duration_ms" 'BEGIN {printf "%.3f", ms / 1000}')
                log_info "  Frame $frame: ${duration_sec}s"
                echo "file '$frame_file'" >> "$CHAPTER_DIR/frames.txt"
                echo "duration $duration_sec" >> "$CHAPTER_DIR/frames.txt"
            fi
        done < "$timings_file"
    else
        # Default: 5 seconds per frame
        for f in "$FRAMES_DIR"/frame_*.png; do
            [[ -f "$f" ]] || continue
            echo "file '$f'" >> "$CHAPTER_DIR/frames.txt"
            echo "duration 5" >> "$CHAPTER_DIR/frames.txt"
        done
    fi

    # Add last frame one more time without duration (FFmpeg concat quirk - ensures last frame displays)
    local last_frame
    last_frame=$(find "$FRAMES_DIR" -name "frame_*.png" | sort | tail -1)
    [[ -n "$last_frame" ]] && echo "file '$last_frame'" >> "$CHAPTER_DIR/frames.txt"

    # Create slideshow video (no duration limit - let it match frame timings)
    ffmpeg -y -f concat -safe 0 -i "$CHAPTER_DIR/frames.txt" \
        -vf "scale=${WIDTH}:${HEIGHT}:flags=lanczos,format=nv12" \
        -c:v libx264 -preset medium -crf 18 -r $FPS \
        "$CHAPTER_DIR/slideshow.mp4" 2>/dev/null

    log_success "Slideshow created: $CHAPTER_DIR/slideshow.mp4"
}

# Assemble final video with melt (MLT framework)
assemble_with_melt() {
    log_info "Assembling video with melt..."

    local video_file="$CHAPTER_DIR/slideshow.mp4"
    local audio_file="$AUDIO_DIR/narration.wav"
    local output_file="$OUTPUT_DIR/${CHAPTER}_tutorial.mp4"

    [[ -f "$video_file" ]] || log_error "Video not found: $video_file"
    [[ -f "$audio_file" ]] || log_error "Audio not found: $audio_file"

    # Get video duration in frames
    local video_duration
    video_duration=$(ffprobe -v error -show_entries format=duration \
        -of default=noprint_wrappers=1:nokey=1 "$video_file" 2>/dev/null)
    local video_frames=$(echo "$video_duration * $FPS" | bc | cut -d. -f1)

    # Use melt for combining with crossfade intro/outro
    melt \
        -profile atsc_1080p_30 \
        color:black out=15 \
        "$video_file" in=0 out=$video_frames \
        -mix 10 -mixer luma \
        color:black out=15 \
        -mix 10 -mixer luma \
        -audio-track "$audio_file" \
        -consumer avformat:"$output_file" \
        vcodec=libx264 preset=medium crf=18 \
        acodec=aac ab=192k \
        width=$WIDTH height=$HEIGHT \
        frame_rate_num=$FPS frame_rate_den=1 \
        2>/dev/null

    log_success "Final video: $output_file"
}

# Alternative: Assemble with FFmpeg (simpler, no transitions)
assemble_with_ffmpeg() {
    log_info "Assembling video with FFmpeg..."

    local video_file="$CHAPTER_DIR/slideshow.mp4"
    local audio_file="$AUDIO_DIR/narration.wav"
    local output_file="$OUTPUT_DIR/${CHAPTER}_tutorial.mp4"

    [[ -f "$video_file" ]] || log_error "Video not found: $video_file"
    [[ -f "$audio_file" ]] || log_error "Audio not found: $audio_file"

    # Simple combination with FFmpeg
    ffmpeg -y -i "$video_file" -i "$audio_file" \
        -c:v copy -c:a aac -b:a 192k \
        -movflags +faststart \
        -shortest \
        "$output_file" 2>/dev/null

    log_success "Final video: $output_file"
}

# Generate Kdenlive project file
generate_kdenlive_project() {
    log_info "Generating Kdenlive project..."

    local project_file="$CHAPTER_DIR/${CHAPTER}.kdenlive"
    local video_file="$CHAPTER_DIR/slideshow.mp4"
    local audio_file="$AUDIO_DIR/narration.wav"

    # Get absolute paths
    video_file=$(realpath "$video_file" 2>/dev/null || echo "$video_file")
    audio_file=$(realpath "$audio_file" 2>/dev/null || echo "$audio_file")

    # Generate basic Kdenlive project XML
    cat > "$project_file" << KDENLIVE_EOF
<?xml version='1.0' encoding='utf-8'?>
<mlt LC_NUMERIC="C" producer="main_bin" version="7.24.0" root="$CHAPTER_DIR">
  <profile frame_rate_num="$FPS" sample_aspect_num="1" display_aspect_den="9" colorspace="709" progressive="1" description="HD 4K" display_aspect_num="16" frame_rate_den="1" width="$WIDTH" height="$HEIGHT" sample_aspect_den="1"/>

  <producer id="producer0" in="00:00:00.000" out="00:05:00.000">
    <property name="resource">$video_file</property>
    <property name="mlt_service">avformat-novalidate</property>
    <property name="kdenlive:clipname">Video</property>
  </producer>

  <producer id="producer1" in="00:00:00.000" out="00:05:00.000">
    <property name="resource">$audio_file</property>
    <property name="mlt_service">avformat-novalidate</property>
    <property name="kdenlive:clipname">Narration</property>
  </producer>

  <playlist id="main_bin">
    <property name="kdenlive:docproperties.version">1.1</property>
    <property name="kdenlive:docproperties.profile">atsc_2160p_30</property>
    <entry producer="producer0"/>
    <entry producer="producer1"/>
  </playlist>

  <tractor id="tractor0" in="00:00:00.000" out="00:05:00.000">
    <track producer="producer0"/>
    <track producer="producer1"/>
  </tractor>
</mlt>
KDENLIVE_EOF

    log_success "Kdenlive project: $project_file"
    log_info "Open with: kdenlive '$project_file'"
}

# Render with hardware acceleration
render_hardware() {
    log_info "Rendering with Intel VAAPI hardware acceleration..."

    local input_file="$OUTPUT_DIR/${CHAPTER}_tutorial.mp4"
    local output_file="$OUTPUT_DIR/${CHAPTER}_tutorial_hw.mp4"

    [[ -f "$input_file" ]] || log_error "Input not found: $input_file"

    ffmpeg -y -vaapi_device "$VAAPI_DEVICE" \
        -i "$input_file" \
        -vf "format=nv12,hwupload" \
        -c:v h264_vaapi -qp 20 \
        -c:a copy \
        -movflags +faststart \
        "$output_file" 2>/dev/null

    log_success "Hardware-encoded: $output_file"
}

# Show help
show_help() {
    cat << 'HELP'
SPE Tutorial Generator
======================

Usage: ./spe-tutorial.sh [chapter] [command] [tts]

Chapters:
  01-Simple    First chapter (default)
  02-Styled    Second chapter
  ...          etc.

Commands:
  all          Run full pipeline (default)
  init         Initialize project directories
  script       Edit/create tutorial script file
  audio        Generate audio from script
  capture      Capture screenshots interactively
  record       Record terminal with asciinema
  slides       Create slideshow from frames
  assemble     Combine video + audio with melt
  ffmpeg       Combine video + audio with FFmpeg
  kdenlive     Generate Kdenlive project file
  render       Re-encode with hardware acceleration
  help         Show this help

TTS Engine:
  piper        Local Piper TTS (default, free)
  google       Google Cloud Neural2 (higher quality)

Examples:
  ./spe-tutorial.sh 01-Simple audio           # Piper TTS
  ./spe-tutorial.sh 01-Simple audio google    # Google Neural2
  ./spe-tutorial.sh 01-Simple all google      # Full pipeline with Google TTS

Directory Structure:
  spe/XX-Chapter/tutorial.txt       Narration script (versioned)
  spe/XX-Chapter/frames/            Screenshot captures (generated)
  spe/XX-Chapter/audio/             TTS audio segments (generated)
  spe/00-Tutorial/XX_tutorial.mp4   Final videos (generated)

HELP
}

# Main execution
main() {
    case "$COMMAND" in
        help|-h|--help)
            show_help
            ;;
        init)
            init_project
            ;;
        script)
            init_project
            ${EDITOR:-nano} "$CHAPTER_DIR/tutorial.txt"
            ;;
        audio)
            init_project
            generate_audio
            ;;
        capture)
            init_project
            capture_screenshots
            ;;
        record)
            init_project
            record_terminal
            ;;
        slides)
            create_slideshow
            ;;
        assemble)
            create_slideshow
            assemble_with_melt
            ;;
        ffmpeg)
            create_slideshow
            assemble_with_ffmpeg
            ;;
        kdenlive)
            init_project
            generate_kdenlive_project
            ;;
        render)
            render_hardware
            ;;
        all)
            init_project
            if [[ -f "$CHAPTER_DIR/tutorial.txt" ]]; then
                generate_audio
                if [[ $(find "$FRAMES_DIR" -name "frame_*.png" 2>/dev/null | wc -l) -gt 0 ]]; then
                    create_slideshow
                    assemble_with_melt
                    generate_kdenlive_project
                    log_success "Pipeline complete!"
                else
                    log_warn "No frames found. Run: bun run capture $CHAPTER"
                fi
            else
                log_error "No tutorial script found. Create $CHAPTER_DIR/tutorial.txt first"
            fi
            ;;
        *)
            log_error "Unknown command: $COMMAND. Use 'help' for usage."
            ;;
    esac
}

main
