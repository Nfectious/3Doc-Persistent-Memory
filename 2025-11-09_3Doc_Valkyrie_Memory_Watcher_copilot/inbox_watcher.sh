#!/bin/bash
# Valkyrie Inbox Watcher
INBOX_DIR="/opt/valkyrie/memory/_incoming/"
PROCESSED_DIR="/opt/valkyrie/memory/_archive/"
LOG_FILE="/var/log/valkyrie_watcher.log"
API_URL="http://localhost/valkyrie/api.php?action=process_export"   # adjust if your api path differs

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

mkdir -p "$INBOX_DIR" "$PROCESSED_DIR"
log "Watcher starting - monitoring $INBOX_DIR"

# Install inotifywait first: apt install inotify-tools jq
inotifywait -m -e close_write -e moved_to --format '%w%f' "$INBOX_DIR" | while read FILE; do
    # Skip temporary and hidden files
    BASENAME=$(basename "$FILE")
    if [[ "$BASENAME" =~ ^\. ]] || [[ "$BASENAME" =~ ~$ ]]; then
        log "Skipping tmp/hidden: $BASENAME"
        continue
    fi

    sleep 1   # wait for writes to finish
    if [[ ! -f "$FILE" ]]; then
        log "File vanished: $FILE"
        continue
    fi

    log "Detected: $BASENAME"

    CONTENT=$(jq -Rs '.' < "$FILE" 2>/dev/null)
    if [[ -z "$CONTENT" ]]; then
        # fallback to raw content if jq isn't present / binary data
        CONTENT=$(python3 -c "import json,sys; print(json.dumps(sys.stdin.read()))" < "$FILE")
    fi

    # Basic auto-detect heuristics (filename or content)
    PROJECT="global"
    LOWER=$(tr '[:upper:]' '[:lower:]' < "$FILE")
    case "$LOWER" in
        *ebay*|*autods*) PROJECT="ebay_autods_bot" ;;
        *phoenix*) PROJECT="phoenix_commandops" ;;
        *madison*) PROJECT="madison_mission" ;;
        *overthrow*) PROJECT="overthrow" ;;
        *truth*) PROJECT="truthvault" ;;
    esac

    # Post to API (export_text = raw file content)
    log "Posting to API (project=$PROJECT): $BASENAME"
    curl -s -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -d "{\"export_text\":$(jq -Rs . < \"$FILE\"),\"project\":\"$PROJECT\"}" >> "$LOG_FILE" 2>&1

    # Archive the file with timestamp
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    mkdir -p "$PROCESSED_DIR"
    mv "$FILE" "$PROCESSED_DIR/${BASENAME}.${TIMESTAMP}"
    log "Archived: ${BASENAME}.${TIMESTAMP}"
done