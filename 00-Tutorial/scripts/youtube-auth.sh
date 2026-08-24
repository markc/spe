#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# One-time YouTube OAuth: turn ~/.config/google/client_secret.json (a "Desktop app"
# OAuth client from console.cloud.google.com, with YouTube Data API v3 enabled) into
# ~/.config/google/youtube_token.json (a refresh token) for youtube-upload.sh.
#
# Opens the consent page in your browser; sign in as the channel's Google account
# (mc@netserva.org). Google redirects to a loopback listener here, which catches the
# code and swaps it for tokens. Run it in your own session (needs a browser).
#
#   bash scripts/youtube-auth.sh
set -euo pipefail
CFG="$HOME/.config/google"
SECRET="$CFG/client_secret.json"
TOKEN="$CFG/youtube_token.json"
PORT=8765
SCOPE="https://www.googleapis.com/auth/youtube"

[ -f "$SECRET" ] || { echo "missing $SECRET — download the OAuth client JSON (Desktop app) there first"; exit 1; }
CLIENT_ID=$(jq -r '.installed.client_id // .web.client_id' "$SECRET")
CLIENT_SECRET=$(jq -r '.installed.client_secret // .web.client_secret' "$SECRET")
[ "$CLIENT_ID" != null ] || { echo "no client_id in $SECRET"; exit 1; }

REDIRECT="http://127.0.0.1:$PORT"
URL="https://accounts.google.com/o/oauth2/v2/auth?client_id=$CLIENT_ID&redirect_uri=$REDIRECT&response_type=code&scope=$SCOPE&access_type=offline&prompt=consent"

echo "Opening consent page (sign in as the channel owner)…"
echo "$URL"
xdg-open "$URL" >/dev/null 2>&1 || true

# Catch the single redirect request; reply with a tiny page so the tab closes cleanly.
REQ=$(printf 'HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n<h2>Authorised &mdash; you can close this tab.</h2>' \
      | ncat -l 127.0.0.1 "$PORT" -w 120 2>/dev/null | head -1)
CODE=$(echo "$REQ" | grep -oP 'code=\K[^& ]+' || true)
[ -n "$CODE" ] || { echo "no code received (request was: $REQ)"; exit 1; }

RESP=$(curl -s -m 30 -X POST https://oauth2.googleapis.com/token \
  -d "code=$CODE" -d "client_id=$CLIENT_ID" -d "client_secret=$CLIENT_SECRET" \
  -d "redirect_uri=$REDIRECT" -d "grant_type=authorization_code")
RT=$(echo "$RESP" | jq -r '.refresh_token // empty')
[ -n "$RT" ] || { echo "token exchange failed:"; echo "$RESP" | jq .; exit 1; }

( umask 077; echo "$RESP" | jq '{refresh_token, scope, token_type}' > "$TOKEN" )
echo "saved $TOKEN"
echo "check: $(curl -s -H "Authorization: Bearer $(echo "$RESP" | jq -r .access_token)" \
  'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true' | jq -r '.items[0].snippet.title // "no channel found"')"
