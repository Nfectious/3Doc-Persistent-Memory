#!/bin/bash
#
# Valkyrie Inbox Watcher Service
# Monitors the _incoming directory for new files and processes them automatically
# Version: 4.2.0
#

# ========================================
# CONFIGURATION - UPDATE THESE VALUES
# ========================================
INBOX_DIR="/opt/valkyrie/memory/_incoming/"
PROCESSED_DIR="/opt/valkyrie/memory/_archive/"
LOG_FILE="/var/log/valkyrie_watcher.log"

# TODO: Update this URL to match your domain
# Examples:
#   - http://localhost/valkyrie/api.php?action=process_export
#   - http://aimem.yourdomain.com/api.php?action=process_export
API_URL="http://localhost/valkyrie/api.php?action=process_export"

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
    # TODO: Customize these project detection rules for your projects
    PROJECT="global"
    LOWER=$(tr '[:upper:]' '[:lower:]' < "$FILE")
    case "$LOWER" in
        *project1*) PROJECT="project1" ;;
        *project2*) PROJECT="project2" ;;
        *project3*) PROJECT="project3" ;;
        # Add more project detection patterns here
        # *keyword*) PROJECT="your_project_name" ;;
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