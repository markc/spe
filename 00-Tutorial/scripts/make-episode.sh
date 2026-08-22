#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Orchestrate a full 01-Simple episode: serve the app, drive+capture+synth via
# make-episode.mjs, then assemble a 4K MP4 with synced, loudness-normalised
# narration. Output: /tmp/ep01/episode-01.mp4
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
DIR=/tmp/ep01

php -S 127.0.0.1:8000 "$ROOT/index.php" >/tmp/php-ep.log 2>&1 &
PHP=$!
trap 'kill $PHP 2>/dev/null || true' EXIT
sleep 1

BASE=http://127.0.0.1:8000 node "$HERE/make-episode.mjs"

cd "$DIR"
LEAD=$(cat lead.sec)

echo "assembling…"
# lead-in silence matched to the TTS wav format (24 kHz mono s16)
ffmpeg -y -loglevel error -f lavfi -t "$LEAD" -i anullsrc=r=24000:cl=mono -c:a pcm_s16le lead.wav
# video from screencast frames -> constant 30 fps 4K
ffmpeg -y -loglevel error -f concat -safe 0 -i frames.txt -fps_mode cfr -r 30 \
  -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p -movflags +faststart v.mp4
# narration: lead-in + scenes, back to back, then loudness-normalised
ffmpeg -y -loglevel error -f concat -safe 0 -i audio.txt -c copy a-raw.wav
ffmpeg -y -loglevel error -i a-raw.wav -af loudnorm=I=-16:TP=-1.5:LRA=11 -ar 48000 -ac 2 a.wav
# mux
ffmpeg -y -loglevel error -i v.mp4 -i a.wav -c:v copy -c:a aac -b:a 192k -shortest episode-01.mp4

echo "== done =="
ffprobe -v error -show_entries stream=width,height,codec_name -show_entries format=duration -of default=nw=1 episode-01.mp4
ls -la "$DIR/episode-01.mp4"
