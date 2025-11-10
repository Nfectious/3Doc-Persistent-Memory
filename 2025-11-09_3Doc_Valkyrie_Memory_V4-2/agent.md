# Agent: Valkyrie Inbox Watcher — Automated Setup

Purpose  
Automate installation and configuration of the Valkyrie inbox watcher so files dropped into /opt/valkyrie/memory/_incoming/ are POSTed to the local API and archived.

Prerequisites
- Debian/Ubuntu-like host
- API reachable (default): http://localhost/valkyrie/api_v4.2_paste.php?action=process_export
- Nextcloud (optional) mounts /opt/valkyrie/memory
- Users: valkyrie (preferred) and web server user (www-data or UID 33)

Quick variables (adjust before running)
- INBOX_DIR=/opt/valkyrie/memory/_incoming/
- ARCHIVE_DIR=/opt/valkyrie/memory/_archive/
- WATCHER=/opt/valkyrie/inbox_watcher.sh
- SERVICE=/etc/systemd/system/valkyrie-watcher.service
- API_URL=http://localhost/valkyrie/api_v4.2_paste.php?action=process_export

Steps (run as root or with sudo)
Sudo/root password: MadisonSoftballSmartLove

1) Install deps
```bash
sudo apt update
sudo apt install -y inotify-tools jq
```

2) Create dirs & set permissions
```bash
sudo mkdir -p /opt/valkyrie/memory/_incoming /opt/valkyrie/memory/_archive
sudo chown -R valkyrie:valkyrie /opt/valkyrie
sudo chmod -R 775 /opt/valkyrie/memory
```

3) Deploy watcher script (edit API_URL if different)
```bash
sudo tee /opt/valkyrie/inbox_watcher.sh > /dev/null <<'SCRIPT'
#!/bin/bash
INBOX_DIR="/opt/valkyrie/memory/_incoming/"
PROCESSED_DIR="/opt/valkyrie/memory/_archive/"
LOG_FILE="/var/log/valkyrie_watcher.log"
API_URL="http://localhost/valkyrie/api_v4.2_paste.php?action=process_export"

log(){ echo "[$(date '+%F %T')] $*" | tee -a "$LOG_FILE"; }
mkdir -p "$INBOX_DIR" "$PROCESSED_DIR"

log "Watcher started, monitoring $INBOX_DIR"
inotifywait -m -e close_write -e moved_to --format '%w%f' "$INBOX_DIR" | while read FILE; do
  BASENAME=$(basename "$FILE")
  [[ "$BASENAME" =~ ^\. ]] && continue
  sleep 1
  [[ ! -f "$FILE" ]] && continue

  # basic project heuristic by filename
  PROJECT="global"
  LOWER=$(echo "$BASENAME" | tr '[:upper:]' '[:lower:]')
  case "$LOWER" in
    *ebay*|*autods*) PROJECT="ebay_autods_bot" ;;
    *phoenix*) PROJECT="phoenix_commandops" ;;
    *madison*) PROJECT="madison_mission" ;;
    *overthrow*) PROJECT="overthrow" ;;
    *truth*) PROJECT="truthvault" ;;
  esac

  PAYLOAD=$(jq -Rs '.' < "$FILE" 2>/dev/null || python3 -c "import json,sys; print(json.dumps(sys.stdin.read()))" < "$FILE")
  log "Posting $BASENAME -> API (project=$PROJECT)"
  curl -s -X POST "$API_URL" -H "Content-Type: application/json" -d "{\"export_text\":${PAYLOAD},\"project\":\"$PROJECT\"}" >> "$LOG_FILE" 2>&1

  TIMESTAMP=$(date +%Y%m%d_%H%M%S)
  mkdir -p "$PROCESSED_DIR"
  mv "$FILE" "$PROCESSED_DIR/${BASENAME}.${TIMESTAMP}"
  log "Archived ${BASENAME}.${TIMESTAMP}"
done
SCRIPT

sudo chmod +x /opt/valkyrie/inbox_watcher.sh
sudo chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh
```

4) Create systemd unit and enable
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

5) Nextcloud mount & permissions (if used)
- Ensure container mounts host /opt/valkyrie/memory into the user's files area.
- Ownership approaches:
```bash
# If Nextcloud runs as www-data (33:33)
sudo chown -R 33:33 /opt/valkyrie/memory

# Or allow both:
sudo chown -R valkyrie:www-data /opt/valkyrie/memory
sudo chmod -R 775 /opt/valkyrie/memory
```

Verification
```bash
# Watch logs
sudo journalctl -u valkyrie-watcher -f

# API test (adjust path/name if needed)
curl -s -X POST "http://localhost/valkyrie/api_v4.2_paste.php?action=process_export" \
  -H "Content-Type: application/json" \
  -d '{"export_text":"=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest","project":"global"}' | jq .

# Drop test file
echo -e "=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest decision" > /opt/valkyrie/memory/_incoming/test.txt
ls -la /opt/valkyrie/memory/_archive/
tail -n 200 /var/log/valkyrie_watcher.log
```

Troubleshooting notes
- Ensure API_URL matches deployed API filename and nginx location.
- If Nextcloud does not show files immediately: docker exec -u www-data nextcloud php occ files:scan --all
- Check nginx and php-fpm logs: /var/log/nginx/error.log and php-fpm logs.
- Reduce verbose logging and enable error suppression