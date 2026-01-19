#!/bin/bash
# Google Cloud Text-to-Speech wrapper
# Copyright (C) 2015-2026 Mark Constable (MIT License)
#
# Usage: ./google-tts.sh "text to speak" output.wav [voice]
#        ./google-tts.sh list  - List available voices
#
# Prerequisites:
#   1. Create service account: https://console.cloud.google.com/iam-admin/serviceaccounts
#   2. Download JSON key to ~/.config/google/tts-service-account.json
#   3. Enable API: https://console.cloud.google.com/apis/library/texttospeech.googleapis.com

set -euo pipefail

# Configuration
CONFIG_DIR="$HOME/.config/google"
SERVICE_ACCOUNT="$CONFIG_DIR/tts-service-account.json"
TTS_API="https://texttospeech.googleapis.com/v1/text:synthesize"

# Default voice (Neural2 - high quality, natural sounding)
DEFAULT_VOICE="en-US-Neural2-J"  # Male, natural
# Other good options:
#   en-US-Neural2-A (Male)
#   en-US-Neural2-C (Female)
#   en-US-Neural2-D (Male)
#   en-US-Neural2-F (Female)
#   en-US-Neural2-J (Male, recommended for tutorials)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[TTS]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1" >&2; exit 1; }

# Base64url encode (no padding, URL-safe)
base64url() {
    openssl base64 -e | tr -d '\n=' | tr '+/' '-_'
}

# Get access token from service account
get_access_token() {
    [[ -f "$SERVICE_ACCOUNT" ]] || log_error "Missing service account key: $SERVICE_ACCOUNT

Setup instructions:
1. Go to: https://console.cloud.google.com/iam-admin/serviceaccounts
2. Create service account (any name)
3. Click the account → Keys → Add Key → Create new key → JSON
4. Save as: $SERVICE_ACCOUNT
5. Enable API: https://console.cloud.google.com/apis/library/texttospeech.googleapis.com"

    local client_email private_key
    client_email=$(jq -r '.client_email' "$SERVICE_ACCOUNT")
    private_key=$(jq -r '.private_key' "$SERVICE_ACCOUNT")

    # Create JWT header and claims
    local now exp
    now=$(date +%s)
    exp=$((now + 3600))

    local header='{"alg":"RS256","typ":"JWT"}'
    local claims
    claims=$(jq -n \
        --arg iss "$client_email" \
        --arg scope "https://www.googleapis.com/auth/cloud-platform" \
        --arg aud "https://oauth2.googleapis.com/token" \
        --argjson iat "$now" \
        --argjson exp "$exp" \
        '{iss:$iss,scope:$scope,aud:$aud,iat:$iat,exp:$exp}')

    # Create signature input
    local header_b64 claims_b64 signature_input
    header_b64=$(echo -n "$header" | base64url)
    claims_b64=$(echo -n "$claims" | base64url)
    signature_input="${header_b64}.${claims_b64}"

    # Sign with private key
    local signature
    signature=$(echo -n "$signature_input" | openssl dgst -sha256 -sign <(echo "$private_key") | base64url)

    local jwt="${signature_input}.${signature}"

    # Exchange JWT for access token
    local response
    response=$(curl -s -X POST "https://oauth2.googleapis.com/token" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer" \
        -d "assertion=$jwt")

    local token
    token=$(echo "$response" | jq -r '.access_token')

    if [[ "$token" == "null" || -z "$token" ]]; then
        echo "$response" | jq -r '.error_description // .error // "Unknown error"' >&2
        log_error "Failed to get access token"
    fi

    echo "$token"
}

# Synthesize speech
synthesize() {
    local text="$1"
    local output_file="$2"
    local voice="${3:-$DEFAULT_VOICE}"

    log_info "Voice: $voice"
    log_info "Text: ${text:0:50}..."

    local access_token
    access_token=$(get_access_token)

    # Check if text contains SSML tags
    local request_json
    if [[ "$text" == *"<"* && "$text" == *">"* ]]; then
        # SSML input - wrap in <speak> if not already
        local ssml="$text"
        [[ "$ssml" != "<speak>"* ]] && ssml="<speak>$ssml</speak>"
        request_json=$(jq -n \
            --arg ssml "$ssml" \
            --arg voice "$voice" \
            --arg lang "${voice:0:5}" \
            '{
                input: { ssml: $ssml },
                voice: {
                    languageCode: $lang,
                    name: $voice
                },
                audioConfig: {
                    audioEncoding: "LINEAR16",
                    speakingRate: 0.95,
                    pitch: 0
                }
            }')
    else
        # Plain text input
        request_json=$(jq -n \
            --arg text "$text" \
            --arg voice "$voice" \
            --arg lang "${voice:0:5}" \
            '{
                input: { text: $text },
                voice: {
                    languageCode: $lang,
                    name: $voice
                },
                audioConfig: {
                    audioEncoding: "LINEAR16",
                    speakingRate: 0.95,
                    pitch: 0
                }
            }')
    fi

    # Call API
    local response
    response=$(curl -s -X POST "$TTS_API" \
        -H "Authorization: Bearer $access_token" \
        -H "Content-Type: application/json" \
        -d "$request_json")

    # Check for errors
    local error
    error=$(echo "$response" | jq -r '.error.message // empty')
    if [[ -n "$error" ]]; then
        log_error "API Error: $error"
    fi

    # Extract and decode audio
    local audio_content
    audio_content=$(echo "$response" | jq -r '.audioContent')

    if [[ "$audio_content" == "null" || -z "$audio_content" ]]; then
        log_error "No audio content in response"
    fi

    # Decode base64 to WAV
    echo "$audio_content" | base64 -d > "$output_file"

    local size
    size=$(du -h "$output_file" | cut -f1)
    log_info "Saved: $output_file ($size)"
}

# List available voices
list_voices() {
    local access_token
    access_token=$(get_access_token)

    echo "=== Neural2 Voices (Recommended) ==="
    curl -s "https://texttospeech.googleapis.com/v1/voices" \
        -H "Authorization: Bearer $access_token" | \
        jq -r '.voices[] | select(.name | contains("Neural2")) | select(.languageCodes[] | contains("en-US")) | "\(.name) - \(.ssmlGender)"' | sort

    echo ""
    echo "=== WaveNet Voices ==="
    curl -s "https://texttospeech.googleapis.com/v1/voices" \
        -H "Authorization: Bearer $access_token" | \
        jq -r '.voices[] | select(.name | contains("Wavenet")) | select(.languageCodes[] | contains("en-US")) | "\(.name) - \(.ssmlGender)"' | sort | head -10

    echo ""
    echo "(Use: ./google-tts.sh 'text' output.wav en-US-Neural2-J)"
}

# Main
main() {
    if [[ "${1:-}" == "list" ]]; then
        list_voices
        exit 0
    fi

    if [[ $# -lt 2 ]]; then
        echo "Usage: $0 <text> <output.wav> [voice]"
        echo "       $0 list  - List available voices"
        echo ""
        echo "Examples:"
        echo "  $0 'Hello world' test.wav"
        echo "  $0 'Hello world' test.wav en-US-Neural2-C"
        exit 1
    fi

    synthesize "$1" "$2" "${3:-}"
}

main "$@"
