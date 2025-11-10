# NEXTCLOUD DOCKER INTEGRATION GUIDE

Complete guide to integrate Valkyrie Memory System with Nextcloud running in Docker.

---

## 🎯 GOAL

Automatically sync Valkyrie memory files via Nextcloud and process files dropped in inbox folder.

---

## 📋 ARCHITECTURE

```
Nextcloud Docker Container
  ├── /var/www/html/data/[user]/files/Valkyrie/  ← Nextcloud folder
  └── Mounted to → /opt/valkyrie/memory/         ← Host directory

File Watcher Service (on host)
  └── Monitors: /opt/valkyrie/memory/_incoming/
  └── Processes: New files automatically
```

---

## 🔧 STEP 1: SETUP NEXTCLOUD DOCKER MOUNT

### Find Your Nextcloud Setup

```bash
# Check running containers
docker ps | grep nextcloud

# Inspect Nextcloud container
docker inspect nextcloud | grep -A 10 Mounts
```

### Option A: Docker Compose (Recommended)

Edit your `docker-compose.yml`:

```yaml
version: '3'

services:
  nextcloud:
    image: nextcloud:latest
    container_name: nextcloud
    restart: always
    ports:
      - "8080:80"
    volumes:
      - nextcloud_data:/var/www/html
      - /opt/valkyrie/memory:/var/www/html/data/[YOUR_USERNAME]/files/Valkyrie:rw
    environment:
      - MYSQL_HOST=db
      - MYSQL_DATABASE=nextcloud
      - MYSQL_USER=nextcloud
      - MYSQL_PASSWORD=your_password

  db:
    image: mariadb
    container_name: nextcloud_db
    restart: always
    volumes:
      - db_data:/var/lib/mysql
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=nextcloud
      - MYSQL_USER=nextcloud
      - MYSQL_PASSWORD=your_password

volumes:
  nextcloud_data:
  db_data:
```

**Replace `[YOUR_USERNAME]` with your actual Nextcloud username.**

### Apply Changes

```bash
# Stop container
docker-compose down

# Start with new config
docker-compose up -d

# Verify mount
docker exec nextcloud ls -la /var/www/html/data/[YOUR_USERNAME]/files/Valkyrie
```

### Option B: Existing Container

```bash
# Stop container
docker stop nextcloud

# Remove container (keeps data)
docker rm nextcloud

# Recreate with mount
docker run -d \
  --name=nextcloud \
  -p 8080:80 \
  -v nextcloud_data:/var/www/html \
  -v /opt/valkyrie/memory:/var/www/html/data/[YOUR_USERNAME]/files/Valkyrie:rw \
  --restart=always \
  nextcloud:latest
```

---

## 🔧 STEP 2: FIX PERMISSIONS

```bash
# Find Nextcloud user/group ID
docker exec nextcloud id www-data

# Typical output: uid=33(www-data) gid=33(www-data)

# Set permissions on host
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Add your user to www-data group
sudo usermod -a -G www-data $USER

# Or if using valkyrie user
sudo usermod -a -G www-data valkyrie
```

---

## 🔧 STEP 3: RESCAN NEXTCLOUD FILES

```bash
# Scan files from within container
docker exec -u www-data nextcloud php occ files:scan [YOUR_USERNAME]

# Or scan specific folder
docker exec -u www-data nextcloud php occ files:scan --path="[YOUR_USERNAME]/files/Valkyrie"
```

You should now see the Valkyrie folder in Nextcloud web interface!

---

## 🤖 STEP 4: INSTALL FILE WATCHER

Create automated inbox processing service.

### Create Watcher Script

```bash
sudo nano /opt/valkyrie/inbox_watcher.sh
```

```bash
#!/bin/bash
#
# Valkyrie Inbox Watcher
# Monitors _incoming folder and processes new files
#

INBOX_DIR="/opt/valkyrie/memory/_incoming"
PROCESSED_DIR="/opt/valkyrie/memory/_archive"
LOG_FILE="/var/log/valkyrie_watcher.log"

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

process_file() {
    local file="$1"
    local filename=$(basename "$file")
    
    log_message "Processing: $filename"
    
    # Detect project from filename or content
    local project="global"
    
    if [[ "$filename" == *"ebay"* ]]; then
        project="ebay_autods_bot"
    elif [[ "$filename" == *"phoenix"* ]]; then
        project="phoenix_commandops"
    elif [[ "$filename" == *"madison"* ]]; then
        project="madison_mission"
    elif [[ "$filename" == *"overthrow"* ]]; then
        project="overthrow"
    elif [[ "$filename" == *"truthvault"* ]] || [[ "$filename" == *"truth"* ]]; then
        project="truthvault"
    fi
    
    # Read file content
    content=$(cat "$file")
    
    # Check if it's a Valkyrie export
    if echo "$content" | grep -q "=== VALKYRIE SESSION EXPORT ==="; then
        log_message "Detected Valkyrie export format"
        
        # Process via API
        curl -s -X POST http://localhost/valkyrie/api.php?action=process_export \
            -H "Content-Type: application/json" \
            -d "{\"export_text\":$(echo "$content" | jq -Rs .),\"project\":\"$project\"}" \
            >> "$LOG_FILE" 2>&1
        
        log_message "Export processed to project: $project"
    else
        # Just append as raw text
        timestamp=$(date '+%Y-%m-%d %H:%M:%S')
        echo -e "\n\n---\n[Imported: $timestamp]\n\n$content\n" >> "/opt/valkyrie/memory/projects/$project/PROJECT_MEMORY.md"
        
        log_message "Content appended to project: $project"
    fi
    
    # Move to archive
    mkdir -p "$PROCESSED_DIR"
    mv "$file" "$PROCESSED_DIR/$filename.$(date +%Y%m%d_%H%M%S)"
    
    log_message "File archived: $filename"
}

# Main watch loop
log_message "Valkyrie Inbox Watcher started"

inotifywait -m -e close_write -e moved_to "$INBOX_DIR" |
while read -r directory events filename; do
    # Skip hidden files and temporary files
    if [[ "$filename" =~ ^\. ]] || [[ "$filename" =~ ~$ ]]; then
        continue
    fi
    
    filepath="$directory$filename"
    
    # Wait a moment for file to be fully written
    sleep 1
    
    # Process file
    if [ -f "$filepath" ]; then
        process_file "$filepath"
    fi
done
```

```bash
# Make executable
sudo chmod +x /opt/valkyrie/inbox_watcher.sh
```

### Install inotify-tools

```bash
# Ubuntu/Debian
sudo apt install inotify-tools jq -y
```

---

## 🔧 STEP 5: CREATE SYSTEMD SERVICE

```bash
sudo nano /etc/systemd/system/valkyrie-watcher.service
```

```ini
[Unit]
Description=Valkyrie Inbox Watcher
After=network.target

[Service]
Type=simple
User=valkyrie
Group=valkyrie
ExecStart=/opt/valkyrie/inbox_watcher.sh
Restart=always
RestartSec=10
StandardOutput=append:/var/log/valkyrie_watcher.log
StandardError=append:/var/log/valkyrie_watcher.log

[Install]
WantedBy=multi-user.target
```

### Start Service

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service
sudo systemctl enable valkyrie-watcher

# Start service
sudo systemctl start valkyrie-watcher

# Check status
sudo systemctl status valkyrie-watcher

# View logs
sudo tail -f /var/log/valkyrie_watcher.log
```

---

## 🧪 TEST THE SETUP

### Test 1: Drop File in Nextcloud

1. Open Nextcloud web interface
2. Navigate to Valkyrie folder
3. Navigate to _incoming subfolder
4. Upload a text file or session export
5. Watch the logs:
   ```bash
   sudo tail -f /var/log/valkyrie_watcher.log
   ```

### Test 2: Drop File Directly

```bash
# Create test export
cat > /opt/valkyrie/memory/_incoming/test_export.txt << 'EOF'
=== VALKYRIE SESSION EXPORT ===
DATE: 2025-11-09
TIME: 23:30
SESSION: Testing Nextcloud integration

[DECISIONS]
Setup Nextcloud Docker mount
Created inbox watcher service
Automated file processing

[STATUS]
Phase: Testing
Blockers: None
Progress: All systems operational

[INSIGHTS]
Nextcloud sync enables cross-device access
Automated processing reduces manual work
File watcher provides instant feedback

[ACTIONS]
Test file upload from mobile device
Verify cross-device sync works
Monitor watcher logs for errors

=== END EXPORT ===
EOF

# Watch it get processed
sudo tail -f /var/log/valkyrie_watcher.log
```

### Test 3: Check Results

```bash
# Check if file was processed
ls -la /opt/valkyrie/memory/_archive/

# Check if content was added to project
tail -50 /opt/valkyrie/memory/global/PROJECT_MEMORY.md
```

---

## 📱 MOBILE WORKFLOW

Now you can use Nextcloud from your phone!

### Setup Nextcloud Mobile App

1. Install Nextcloud app (Android/iOS)
2. Login to your server
3. Navigate to Valkyrie/_incoming folder
4. Enable auto-upload (optional)

### Upload Session Export from Phone

1. Copy session export text
2. Create note/text file
3. Upload to Valkyrie/_incoming
4. Wait ~1 second
5. File is automatically processed!
6. Check project memory in web interface

---

## 🔄 NEXTCLOUD AUTO-SYNC

### Desktop Client

```bash
# Install Nextcloud desktop client
sudo apt install nextcloud-desktop

# Or download from: https://nextcloud.com/install/

# Configure:
1. Add account
2. Select folders to sync
3. Choose: Valkyrie folder
4. Sync to: /opt/valkyrie/memory/
```

### Selective Sync

You can choose to sync only certain folders:
- ✅ projects/ (all your projects)
- ✅ global/ (cross-project)
- ⚠️ _incoming/ (only if you want to see processed files)
- ❌ _archive/ (skip to save space)

---

## 🐛 TROUBLESHOOTING

### Files Not Appearing in Nextcloud

```bash
# Force rescan
docker exec -u www-data nextcloud php occ files:scan --all

# Check permissions
ls -la /opt/valkyrie/memory/

# Should show: drwxrwxr-x 33 www-data
```

### Watcher Not Processing Files

```bash
# Check service status
sudo systemctl status valkyrie-watcher

# Restart service
sudo systemctl restart valkyrie-watcher

# Check logs
sudo tail -100 /var/log/valkyrie_watcher.log

# Test manually
sudo -u valkyrie /opt/valkyrie/inbox_watcher.sh
```

### Permission Denied Errors

```bash
# Fix ownership
sudo chown -R 33:33 /opt/valkyrie/memory/

# Fix permissions
sudo chmod -R 775 /opt/valkyrie/memory/

# Restart container
docker restart nextcloud
```

### Files Stuck in Inbox

```bash
# Check file format
cat /opt/valkyrie/memory/_incoming/stuck_file.txt

# Process manually
sudo /opt/valkyrie/inbox_watcher.sh
```

---

## 📊 MONITORING

### Check Watcher Status

```bash
# Service status
systemctl status valkyrie-watcher

# Recent logs
sudo journalctl -u valkyrie-watcher -n 50

# Follow logs
sudo journalctl -u valkyrie-watcher -f
```

### Check Sync Status

```bash
# Files in inbox
ls -la /opt/valkyrie/memory/_incoming/

# Files in archive
ls -la /opt/valkyrie/memory/_archive/ | tail -20

# Recent activity
find /opt/valkyrie/memory/ -type f -mmin -60 -ls
```

---

## ⚙️ CONFIGURATION OPTIONS

### Adjust Watcher Delay

Edit `/opt/valkyrie/inbox_watcher.sh`:

```bash
# Change this line:
sleep 1

# To (for slower networks):
sleep 3
```

### Change Archive Location

```bash
# Edit watcher script
PROCESSED_DIR="/opt/valkyrie/memory/_archive"

# Change to:
PROCESSED_DIR="/opt/valkyrie/memory/_archive/$(date +%Y%m)"
```

### Add Email Notifications

```bash
# Install mail utils
sudo apt install mailutils

# Add to watcher script:
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
    
    # Email on errors
    if [[ "$1" == *"ERROR"* ]]; then
        echo "$1" | mail -s "Valkyrie Watcher Error" your@email.com
    fi
}
```

---

## 🎯 COMPLETE WORKFLOW

### From Computer

1. Work with AI session
2. Generate session export
3. Save to `/opt/valkyrie/memory/_incoming/`
4. Automatically processed in ~1 second
5. Memory updated
6. Nextcloud syncs to all devices

### From Phone

1. Work with AI session
2. Copy session export
3. Create file in Nextcloud app
4. Upload to Valkyrie/_incoming
5. Automatically processed
6. Check results in web interface

### From Any Device

1. Use Nextcloud web interface
2. Navigate to Valkyrie/_incoming
3. Upload/paste session export
4. Automatic processing
5. Access updated memory anywhere

---

## ✅ VERIFICATION CHECKLIST

```
Setup:
☐ Nextcloud Docker has mount to /opt/valkyrie/memory
☐ Permissions set correctly (33:33 or www-data)
☐ Files visible in Nextcloud web interface
☐ inotify-tools installed
☐ Watcher script created and executable
☐ Systemd service created and enabled

Testing:
☐ Watcher service running
☐ Test file processed correctly
☐ File moved to archive
☐ Content added to project memory
☐ Logs show successful processing

Monitoring:
☐ Watcher logs accessible
☐ No errors in systemd journal
☐ Files syncing via Nextcloud
☐ Mobile app connected
☐ Cross-device sync working
```

---

## 📚 SUMMARY

**What you now have:**

✅ Nextcloud Docker integrated with Valkyrie  
✅ Automatic file processing from inbox  
✅ Cross-device sync via Nextcloud  
✅ Mobile upload support  
✅ Automated project routing  
✅ Archive of processed files  
✅ Comprehensive logging  

**Workflow:**

```
Drop file → _incoming folder
           ↓
      Watcher detects
           ↓
     Auto-processes
           ↓
    Updates memory
           ↓
    Archives file
           ↓
  Nextcloud syncs
```

**Access from anywhere. Process automatically. Never lose context.**

---

Need help? Check logs: `sudo journalctl -u valkyrie-watcher -f`
