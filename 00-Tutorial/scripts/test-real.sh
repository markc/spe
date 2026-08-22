#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Proof run: record the real screen while a visible browser is driven fullscreen.
# Output: /tmp/spe-real.mp4
set -uo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
ZOOM="${ZOOM:-1.2}"
OUT=/tmp/spe-real.mp4

php -S 127.0.0.1:8000 "$ROOT/index.php" >/tmp/php-real.log 2>&1 &
PHP=$!

gpu-screen-recorder -w portal -restore-portal-session yes \
  -portal-session-token-filepath "$HOME/.cache/gsr-spe.token" \
  -f 30 -k h264 -q very_high -bm qp -cr full -cursor yes -o "$OUT" >/tmp/gsr.log 2>&1 &
GSR=$!
sleep 3   # let the recorder negotiate the portal/pipewire stream

BASE="http://127.0.0.1:8000/02-Styled" ZOOM="$ZOOM" node "$HERE/real-capture.mjs" >/tmp/driver.log 2>&1

sleep 1
kill -SIGINT "$GSR" 2>/dev/null || true
sleep 2
kill "$PHP" 2>/dev/null || true
echo "== done =="
ls -la "$OUT" 2>/dev/null
