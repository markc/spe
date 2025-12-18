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
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Show detailed API error with context
show_api_error() {
    local context="$1"
    local response="$2"
    local request_data="${3:-}"

    echo ""
    echo -e "${RED}=== API Error: $context ===${NC}"
    echo ""

    # Try to extract and highlight the error message
    local error_msg=$(echo "$response" | jq -r '.error.message // empty' 2>/dev/null)
    local error_reason=$(echo "$response" | jq -r '.error.errors[0].reason // empty' 2>/dev/null)

    if [[ -n "$error_msg" ]]; then
        echo -e "${RED}Error:${NC} $error_msg"
        [[ -n "$error_reason" ]] && echo -e "${RED}Reason:${NC} $error_reason"
        echo ""
    fi

    echo "Full Response:"
    echo "$response" | jq . 2>/dev/null || echo "$response"

    if [[ -n "$request_data" ]]; then
        echo ""
        echo "Request Data:"
        echo "$request_data" | jq . 2>/dev/null || echo "$request_data"
    fi
    echo ""
}

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
TOKEN_RESPONSE=$(curl -s -m 30 -X POST "https://oauth2.googleapis.com/token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "client_id=$CLIENT_ID" \
    -d "client_secret=$CLIENT_SECRET_VALUE" \
    -d "refresh_token=$REFRESH_TOKEN" \
    -d "grant_type=refresh_token")

ACCESS_TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.access_token')

if [[ "$ACCESS_TOKEN" == "null" || -z "$ACCESS_TOKEN" ]]; then
    show_api_error "Token Refresh" "$TOKEN_RESPONSE"
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

# Create video metadata JSON (use temp file to avoid escaping issues)
METADATA_TMP=$(mktemp)
jq -n \
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
    }' > "$METADATA_TMP"

# Upload video using resumable upload
log_info "Initiating upload..."

# Use temp files to capture both headers and body
HEADERS_TMP=$(mktemp)
BODY_TMP=$(mktemp)

curl -s -m 60 -X POST \
    "https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -H "X-Upload-Content-Type: video/mp4" \
    -H "X-Upload-Content-Length: $(stat -c%s "$VIDEO_FILE")" \
    -d @"$METADATA_TMP" \
    -D "$HEADERS_TMP" \
    -o "$BODY_TMP"

UPLOAD_URL=$(grep -i "location:" "$HEADERS_TMP" 2>/dev/null | cut -d' ' -f2 | tr -d '\r' || true)

if [[ -z "$UPLOAD_URL" ]]; then
    BODY_CONTENT=$(cat "$BODY_TMP")
    METADATA_CONTENT=$(cat "$METADATA_TMP")
    show_api_error "Upload Initiation" "$BODY_CONTENT" "$METADATA_CONTENT"
    echo "Response Headers:"
    cat "$HEADERS_TMP"
    echo ""
    rm -f "$HEADERS_TMP" "$BODY_TMP"
    log_error "Failed to initiate upload"
fi

rm -f "$HEADERS_TMP" "$BODY_TMP"

log_info "Uploading video data..."

UPLOAD_RESPONSE=$(curl -X PUT "$UPLOAD_URL" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: video/mp4" \
    --data-binary "@$VIDEO_FILE" \
    --progress-bar)

VIDEO_ID=$(echo "$UPLOAD_RESPONSE" | jq -r '.id')

if [[ "$VIDEO_ID" == "null" || -z "$VIDEO_ID" ]]; then
    show_api_error "Video Upload" "$UPLOAD_RESPONSE"
    log_error "Video upload failed"
fi

log_success "Video uploaded: https://youtu.be/$VIDEO_ID"

# Add to playlist if specified
if [[ -n "$PLAYLIST_ID" ]]; then
    log_info "Adding to playlist: $PLAYLIST_ID at position $PLAYLIST_POSITION"

    PLAYLIST_RESPONSE=$(curl -s -m 30 -X POST \
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
        show_api_error "Playlist Add" "$PLAYLIST_RESPONSE"
        log_warn "Could not add to playlist (video still uploaded successfully)"
    fi
fi

# Cleanup temp file
rm -f "$METADATA_TMP"

echo ""
log_success "Upload complete!"
echo "  Video URL: https://youtu.be/$VIDEO_ID"
echo "  Studio:    https://studio.youtube.com/video/$VIDEO_ID/edit"
