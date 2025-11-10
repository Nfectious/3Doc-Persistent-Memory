# Agent: Valkyrie Inbox Watcher — Setup Instructions

Purpose: automated, repeatable steps an AI agent (or operator) can follow to install the inbox watcher, create the systemd unit, and verify Nextcloud + API integration.

Prerequisites
- Debian/Ubuntu-like host
- API reachable at http://localhost/valkyrie/api.php?action=process_export (adjust if different)
- Nextcloud (optional) mounts /opt/valkyrie/memory into container
- User accounts: valkyrie (preferred) and web server user (www-data or UID 33)

Steps (run commands as root or with sudo)

1) Install dependencies
```bash
sudo apt update
sudo apt install -y inotify-tools jq
```

2) Create directories and set owner
```bash
sudo mkdir -p /opt/valkyrie/memory/_incoming /opt/valkyrie/memory/_archive
sudo chown -R valkyrie:valkyrie /opt/valkyrie
sudo chmod -R 775 /opt/valkyrie/memory
```

3) Deploy watcher script (/opt/valkyrie/inbox_watcher.sh)
```bash
sudo tee /opt/valkyrie/inbox_watcher.sh > /dev/null <<'SCRIPT'
#!/bin/bash
# Valkyrie Inbox Watcher
INBOX_DIR="/opt/valkyrie/memory/_incoming/"
PROCESSED_DIR="/opt/valkyrie/memory/_archive/"
LOG_FILE="/var/log/valkyrie_watcher.log"
API_URL="http://localhost/valkyrie/api.php?action=process_export"   # adjust if your api path differs

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

mkdir -p "$INBOX_DIR" "$PROCESSED_DIR"
log "Watcher starting - monitoring $INBOX_DIR"

inotifywait -m -e close_write -e moved_to --format '%w%f' "$INBOX_DIR" | while read FILE; do
    BASENAME=$(basename "$FILE")
    # skip hidden/temp files
    if [[ "$BASENAME" =~ ^\. ]] || [[ "$BASENAME" =~ ~$ ]]; then
        log "Skipping tmp/hidden: $BASENAME"
        continue
    fi

    sleep 1
    if [[ ! -f "$FILE" ]]; then
        log "File vanished: $FILE"
        continue
    fi

    log "Detected: $BASENAME"

    # Auto-detect project by filename/content heuristics
    PROJECT="global"
    LOWER=$(echo "$BASENAME" | tr '[:upper:]' '[:lower:]')
    case "$LOWER" in
        *ebay*|*autods*) PROJECT="ebay_autods_bot" ;;
        *phoenix*) PROJECT="phoenix_commandops" ;;
        *madison*) PROJECT="madison_mission" ;;
        *overthrow*) PROJECT="overthrow" ;;
        *truth*) PROJECT="truthvault" ;;
    esac

    # Post file content to API
    PAYLOAD=$(jq -Rs '.' < "$FILE" 2>/dev/null || python3 -c "import json,sys; print(json.dumps(sys.stdin.read()))" < "$FILE")
    log "Posting to API (project=$PROJECT): $BASENAME"
    curl -s -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -d "{\"export_text\":${PAYLOAD},\"project\":\"$PROJECT\"}" >> "$LOG_FILE" 2>&1

    # Archive with timestamp
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    mkdir -p "$PROCESSED_DIR"
    mv "$FILE" "$PROCESSED_DIR/${BASENAME}.${TIMESTAMP}"
    log "Archived: ${BASENAME}.${TIMESTAMP}"
done
SCRIPT

sudo chmod +x /opt/valkyrie/inbox_watcher.sh
sudo chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh
```

4) Create systemd unit (/etc/systemd/system/valkyrie-watcher.service)
```bash
sudo tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<'UNIT'
[Unit]
Description=Valkyrie Inbox Watcher
After=network.target

[Service]
Type=simple
User=valkyrie
Group=valkyrie
ExecStart=/opt/valkyrie/inbox_watcher.sh
Restart=on-failure
RestartSec=5
StandardOutput=append:/var/log/valkyrie_watcher.log
StandardError=append:/var/log/valkyrie_watcher.log

[Install]
WantedBy=multi-user.target
UNIT

sudo systemctl daemon-reload
sudo systemctl enable --now valkyrie-watcher
```

5) Adjust Nextcloud mount and ownership (if using Nextcloud)
- Ensure Nextcloud docker-compose mounts host /opt/valkyrie/memory into user files.
- Common ownership approaches:
```bash
# If Nextcloud runs as www-data (33:33) inside container:
sudo chown -R 33:33 /opt/valkyrie/memory

# Or allow both host watcher and web container:
sudo chown -R valkyrie:www-data /opt/valkyrie/memory
sudo chmod -R 775 /opt/valkyrie/memory
```

6) Verify
```bash
# Check service logs
sudo journalctl -u valkyrie-watcher -f

# API reachable
curl -s 'http://localhost/valkyrie/api.php?action=list_projects' | jq .

# Test drop
echo -e "=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest decision" > /opt/valkyrie/memory/_incoming/test.txt
# Wait; watcher should post and move file to _archive
ls -la /opt/valkyrie/memory/_archive/
tail -n 200 /var/log/valkyrie_watcher.log
```

Notes
- Update API_URL inside the watcher if web path differs.
- For Nextcloud sync issues, run: docker exec -u www-data nextcloud php occ files:scan --all
- Remove or reduce display_errors and verbose logging in production.

End.  # Agent: Valkyrie Inbox Watcher — Setup Instructions

Purpose: automated, repeatable steps an AI agent (or operator) can follow to install the inbox watcher, create the systemd unit, and verify Nextcloud + API integration.

Prerequisites
- Debian/Ubuntu-like host
- API reachable at http://localhost/valkyrie/api.php?action=process_export (adjust if different)
- Nextcloud (optional) mounts /opt/valkyrie/memory into container
- User accounts: valkyrie (preferred) and web server user (www-data or UID 33)

Steps (run commands as root or with sudo)

1) Install dependencies
```bash
sudo apt update
sudo apt install -y inotify-tools jq
```

2) Create directories and set owner
```bash
sudo mkdir -p /opt/valkyrie/memory/_incoming /opt/valkyrie/memory/_archive
sudo chown -R valkyrie:valkyrie /opt/valkyrie
sudo chmod -R 775 /opt/valkyrie/memory
```

3) Deploy watcher script (/opt/valkyrie/inbox_watcher.sh)
```bash
sudo tee /opt/valkyrie/inbox_watcher.sh > /dev/null <<'SCRIPT'
#!/bin/bash
# Valkyrie Inbox Watcher
INBOX_DIR="/opt/valkyrie/memory/_incoming/"
PROCESSED_DIR="/opt/valkyrie/memory/_archive/"
LOG_FILE="/var/log/valkyrie_watcher.log"
API_URL="http://localhost/valkyrie/api.php?action=process_export"   # adjust if your api path differs

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

mkdir -p "$INBOX_DIR" "$PROCESSED_DIR"
log "Watcher starting - monitoring $INBOX_DIR"

inotifywait -m -e close_write -e moved_to --format '%w%f' "$INBOX_DIR" | while read FILE; do
    BASENAME=$(basename "$FILE")
    # skip hidden/temp files
    if [[ "$BASENAME" =~ ^\. ]] || [[ "$BASENAME" =~ ~$ ]]; then
        log "Skipping tmp/hidden: $BASENAME"
        continue
    fi

    sleep 1
    if [[ ! -f "$FILE" ]]; then
        log "File vanished: $FILE"
        continue
    fi

    log "Detected: $BASENAME"

    # Auto-detect project by filename/content heuristics
    PROJECT="global"
    LOWER=$(echo "$BASENAME" | tr '[:upper:]' '[:lower:]')
    case "$LOWER" in
        *ebay*|*autods*) PROJECT="ebay_autods_bot" ;;
        *phoenix*) PROJECT="phoenix_commandops" ;;
        *madison*) PROJECT="madison_mission" ;;
        *overthrow*) PROJECT="overthrow" ;;
        *truth*) PROJECT="truthvault" ;;
    esac

    # Post file content to API
    PAYLOAD=$(jq -Rs '.' < "$FILE" 2>/dev/null || python3 -c "import json,sys; print(json.dumps(sys.stdin.read()))" < "$FILE")
    log "Posting to API (project=$PROJECT): $BASENAME"
    curl -s -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -d "{\"export_text\":${PAYLOAD},\"project\":\"$PROJECT\"}" >> "$LOG_FILE" 2>&1

    # Archive with timestamp
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    mkdir -p "$PROCESSED_DIR"
    mv "$FILE" "$PROCESSED_DIR/${BASENAME}.${TIMESTAMP}"
    log "Archived: ${BASENAME}.${TIMESTAMP}"
done
SCRIPT

sudo chmod +x /opt/valkyrie/inbox_watcher.sh
sudo chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh
```

4) Create systemd unit (/etc/systemd/system/valkyrie-watcher.service)
```bash
sudo tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<'UNIT'
[Unit]
Description=Valkyrie Inbox Watcher
After=network.target

[Service]
Type=simple
User=valkyrie
Group=valkyrie
ExecStart=/opt/valkyrie/inbox_watcher.sh
Restart=on-failure
RestartSec=5
StandardOutput=append:/var/log/valkyrie_watcher.log
StandardError=append:/var/log/valkyrie_watcher.log

[Install]
WantedBy=multi-user.target
UNIT

sudo systemctl daemon-reload
sudo systemctl enable --now valkyrie-watcher
```

5) Adjust Nextcloud mount and ownership (if using Nextcloud)
- Ensure Nextcloud docker-compose mounts host /opt/valkyrie/memory into user files.
- Common ownership approaches:
```bash
# If Nextcloud runs as www-data (33:33) inside container:
sudo chown -R 33:33 /opt/valkyrie/memory

# Or allow both host watcher and web container:
sudo chown -R valkyrie:www-data /opt/valkyrie/memory
sudo chmod -R 775 /opt/valkyrie/memory
```

6) Verify
```bash
# Check service logs
sudo journalctl -u valkyrie-watcher -f

# API reachable
curl -s 'http://localhost/valkyrie/api.php?action=list_projects' | jq .

# Test drop
echo -e "=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest decision" > /opt/valkyrie/memory/_incoming/test.txt
# Wait; watcher should post and move file to _archive
ls -la /opt/valkyrie/memory/_archive/
tail -n 200 /var/log/valkyrie_watcher.log
```

Notes
- Update API_URL inside the watcher if web path differs.
- For Nextcloud sync issues, run: docker exec -u www-data nextcloud php occ files:scan --all
- Remove or reduce display_errors and verbose logging in production.

End.  