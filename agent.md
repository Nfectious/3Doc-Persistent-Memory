# 🤖 AGENT DEPLOYMENT INSTRUCTIONS
## Valkyrie Memory System v4.2 - Integration with Existing Nextcloud

**Target Environment**: Contabo Server with Nginx + Existing Nextcloud Docker Container

---

## 📋 PREREQUISITES CHECK

Before starting, verify you have:
- [ ] Contabo server with Ubuntu/Debian
- [ ] Nginx installed and running
- [ ] Existing Nextcloud running in Docker
- [ ] Docker and docker-compose installed
- [ ] Root/sudo access
- [ ] Your Nextcloud admin credentials
- [ ] Your Nextcloud username

---

## 🎯 OBJECTIVE

Integrate Valkyrie Memory System with your existing Nextcloud installation:
1. Install Valkyrie web application
2. Mount Valkyrie memory directory into existing Nextcloud container
3. Configure automated file processing
4. Enable cross-device memory sync

**NO NEW NEXTCLOUD INSTALLATION NEEDED** - We're using your existing one!

---

## ⚙️ CONFIGURATION REQUIRED

### Gather This Information First:

1. **Your Nextcloud Container Name**:
   ```bash
   docker ps | grep nextcloud
   ```
   Note the container name (e.g., `nextcloud`, `nextcloud-app`, etc.)

2. **Your Nextcloud Username**:
   The username you log in with (e.g., `admin`, `myuser`)

3. **Your Domain for Valkyrie**:
   Example: `aimem.contaboserver.com` or use your Nextcloud domain

4. **Your PHP Version**:
   ```bash
   php -v
   ```
   Note the version (e.g., `7.4`, `8.1`, `8.2`)

5. **Your Project Names** (optional):
   Space-separated list of projects you want to track
   Default: `project1 project2 project3`

---

## 🚀 STEP-BY-STEP DEPLOYMENT

### STEP 1: Clone and Configure Repository

```bash
# Clone repository
cd /root
git clone https://github.com/Nfectious/3Doc-Persistent-Memory.git
cd 3Doc-Persistent-Memory

# Or if already cloned, pull latest
git pull origin main
```

### STEP 2: Update Configuration Files

#### File 1: `scripts/install.sh`

Edit the configuration section (lines 30-35):

```bash
nano scripts/install.sh
```

Update these values:
```bash
DOMAIN="your.domain.com"              # Your actual domain
PHP_VERSION="8.1"                      # Your PHP version from php -v
DEFAULT_PROJECTS="project1 project2"   # Your project names
```

Save and exit (Ctrl+X, Y, Enter)

#### File 2: `scripts/inbox_watcher.sh`

Edit the API URL (line 19):

```bash
nano scripts/inbox_watcher.sh
```

Update:
```bash
API_URL="http://your.domain.com/api.php?action=process_export"
# or use: http://localhost/api.php?action=process_export
```

Update project detection rules (lines 41-46):
```bash
case "$LOWER" in
    *project1*) PROJECT="project1" ;;
    *project2*) PROJECT="project2" ;;
    # Add your projects here
esac
```

Save and exit

### STEP 3: Install Valkyrie Application

```bash
# Make install script executable
chmod +x scripts/install.sh

# Run installation
sudo bash scripts/install.sh
```

**Expected Output**:
```
✓ INSTALLATION COMPLETE
📍 Web Interface: http://your.domain.com
```

**Verify Installation**:
```bash
# Check web directory
ls -la /var/www/valkyrie/

# Check memory directory
ls -la /opt/valkyrie/memory/

# Test web interface
curl http://localhost
```

### STEP 4: Find Your Nextcloud Container Details

```bash
# Get container name
docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}" | grep nextcloud

# Inspect current mounts
docker inspect YOUR_NEXTCLOUD_CONTAINER_NAME | grep -A 10 Mounts

# Find your docker-compose.yml location
find /root -name "docker-compose.yml" -type f 2>/dev/null | grep nextcloud
# or
find /home -name "docker-compose.yml" -type f 2>/dev/null | grep nextcloud
# or
find /opt -name "docker-compose.yml" -type f 2>/dev/null | grep nextcloud
```

Note the docker-compose.yml path!

### STEP 5: Add Valkyrie Mount to Your Nextcloud Container

**IMPORTANT**: We're editing YOUR existing docker-compose.yml

```bash
# Backup your current docker-compose.yml first!
cp /path/to/your/docker-compose.yml /path/to/your/docker-compose.yml.backup

# Edit your docker-compose.yml
nano /path/to/your/docker-compose.yml
```

Find the `nextcloud` service section and add the Valkyrie volume mount to the `volumes:` section:

**Before**:
```yaml
services:
  nextcloud:
    volumes:
      - nextcloud_data:/var/www/html
      # ... other volumes ...
```

**After** (add this line):
```yaml
services:
  nextcloud:
    volumes:
      - nextcloud_data:/var/www/html
      # ... other volumes ...
      - /opt/valkyrie/memory:/var/www/html/data/YOUR_USERNAME/files/Valkyrie:rw
```

**Replace `YOUR_USERNAME` with your actual Nextcloud username!**

Save and exit

### STEP 6: Restart Nextcloud Container

```bash
# Navigate to your docker-compose directory
cd /path/to/your/nextcloud/docker-compose/directory

# Stop container
docker-compose down

# Start with new mount
docker-compose up -d

# Verify it's running
docker-compose ps

# Check if mount is present
docker exec YOUR_CONTAINER_NAME ls -la /var/www/html/data/YOUR_USERNAME/files/
# Should see "Valkyrie" directory
```

**If you don't use docker-compose**, restart manually:
```bash
# Stop container
docker stop YOUR_CONTAINER_NAME

# Inspect current run command to replicate it with new mount
docker inspect YOUR_CONTAINER_NAME | grep -A 50 "Cmd"

# Remove container (data is safe in volumes)
docker rm YOUR_CONTAINER_NAME

# Recreate with Valkyrie mount added
docker run -d \
  --name=YOUR_CONTAINER_NAME \
  [... your existing parameters ...] \
  -v /opt/valkyrie/memory:/var/www/html/data/YOUR_USERNAME/files/Valkyrie:rw \
  [... rest of parameters ...]
```

### STEP 7: Fix Permissions (CRITICAL!)

```bash
# Get www-data UID from container (usually 33)
docker exec YOUR_CONTAINER_NAME id www-data

# Set ownership to www-data (UID 33)
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Add local user to www-data group for access
sudo usermod -a -G www-data $USER
sudo usermod -a -G www-data valkyrie
```

### STEP 8: Scan Files in Nextcloud

```bash
# Rescan files to detect Valkyrie folder
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan YOUR_USERNAME

# Or scan all users
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan --all
```

**Verify in Nextcloud Web Interface**:
1. Open your Nextcloud URL in browser
2. Log in
3. Go to Files
4. You should see "Valkyrie" folder with subfolders:
   - `projects/`
   - `global/`
   - `_incoming/`
   - `_archive/`

### STEP 9: Install Inbox Watcher Service (Optional but Recommended)

```bash
# Install dependencies
sudo apt update
sudo apt install -y inotify-tools jq

# Copy watcher script
sudo cp scripts/inbox_watcher.sh /opt/valkyrie/inbox_watcher.sh
sudo chmod +x /opt/valkyrie/inbox_watcher.sh
sudo chown valkyrie:valkyrie /opt/valkyrie/inbox_watcher.sh

# Create systemd service
sudo tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<'EOF'
[Unit]
Description=Valkyrie Inbox Watcher Service
After=network.target nginx.service

[Service]
Type=simple
User=valkyrie
Group=valkyrie
ExecStart=/opt/valkyrie/inbox_watcher.sh
Restart=always
RestartSec=10
StandardOutput=append:/var/log/valkyrie_watcher.log
StandardError=append:/var/log/valkyrie_watcher.log

# Allow www-data group access
SupplementaryGroups=www-data

[Install]
WantedBy=multi-user.target
EOF

# Create log file with correct permissions
sudo touch /var/log/valkyrie_watcher.log
sudo chown valkyrie:valkyrie /var/log/valkyrie_watcher.log
sudo chmod 664 /var/log/valkyrie_watcher.log

# Reload systemd and start service
sudo systemctl daemon-reload
sudo systemctl enable valkyrie-watcher
sudo systemctl start valkyrie-watcher

# Check status
sudo systemctl status valkyrie-watcher

# Follow logs
sudo tail -f /var/log/valkyrie_watcher.log
```

---

## ✅ VERIFICATION CHECKLIST

Run these commands to verify everything works:

### 1. Valkyrie Web Interface
```bash
# Test local access
curl -I http://localhost
# Should return: HTTP/1.1 200 OK

# Test API
curl http://localhost/api.php?action=list_projects
# Should return JSON with projects
```

### 2. Nextcloud Mount
```bash
# Check mount from host
ls -la /opt/valkyrie/memory/
# Should show: drwxrwxr-x 33 www-data

# Check mount from container
docker exec YOUR_CONTAINER_NAME ls -la /var/www/html/data/YOUR_USERNAME/files/Valkyrie
# Should show: projects/ global/ _incoming/ _archive/
```

### 3. Nextcloud Web Access
- [ ] Open Nextcloud in browser
- [ ] See "Valkyrie" folder in Files
- [ ] Can navigate into subfolders
- [ ] Can upload files to _incoming/

### 4. Inbox Watcher Service
```bash
# Check service is running
systemctl is-active valkyrie-watcher
# Should return: active

# Create test file
echo "Test content" | sudo tee /opt/valkyrie/memory/_incoming/test.txt

# Watch logs (should show processing)
sudo tail -20 /var/log/valkyrie_watcher.log
# Should see: "Detected: test.txt" → "Archived: test.txt"

# Verify file was moved to archive
ls -la /opt/valkyrie/memory/_archive/
# Should see: test.txt.TIMESTAMP
```

### 5. Cross-Device Sync Test
```bash
# From server: Create a file
echo "Server test" > /opt/valkyrie/memory/projects/project1/test_from_server.txt

# From Nextcloud web: Check if file appears
# Open Nextcloud → Files → Valkyrie → projects → project1
# Should see: test_from_server.txt

# From Nextcloud web: Upload a file to _incoming
# Watch server logs
sudo tail -f /var/log/valkyrie_watcher.log
# Should process automatically
```

---

## 🎯 USAGE INSTRUCTIONS

### For Your AI Agent:

Your agent can now use Valkyrie memory by:

#### Method 1: Direct File Access
```bash
# Read project memory
cat /opt/valkyrie/memory/projects/project1/PROJECT_MEMORY.md

# Append to project memory
echo -e "\n\n## New Section\nContent here" >> /opt/valkyrie/memory/projects/project1/PROJECT_MEMORY.md

# Create new project
mkdir -p /opt/valkyrie/memory/projects/new_project
echo "# PROJECT: new_project" > /opt/valkyrie/memory/projects/new_project/PROJECT_MEMORY.md
```

#### Method 2: API Access
```bash
# Get project memory via API
curl http://localhost/api.php?action=get_memory \
  -H "Content-Type: application/json" \
  -d '{"projects":["project1","project2"]}'

# Append text to project
curl http://localhost/api.php?action=append \
  -H "Content-Type: application/json" \
  -d '{"project":"project1","text":"New memory entry"}'

# List all projects
curl http://localhost/api.php?action=list_projects
```

#### Method 3: CLI Tool
```bash
# List projects
vproject list

# Create new project
vproject create my_new_project

# View project memory
vproject view project1
```

### For Manual Usage:

#### Web Interface:
1. Open: `http://your.domain.com`
2. Go to "Session Start" tab
3. Select project(s)
4. Click "Copy Selected Memory for AI"
5. Paste into AI chat

#### Nextcloud Sync:
1. Install Nextcloud mobile app
2. Navigate to Valkyrie/_incoming/
3. Upload session export text file
4. File is automatically processed in 1-2 seconds

---

## 🔧 TROUBLESHOOTING

### Issue: Valkyrie folder not appearing in Nextcloud

**Solution**:
```bash
# Force rescan
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan --all

# Check permissions
ls -la /opt/valkyrie/memory/
# Should be: drwxrwxr-x 33 www-data

# Fix if needed
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Restart Nextcloud
docker restart YOUR_CONTAINER_NAME
```

### Issue: Permission denied when accessing files

**Solution**:
```bash
# Check current ownership
ls -la /opt/valkyrie/memory/

# Fix ownership
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Add valkyrie user to www-data group
sudo usermod -a -G www-data valkyrie

# Restart services
sudo systemctl restart valkyrie-watcher
docker restart YOUR_CONTAINER_NAME
```

### Issue: Inbox watcher not processing files

**Solution**:
```bash
# Check service status
sudo systemctl status valkyrie-watcher

# Check logs
sudo tail -50 /var/log/valkyrie_watcher.log

# Restart service
sudo systemctl restart valkyrie-watcher

# Test manually
sudo -u valkyrie /opt/valkyrie/inbox_watcher.sh
# Let it run, then drop a file in _incoming/, watch for processing
# Press Ctrl+C to stop
```

### Issue: nginx fails to start

**Solution**:
```bash
# Test configuration
sudo nginx -t

# Check for port conflicts
sudo netstat -tlnp | grep :80

# View error log
sudo tail -50 /var/log/nginx/error.log

# Restart nginx
sudo systemctl restart nginx
```

### Issue: PHP version mismatch

**Solution**:
```bash
# Check installed PHP versions
dpkg -l | grep php | grep fpm

# Update install.sh with correct version
nano scripts/install.sh
# Change PHP_VERSION="8.1" to match your version

# Reinstall
sudo bash scripts/install.sh
```

---

## 📊 FILE STRUCTURE REFERENCE

```
/opt/valkyrie/
├── memory/
│   ├── projects/
│   │   ├── project1/
│   │   │   ├── PROJECT_MEMORY.md      ← Main memory
│   │   │   ├── INSIGHTS_LOG.md         ← Learnings
│   │   │   └── NEXT_ACTIONS.md         ← Action items
│   │   ├── project2/
│   │   └── project3/
│   ├── global/
│   │   ├── PROJECT_MEMORY.md          ← Cross-project context
│   │   ├── INSIGHTS_LOG.md
│   │   └── NEXT_ACTIONS.md
│   ├── _incoming/                      ← Drop files here
│   └── _archive/                       ← Processed files
└── inbox_watcher.sh                    ← Watcher service

/var/www/valkyrie/
├── index.html                          ← Web UI
└── api.php                             ← REST API

Container: /var/www/html/data/YOUR_USERNAME/files/Valkyrie/
→ Mounted from: /opt/valkyrie/memory/
```

---

## 🤖 AGENT-SPECIFIC COMMANDS

For your AI agent running on the server:

### Read All Project Context
```bash
#!/bin/bash
# Load all Valkyrie memory into agent context

for project in /opt/valkyrie/memory/projects/*; do
  echo "=== PROJECT: $(basename $project) ==="
  cat "$project/PROJECT_MEMORY.md"
  echo ""
done

cat /opt/valkyrie/memory/global/PROJECT_MEMORY.md
```

### Save Agent Output to Memory
```bash
#!/bin/bash
# Usage: save_to_memory.sh <project_name> <content>

PROJECT="$1"
CONTENT="$2"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo -e "\n\n---\n## Agent Update - $TIMESTAMP\n\n$CONTENT" >> \
  "/opt/valkyrie/memory/projects/$PROJECT/PROJECT_MEMORY.md"
```

### Monitor for New Instructions
```bash
#!/bin/bash
# Watch _incoming for new agent instructions

inotifywait -m -e create /opt/valkyrie/memory/_incoming/ | \
while read dir action file; do
  if [[ "$file" == agent_instruction_* ]]; then
    content=$(cat "$dir$file")
    echo "New instruction received: $content"
    # Process instruction here
    mv "$dir$file" /opt/valkyrie/memory/_archive/
  fi
done
```

---

## 📱 MOBILE ACCESS SETUP

### For Nextcloud Mobile App:

1. **Install App**:
   - Android: Google Play Store → "Nextcloud"
   - iOS: App Store → "Nextcloud"

2. **Connect**:
   - Server URL: Your Nextcloud URL
   - Username: Your username
   - Password: Your password

3. **Navigate to Valkyrie**:
   - Open Files
   - Go to Valkyrie folder
   - Browse projects or upload to _incoming/

4. **Auto-Upload (Optional)**:
   - Settings → Auto upload
   - Choose Valkyrie/_incoming/ as destination
   - Enable for specific folders

---

## 🔐 SECURITY CONSIDERATIONS

1. **File Permissions**:
   - Memory directory: 775 (valkyrie:www-data)
   - Individual files: 664
   - Never 777!

2. **Nginx Access**:
   - Web interface is public by default
   - Add authentication if needed:
     ```bash
     sudo htpasswd -c /etc/nginx/.htpasswd admin
     # Then add to nginx config:
     # auth_basic "Restricted";
     # auth_basic_user_file /etc/nginx/.htpasswd;
     ```

3. **API Access**:
   - Currently open
   - Consider IP whitelist or API keys for production

4. **Nextcloud Security**:
   - Use HTTPS (install Let's Encrypt)
   - Enable 2FA in Nextcloud
   - Regular updates

---

## 🎯 SUCCESS CRITERIA

You've successfully deployed when:

- [x] Valkyrie web interface accessible at your domain
- [x] API responding to requests
- [x] Valkyrie folder visible in Nextcloud Files
- [x] Can upload file to _incoming/ via Nextcloud
- [x] Inbox watcher processes files automatically
- [x] Files sync between server and Nextcloud
- [x] Agent can read/write memory files
- [x] Mobile app can access Valkyrie folder

---

## 📚 AGENT INTEGRATION EXAMPLES

### Example 1: Context Loading Script
```bash
#!/bin/bash
# load_context.sh - Load Valkyrie memory for agent

CONTEXT_FILE="/tmp/valkyrie_context.txt"

echo "# VALKYRIE MEMORY CONTEXT" > "$CONTEXT_FILE"
echo "# Loaded: $(date)" >> "$CONTEXT_FILE"
echo "" >> "$CONTEXT_FILE"

for project in /opt/valkyrie/memory/projects/*; do
  PROJECT_NAME=$(basename "$project")
  echo "## PROJECT: $PROJECT_NAME" >> "$CONTEXT_FILE"
  cat "$project/PROJECT_MEMORY.md" >> "$CONTEXT_FILE"
  echo "" >> "$CONTEXT_FILE"
done

echo "Context loaded to: $CONTEXT_FILE"
cat "$CONTEXT_FILE"
```

### Example 2: Auto-Save Agent Session
```bash
#!/bin/bash
# save_session.sh - Save agent session to Valkyrie

PROJECT="${1:-global}"
SESSION_LOG="$2"

if [ -z "$SESSION_LOG" ]; then
  echo "Usage: save_session.sh <project_name> <session_log_file>"
  exit 1
fi

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
DEST="/opt/valkyrie/memory/projects/$PROJECT/PROJECT_MEMORY.md"

echo -e "\n\n---\n## Agent Session - $TIMESTAMP\n" >> "$DEST"
cat "$SESSION_LOG" >> "$DEST"

echo "Session saved to: $DEST"
```

### Example 3: Watch for Agent Commands
```bash
#!/bin/bash
# agent_command_watcher.sh - Monitor for commands via Nextcloud

COMMAND_DIR="/opt/valkyrie/memory/_incoming"

echo "Watching for agent commands in: $COMMAND_DIR"

inotifywait -m -e create -e moved_to "$COMMAND_DIR" | \
while read dir action file; do
  if [[ "$file" == agent_cmd_* ]]; then
    COMMAND=$(cat "$dir$file")
    echo "[$(date)] Received command: $COMMAND"

    # Execute command (add your agent logic here)
    eval "$COMMAND"

    # Archive command file
    mv "$dir$file" /opt/valkyrie/memory/_archive/
  fi
done
```

---

## ⏱️ DEPLOYMENT TIME

- **Valkyrie Installation**: ~5 minutes
- **Nextcloud Integration**: ~5 minutes
- **Inbox Watcher Setup**: ~5 minutes
- **Testing & Verification**: ~5 minutes
- **Total**: ~20 minutes

---

## 📞 POST-DEPLOYMENT

After successful deployment:

1. **Test from Web**: Open `http://your.domain.com` and copy memory
2. **Test from Nextcloud**: Upload file to _incoming/, verify processing
3. **Test from Mobile**: Access via Nextcloud app
4. **Test Agent Access**: Run agent scripts to read/write memory
5. **Setup Backups**: Backup `/opt/valkyrie/memory/` regularly

---

## 🆘 NEED HELP?

**Check Logs**:
```bash
# Nginx
sudo tail -f /var/log/nginx/error.log

# Valkyrie Watcher
sudo tail -f /var/log/valkyrie_watcher.log

# Nextcloud (from container)
docker logs -f YOUR_CONTAINER_NAME

# System
sudo journalctl -xe
```

**Quick Health Check**:
```bash
# All-in-one health check
echo "=== Valkyrie Health Check ==="
echo "Web: $(curl -s -o /dev/null -w '%{http_code}' http://localhost)"
echo "API: $(curl -s http://localhost/api.php?action=stats | jq -r .success)"
echo "Memory: $(ls -ld /opt/valkyrie/memory/ | awk '{print $1, $3, $4}')"
echo "Watcher: $(systemctl is-active valkyrie-watcher)"
echo "Nextcloud: $(docker inspect -f '{{.State.Status}}' YOUR_CONTAINER_NAME)"
```

---

**Version**: 4.2.0
**Last Updated**: 2025-11-10
**Status**: ✅ Ready for Agent Deployment
**Environment**: Contabo + Nginx + Existing Nextcloud Docker
