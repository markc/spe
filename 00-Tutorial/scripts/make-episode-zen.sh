#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Build a chapter episode hands-free: synth narration (Chirp 3 HD), drive a
# chromeless kiosk Firefox (geckodriver) through the vetted scenes in
# episodes/<chapter>.json, capture with gpu-screen-recorder, assemble a 4K MP4 (no captions).
# RUN IN YOUR OWN SESSION (opens fullscreen browser + screen capture):
#   [RESYNTH=1] bash ~/Projects/spe/00-Tutorial/scripts/make-episode-zen.sh [chapter]
set -uo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
CHAP="${1:-01-Simple}"
JSON="$ROOT/00-Tutorial/episodes/$CHAP.json"
DIR="/tmp/ep/$CHAP"
TTS="$HERE/google-tts.sh"
mkdir -p "$DIR/aud"

[ -f "$JSON" ] || { echo "no script: $JSON"; exit 1; }

# Speak-as: keep the script readable; fix only what Chirp mispronounces.
speakable() {
  echo "$1" | sed -E '
    s/PHP 8\.5/P H P eight point five/g;
    s/PHP 8\.4/P H P eight point four/g;
    s/\bPHP\b/P H P/g;
    s/\b404\b/four oh four/g;
    s/\b200\b/two hundred/g'
}

mapfile -t NARR < <(jq -r '.scenes[].narr' "$JSON")
echo "chapter $CHAP — ${#NARR[@]} scenes"

if [ -f "$DIR/durations.json" ] && [ "${RESYNTH:-0}" != 1 ]; then
  echo "reusing cached narration (RESYNTH=1 to redo)"
else
  echo "synth…"
  DURS="["
  for i in "${!NARR[@]}"; do
    wav="$DIR/aud/$(printf '%03d' "$i").wav"
    bash "$TTS" "$(speakable "${NARR[$i]}")" "$wav" >/dev/null
    d=$(ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 "$wav")
    DURS="$DURS$d,"
    echo "  scene $i ${d}s"
  done
  echo "${DURS%,}]" > "$DIR/durations.json"
fi

php -S 127.0.0.1:8000 "$ROOT/index.php" >/tmp/php-ep.log 2>&1 &
PHP=$!
PREV_DESK=$(qdbus6 org.kde.KWin /VirtualDesktopManager current 2>/dev/null || true)
restore_desk() { [ -n "${PREV_DESK:-}" ] && qdbus6 org.kde.KWin /VirtualDesktopManager current "$PREV_DESK" 2>/dev/null || true; }
trap 'kill $PHP 2>/dev/null || true; restore_desk' EXIT
if [ "${SWITCH_DESKTOP:-0}" = 1 ]; then
  T=$(gdbus call --session --dest org.kde.KWin --object-path /VirtualDesktopManager \
    --method org.freedesktop.DBus.Properties.Get org.kde.KWin.VirtualDesktopManager desktops 2>/dev/null \
    | grep -oP "'\K[0-9a-f-]{36}(?=', '${DESKTOP:-Desktop 5}')" || true)
  [ -n "$T" ] && { qdbus6 org.kde.KWin /VirtualDesktopManager current "$T" 2>/dev/null || true; sleep 1; }
fi

gpu-screen-recorder -w portal -restore-portal-session yes \
  -portal-session-token-filepath "$HOME/.cache/gsr-spe.token" \
  -f 30 -k h264 -q very_high -bm qp -cr full -cursor yes -o "$DIR/screen.mp4" >/tmp/gsr.log 2>&1 &
GSR=$!
sleep 4

pkill -f 'firefox.*marionette' 2>/dev/null || true   # clear any stale capture instance
sleep 1
echo "driving kiosk Firefox (log: /tmp/driver.log)…"
BASE=http://127.0.0.1:8000 node "$HERE/real-zen.mjs" "$CHAP" 2>&1 | tee /tmp/driver.log || true

sleep 1
kill -SIGINT "$GSR" 2>/dev/null || true
sleep 3

echo "assembling…"
cd "$DIR"
T0=$(ffmpeg -hide_banner -i screen.mp4 -vf "select='gt(scene,0.3)',showinfo" -f null - 2>&1 \
      | grep -oP 'pts_time:\K[0-9.]+' | head -1)
T0="${T0:-4.0}"
echo "  scene-1 start at ${T0}s"

: > audio.txt
for i in "${!NARR[@]}"; do echo "file 'aud/$(printf '%03d' "$i").wav'" >> audio.txt; done
ffmpeg -y -loglevel error -f concat -safe 0 -i audio.txt -c copy a-raw.wav
ffmpeg -y -loglevel error -i a-raw.wav -af loudnorm=I=-16:TP=-1.5:LRA=11 -ar 48000 -ac 2 a.wav
ALEN=$(ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 a.wav)

ffmpeg -y -loglevel error -ss "$T0" -t "$ALEN" -i screen.mp4 -i a.wav \
  -fps_mode cfr -r 30 -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p \
  -c:a aac -b:a 192k -movflags +faststart -shortest "episode-$CHAP.mp4"

# NO CAPTIONS: no SRT is generated (Mark: "NO CAPTIONS"). The MP4 carries video +
# audio only; nothing is burned in and no sidecar is written. (mpv was auto-loading a
# stale sidecar — remove any old episode-*.srt if one lingers.)
rm -f "episode-$CHAP.srt"

echo "== done =="
ffprobe -v error -show_entries stream=width,height,codec_name -show_entries format=duration -of default=nw=1 "episode-$CHAP.mp4"
ls -la "$DIR/episode-$CHAP.mp4"
