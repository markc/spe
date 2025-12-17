#!/bin/bash
# youtube-upload.sh - Upload video to YouTube using OAuth credentials
# Usage: ./youtube-upload.sh <video_file> <metadata.json>

set -euo pipefail

VIDEO_FILE="${1:-}"
METADATA_FILE="${2:-}"

CONFIG_DIR="$HOME/.config/google"
CLIENT_SECRET="$CONFIG_DIR/client_secret.json"
TOKEN_FILE="$CONFIG_DIR/youtube_token.json"

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Check arguments
[[ -z "$VIDEO_FILE" ]] && log_error "Usage: $0 <video_file> <metadata.json>"
[[ -f "$VIDEO_FILE" ]] || log_error "Video file not found: $VIDEO_FILE"
[[ -z "$METADATA_FILE" ]] && METADATA_FILE="$(dirname "$VIDEO_FILE")/youtube-metadata.json"
[[ -f "$METADATA_FILE" ]] || log_error "Metadata file not found: $METADATA_FILE"
[[ -f "$CLIENT_SECRET" ]] || log_error "Client secret not found: $CLIENT_SECRET"
[[ -f "$TOKEN_FILE" ]] || log_error "Token file not found: $TOKEN_FILE"

# Extract credentials
CLIENT_ID=$(jq -r '.installed.client_id // .web.client_id' "$CLIENT_SECRET")
CLIENT_SECRET_VALUE=$(jq -r '.installed.client_secret // .web.client_secret' "$CLIENT_SECRET")
REFRESH_TOKEN=$(jq -r '.refresh_token' "$TOKEN_FILE")

log_info "Refreshing access token..."

# Refresh the access token
TOKEN_RESPONSE=$(curl -s -X POST "https://oauth2.googleapis.com/token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "client_id=$CLIENT_ID" \
    -d "client_secret=$CLIENT_SECRET_VALUE" \
    -d "refresh_token=$REFRESH_TOKEN" \
    -d "grant_type=refresh_token")

ACCESS_TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.access_token')

if [[ "$ACCESS_TOKEN" == "null" || -z "$ACCESS_TOKEN" ]]; then
    echo "$TOKEN_RESPONSE" | jq .
    log_error "Failed to refresh access token"
fi

log_success "Access token refreshed"

# Read metadata
TITLE=$(jq -r '.title' "$METADATA_FILE")
DESCRIPTION=$(jq -r '.description' "$METADATA_FILE")
TAGS=$(jq -c '.tags' "$METADATA_FILE")
CATEGORY_ID=$(jq -r '.categoryId // "28"' "$METADATA_FILE")
PRIVACY=$(jq -r '.privacyStatus // "private"' "$METADATA_FILE")
PLAYLIST_ID=$(jq -r '.playlistId // empty' "$METADATA_FILE")
PLAYLIST_POSITION=$(jq -r '.playlistPosition // 0' "$METADATA_FILE")

log_info "Uploading: $TITLE"
log_info "File: $VIDEO_FILE ($(du -h "$VIDEO_FILE" | cut -f1))"
log_info "Privacy: $PRIVACY"

# Create video metadata JSON
VIDEO_METADATA=$(jq -n \
    --arg title "$TITLE" \
    --arg description "$DESCRIPTION" \
    --argjson tags "$TAGS" \
    --arg categoryId "$CATEGORY_ID" \
    --arg privacy "$PRIVACY" \
    '{
        snippet: {
            title: $title,
            description: $description,
            tags: $tags,
            categoryId: $categoryId
        },
        status: {
            privacyStatus: $privacy,
            selfDeclaredMadeForKids: false
        }
    }')

# Upload video using resumable upload
log_info "Initiating upload..."

UPLOAD_URL=$(curl -s -X POST \
    "https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -H "X-Upload-Content-Type: video/mp4" \
    -H "X-Upload-Content-Length: $(stat -c%s "$VIDEO_FILE")" \
    -d "$VIDEO_METADATA" \
    -D - -o /dev/null | grep -i "location:" | cut -d' ' -f2 | tr -d '\r')

if [[ -z "$UPLOAD_URL" ]]; then
    log_error "Failed to initiate upload"
fi

log_info "Uploading video data..."

UPLOAD_RESPONSE=$(curl -X PUT "$UPLOAD_URL" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: video/mp4" \
    --data-binary "@$VIDEO_FILE" \
    --progress-bar)

VIDEO_ID=$(echo "$UPLOAD_RESPONSE" | jq -r '.id')

if [[ "$VIDEO_ID" == "null" || -z "$VIDEO_ID" ]]; then
    echo "$UPLOAD_RESPONSE" | jq .
    log_error "Upload failed"
fi

log_success "Video uploaded: https://youtu.be/$VIDEO_ID"

# Add to playlist if specified
if [[ -n "$PLAYLIST_ID" ]]; then
    log_info "Adding to playlist: $PLAYLIST_ID at position $PLAYLIST_POSITION"

    PLAYLIST_RESPONSE=$(curl -s -X POST \
        "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Content-Type: application/json" \
        -d "{
            \"snippet\": {
                \"playlistId\": \"$PLAYLIST_ID\",
                \"position\": $PLAYLIST_POSITION,
                \"resourceId\": {
                    \"kind\": \"youtube#video\",
                    \"videoId\": \"$VIDEO_ID\"
                }
            }
        }")

    PLAYLIST_ITEM_ID=$(echo "$PLAYLIST_RESPONSE" | jq -r '.id')

    if [[ "$PLAYLIST_ITEM_ID" != "null" && -n "$PLAYLIST_ITEM_ID" ]]; then
        log_success "Added to playlist at position $PLAYLIST_POSITION"
    else
        echo "$PLAYLIST_RESPONSE" | jq .
        log_info "Warning: Could not add to playlist (video still uploaded)"
    fi
fi

echo ""
log_success "Upload complete!"
echo "  Video URL: https://youtu.be/$VIDEO_ID"
echo "  Studio:    https://studio.youtube.com/video/$VIDEO_ID/edit"
