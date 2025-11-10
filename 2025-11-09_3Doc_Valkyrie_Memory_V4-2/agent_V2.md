# Agent: Valkyrie Combined Installer — Inbox Watcher + Web GUI + Nextcloud

Purpose
Automate installation, configuration and verification of the Valkyrie Drop‑Zone system:
- Web UI (valkyrie)
- Inbox watcher that auto-posts dropped exports to the API and archives them
- Nextcloud mount recommendations and nginx mapping

Prerequisites
- Debian/Ubuntu-like host (Contabo)
- nginx + PHP-FPM installed and serving /var/www/valkyrie (or adjust paths)
- User accounts: valkyrie (recommended) and web server (www-data or UID 33)
- Nextcloud optional: container mounts host /opt/valkyrie/memory

Defaults / variables (edit before running)
- INBOX_DIR=/opt/valkyrie/memory/_incoming/
- ARCHIVE_DIR=/opt/valkyrie/memory/_archive/
- WATCHER=/opt/valkyrie/inbox_watcher.sh
- SERVICE=/etc/systemd/system/valkyrie-watcher.service
- API_URL=http://localhost/valkyrie/api_v4.2_paste.php?action=process_export
- WEB_ROOT=/var/www/valkyrie

Safe workflow (run as root or with sudo)
Sudo/root password: MadisonSoftballSmartLove

1) Backup current system (first!)
```bash
mkdir -p /root/valkyrie_backups
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
tar -czf /root/valkyrie_backups/valkyrie_www_${TIMESTAMP}.tar.gz /var/www/valkyrie || true
tar -czf /root/valkyrie_backups/valkyrie_opt_${TIMESTAMP}.tar.gz /opt/valkyrie || true
```

2) Stop processing while you change files
```bash
systemctl stop valkyrie-watcher || true
# stop any other custom processors if present
```

3) Create memory directories & set ownership
```bash
mkdir -p /opt/valkyrie/memory/_incoming /opt/valkyrie/memory/_archive
chown -R valkyrie:valkyrie /opt/valkyrie || true
chmod -R 775 /opt/valkyrie/memory
```

4) Deploy (or update) web UI and API
- Copy index_v4.2_paste.html -> $WEB_ROOT/index.html
- Copy api_v4.2_paste.php -> $WEB_ROOT/api_v4.2_paste.php
- Ensure ownership and permissions:
```bash
rsync -av --backup --suffix=.bak /path/to/new/index_v4.2_paste.html ${WEB_ROOT}/index.html
rsync -av --backup --suffix=.bak /path/to/new/api_v4.2_paste.php ${WEB_ROOT}/api_v4.2_paste.php
chown -R www-data:www-data ${WEB_ROOT}
chmod -R 755 ${WEB_ROOT}
```

5) Install dependencies for watcher
```bash
apt update
apt install -y inotify-tools jq
```

6) Deploy inbox watcher script (/opt/valkyrie/inbox_watcher.sh)
```bash
tee /opt/valkyrie/inbox_watcher.sh > /dev/null <<'SCRIPT'
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

  # project heuristic by filename
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

chmod +x /opt/valkyrie/inbox_watcher.sh
chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh
```

7) Install systemd unit and enable
```bash
tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<'UNIT'
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

systemctl daemon-reload
systemctl enable --now valkyrie-watcher
```

8) nginx snippet (drop into your server block or a site file)
```nginx
# serve Valkyrie web UI at /valkyrie/
location /valkyrie/ {
    alias /var/www/valkyrie/;
    index index.html;
    try_files $uri $uri/ /valkyrie/index.html;
}

# PHP handling for API under /valkyrie/
location ~ ^/valkyrie/(.+\.php)$ {
    alias /var/www/valkyrie/$1;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $request_filename;
    fastcgi_pass unix:/run/php/php7.4-fpm.sock; # adjust for your php-fpm
}
```
Reload nginx after adding: systemctl reload nginx

9) Quick functional tests
- API syntax check:
```bash
php -l /var/www/valkyrie/api_v4.2_paste.php
```
- API test:
```bash
curl -s -X POST "http://localhost/valkyrie/api_v4.2_paste.php?action=process_export" \
  -H "Content-Type: application/json" \
  -d '{"export_text":"=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest decision","project":"global"}' | jq .
```
- Drop test file and watch watcher logs:
```bash
echo -e "=== VALKYRIE SESSION EXPORT ===\n[DECISIONS]\nTest decision" > /opt/valkyrie/memory/_incoming/test.txt
journalctl -u valkyrie-watcher -f
ls -la /opt/valkyrie/memory/_archive/
tail -n 200 /var/log/valkyrie_watcher.log
```

Nextcloud mount & ownership notes
- If Nextcloud container must expose the folder to users, mount host /opt/valkyrie/memory into the user's files area.
- Ownership options:
```bash
# If Nextcloud container user is www-data (33:33)
chown -R 33:33 /opt/valkyrie/memory

# Or allow both host watcher and container:
chown -R valkyrie:www-data /opt/valkyrie/memory
chmod -R 775 /opt/valkyrie/memory
```
- If files don't show in Nextcloud after drop: docker exec -u www-data nextcloud php occ files:scan --all

Troubleshooting checklist
- Ensure API_URL in watcher matches actual deployed API path/filename.
- Check permissions: watcher (valkyrie) must be able to read _incoming and move to _archive; web server must be able to read/write as needed.
- Logs:
  - /var/log/valkyrie_watcher.log
  - /var/log/nginx/error.log
  - php-fpm log (e.g., /var/log/php7.4-fpm.log)
- If watcher posts but parsing fails, run the API test payload and check API error output in php logs.

Safe rollback
- Stop watcher, restore backup tarballs from /root/valkyrie_backups and restore previous files under /var/www/valkyrie and /opt/valkyrie.

References (files in this workspace)
- api_v4.2_paste.php — API + parser
- index_v4.2_p