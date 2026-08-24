#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Google credentials for the video pipeline live in the ~/.ns secrets DB
# (domain googleapis.com, service api) — never as files in this repo.
#
#   bash youtube-auth.sh import <name> <file.json>   # store a JSON key, then shred the file
#   bash youtube-auth.sh                             # one-time YouTube OAuth → youtube-refresh-token
#   bash youtube-auth.sh code "<redirect URL>"       # finish the flow by hand if the listener missed it
#
# Names: google-tts-service-account · youtube-client-secret · youtube-refresh-token
#
# The OAuth flow needs `youtube-client-secret` already imported (a "Desktop app"
# OAuth client from console.cloud.google.com with YouTube Data API v3 enabled). It
# opens the consent page — sign in as the channel's account (ACCOUNT) — and catches
# Google's redirect on a loopback port (waits up to 10 min). Run it in your own session.
set -euo pipefail
PWDB="${PWDB:-$HOME/.ns/_etc/secrets/secrets.db}"
PORT=8765
SCOPE="https://www.googleapis.com/auth/youtube"
ACCOUNT="${ACCOUNT:-mc@netserva.org}"   # Google account that owns the channel (pre-selected at sign-in)

secret() { sqlite3 "$PWDB" "SELECT password FROM secrets WHERE domain='googleapis.com' AND service='api' AND username='$1' LIMIT 1"; }
secret_put() {   # name  json  notes   (upsert; value kept as compact JSON)
  local v n; v=$(jq -c . <<<"$2") || { echo "not valid JSON"; exit 1; }
  v="${v//\'/\'\'}"; n="${3//\'/\'\'}"
  sqlite3 "$PWDB" "INSERT INTO secrets (vnode, domain, service, username, password, notes, kind, scope, updated_at, rotated_at)
    VALUES ('', 'googleapis.com', 'api', '$1', '$v', '$n', 'api-token', 'global', datetime('now','localtime'), datetime('now','localtime'))
    ON CONFLICT(vnode, domain, service, username) DO UPDATE SET
      password=excluded.password, notes=excluded.notes, updated_at=excluded.updated_at,
      rotated_at=CASE WHEN secrets.password<>excluded.password THEN excluded.rotated_at ELSE secrets.rotated_at END;"
  echo "stored $1 in $PWDB"
}

if [ "${1:-}" = import ]; then
  [ -f "${3:-}" ] || { echo "usage: $0 import <name> <file.json>"; exit 1; }
  secret_put "$2" "$(cat "$3")" "imported from $(basename "$3") $(date +%F)"
  shred -u "$3" 2>/dev/null || rm -f "$3"
  echo "removed $3"
  exit 0
fi

CLIENT=$(secret youtube-client-secret)
[ -n "$CLIENT" ] || { echo "no youtube-client-secret in $PWDB — first: $0 import youtube-client-secret ~/Downloads/client_secret_*.json"; exit 1; }
CLIENT_ID=$(jq -r '.installed.client_id // .web.client_id' <<<"$CLIENT")
CLIENT_SECRET=$(jq -r '.installed.client_secret // .web.client_secret' <<<"$CLIENT")
REDIRECT="http://127.0.0.1:$PORT"

if [ "${1:-}" = code ]; then
  CODE=$(grep -oP 'code=\K[^& ]+' <<<"${2:-}" || true); [ -n "$CODE" ] || CODE="${2:-}"
  [ -n "$CODE" ] || { echo "usage: $0 code '<redirect URL or code>'"; exit 1; }
else
  URL="https://accounts.google.com/o/oauth2/v2/auth?client_id=$CLIENT_ID&redirect_uri=$REDIRECT&response_type=code&scope=$SCOPE&access_type=offline&prompt=consent&login_hint=$ACCOUNT"
  echo "Opening consent page (sign in as $ACCOUNT)…"
  echo "$URL"
  xdg-open "$URL" >/dev/null 2>&1 || true
  # Catch the redirect request; reply with a tiny page so the tab closes cleanly.
  # Firefox opens a speculative empty connection before the real request, which
  # would consume a one-shot listener — so keep accepting until a request carries code=.
  CODE=""; END=$((SECONDS + 600))
  while [ -z "$CODE" ] && [ $SECONDS -lt $END ]; do
    REQ=$(printf 'HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n<h2>Authorised &mdash; you can close this tab.</h2>' \
          | ncat -l 127.0.0.1 "$PORT" -w 600 2>/dev/null | head -1 || true)
    CODE=$(echo "$REQ" | grep -oP 'code=\K[^& ]+' || true)
  done
  [ -n "$CODE" ] || { echo "no code received. If the browser shows a 127.0.0.1:$PORT URL containing code=…, run: $0 code '<that URL>'"; exit 1; }
fi

RESP=$(curl -s -m 30 -X POST https://oauth2.googleapis.com/token \
  -d "code=$CODE" -d "client_id=$CLIENT_ID" -d "client_secret=$CLIENT_SECRET" \
  -d "redirect_uri=$REDIRECT" -d "grant_type=authorization_code")
RT=$(jq -r '.refresh_token // empty' <<<"$RESP")
[ -n "$RT" ] || { echo "token exchange failed:"; jq . <<<"$RESP"; exit 1; }

CHANNEL=$(curl -s -H "Authorization: Bearer $(jq -r .access_token <<<"$RESP")" \
  'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true' | jq -r '.items[0].snippet.title // "no channel found"')
secret_put youtube-refresh-token "$(jq '{refresh_token, scope, token_type}' <<<"$RESP")" "YouTube upload OAuth (channel: $CHANNEL) $(date +%F)"
echo "channel: $CHANNEL"
