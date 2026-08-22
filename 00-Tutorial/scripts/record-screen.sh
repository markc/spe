#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Record the real screen (fullscreen Chrome at 120% zoom on a native 4K display)
# with gpu-screen-recorder, via the KDE Wayland portal. WYSIWYG 4K capture — what
# you see is what lands in the file.
#
# Usage:  ./record-screen.sh [output.mp4]
#         Ctrl+C to stop (finalises the file cleanly).
#
# First run pops the KDE screen-capture consent dialog once; the session token is
# saved so subsequent runs are silent.

set -u
OUT="${1:-/tmp/spe-screen-$(date +%H%M%S).mp4}"
TOKEN="$HOME/.cache/gsr-spe.token"
mkdir -p "$(dirname "$TOKEN")"

echo "Recording to: $OUT"
echo "Press Ctrl+C to stop."

exec gpu-screen-recorder \
  -w portal \
  -restore-portal-session yes \
  -portal-session-token-filepath "$TOKEN" \
  -f 30 \
  -k h264 \
  -q very_high \
  -bm qp \
  -cr full \
  -cursor yes \
  -o "$OUT"
