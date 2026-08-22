#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Full hands-free 01-Simple episode using REAL Zen (fullscreen, driven by
# geckodriver) captured with gpu-screen-recorder, narration synced in post.
# RUN THIS IN YOUR OWN SESSION (it opens a fullscreen browser + screen capture):
#   ZOOM=1.2 bash ~/Projects/spe/00-Tutorial/scripts/make-episode-zen.sh
set -uo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
ZOOM="${ZOOM:-1}"   # 1 = trust the Zen profile's own 120% default
DIR=/tmp/ep01
mkdir -p "$DIR/aud"
TTS="$HERE/google-tts.sh"

# The 9 narration sentences (must match the scene order in real-zen.mjs).
mapfile -t NARR <<'EOF'
What you're looking at is a complete PHP web page, with working navigation across three pages and a real not-found response, and the whole thing is produced by a single PHP statement with no framework and no configuration behind it, so let's walk through exactly how that works.
The entire program is a single expression: echo, followed by a new anonymous class, so PHP builds the object and immediately prints it, and printing an object is what triggers its toString method, which means the object really has only one job — to work out which page was requested and then render itself as HTML.
All of the pages live in one typed constant that maps each name to its title and the markup that goes with it, and because it's a constant rather than a property it's fixed when the file is parsed and shared across every request instead of being rebuilt each time the page loads.
These two properties use asymmetric visibility, which arrived in PHP 8.4, so anyone can read them but only the class itself is allowed to set them, and that is exactly the guarantee you want for a value that's computed once from the request and should never be quietly changed anywhere else afterwards.
Here is the PHP 8.5 pipe operator handling the request, and you can read it straight down the page: take the query parameter, trim the whitespace, lower-case it, and fall back to the home page when nothing is left, which is the same logic you would otherwise write as awkward nested function calls, except that now it reads in the exact order the steps actually happen.
When the requested page isn't one that we recognise, the code returns a genuine 404 status rather than a 200 response that merely says not found, because that status line is what browsers, search crawlers and the test suite all rely on to decide whether the page truly exists.
Rendering the page is just another pipe, where the page names become links and the current one is marked active, and the whole result is dropped into a heredoc template, so the HTML is written plainly as HTML with no string concatenation and no separate templating engine that you would have to learn on the side.
And this is the one security habit worth carrying with you from the very first chapter: the incoming value is only ever used to look a page up, and it is never printed back into the document, so even a deliberate script-injection attempt simply becomes an unknown page and quietly returns a clean 404.
That is the entire engine in just fifty-seven lines, and every chapter from here builds on it by adding exactly one new idea, beginning with the next one, where we give the very same page a proper look with shared styling, dark mode and an application shell.
EOF

echo "synth (${#NARR[@]} lines)…"
DURS="["
for i in "${!NARR[@]}"; do
  wav="$DIR/aud/$(printf '%02d' "$i").wav"
  bash "$TTS" "${NARR[$i]}" "$wav" >/dev/null
  d=$(ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 "$wav")
  DURS="$DURS$d,"
  echo "  scene $i ${d}s"
done
DURS="${DURS%,}]"
echo "$DURS" > "$DIR/durations.json"

php -S 127.0.0.1:8000 "$ROOT/index.php" >/tmp/php-ep.log 2>&1 &
PHP=$!
trap 'kill $PHP 2>/dev/null || true' EXIT

gpu-screen-recorder -w portal -restore-portal-session yes \
  -portal-session-token-filepath "$HOME/.cache/gsr-spe.token" \
  -f 30 -k h264 -q very_high -bm qp -cr full -cursor yes -o "$DIR/screen.mp4" >/tmp/gsr.log 2>&1 &
GSR=$!
sleep 4   # let the portal/pipewire stream negotiate

BASE=http://127.0.0.1:8000 ZOOM="$ZOOM" node "$HERE/real-zen.mjs"

sleep 1
kill -SIGINT "$GSR" 2>/dev/null || true
sleep 3

echo "assembling…"
cd "$DIR"
# first scene change (blank -> scene 1) = t0
T0=$(ffmpeg -hide_banner -i screen.mp4 -vf "select='gt(scene,0.3)',showinfo" -f null - 2>&1 \
      | grep -oP 'pts_time:\K[0-9.]+' | head -1)
T0="${T0:-4.0}"
echo "  detected scene-1 start at ${T0}s"

# concat narration (back to back, from t0) and normalise
: > audio.txt
for i in "${!NARR[@]}"; do echo "file 'aud/$(printf '%02d' "$i").wav'" >> audio.txt; done
ffmpeg -y -loglevel error -f concat -safe 0 -i audio.txt -c copy a-raw.wav
ffmpeg -y -loglevel error -i a-raw.wav -af loudnorm=I=-16:TP=-1.5:LRA=11 -ar 48000 -ac 2 a.wav
ALEN=$(ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 a.wav)

# trim video from t0 for the narration length, force CFR, mux
ffmpeg -y -loglevel error -ss "$T0" -t "$ALEN" -i screen.mp4 -i a.wav \
  -fps_mode cfr -r 30 -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p \
  -c:a aac -b:a 192k -movflags +faststart -shortest episode-01.mp4

echo "== done =="
ffprobe -v error -show_entries stream=width,height,codec_name -show_entries format=duration -of default=nw=1 episode-01.mp4
ls -la "$DIR/episode-01.mp4"
