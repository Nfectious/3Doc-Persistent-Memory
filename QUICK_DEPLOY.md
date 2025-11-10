# ⚡ QUICK DEPLOYMENT GUIDE - Valkyrie Memory v4.2

**For Contabo Server with Nginx + Nextcloud Docker**

---

## 🎯 WHAT YOU'RE DEPLOYING

- **Valkyrie Memory System v4.2** - AI memory management web app
- **Nginx** - Web server for Valkyrie
- **Nextcloud** (optional) - Cloud storage with auto-sync
- **Inbox Watcher** (optional) - Automated file processing

---

## ⏱️ DEPLOYMENT TIME

- **Valkyrie Only**: ~10 minutes
- **With Nextcloud**: ~20 minutes
- **Full Setup (with watcher)**: ~30 minutes

---

## 📋 BEFORE YOU START

### Required Information:
1. **Your Domain**: ___________________________
2. **Your Nextcloud Username**: ___________________________
3. **MySQL Password**: ___________________________ (create a strong one)
4. **Project Names**: ___________________________ (space-separated)

### Server Requirements:
- Ubuntu 20.04+ or Debian 10+
- Sudo access
- Internet connection
- Docker installed (for Nextcloud)

---

## 🚀 STEP-BY-STEP DEPLOYMENT

### STEP 1: Update Configuration Files ⚙️

```bash
cd /home/user/3Doc-Persistent-Memory
```

#### Option A: Manual Update (Recommended)
Open and edit these files:

1. **`scripts/install.sh`** - Lines 30-35:
   - Change `DOMAIN="aimem.yourdomain.com"` → Your domain
   - Change `PHP_VERSION="7.4"` → Your PHP version
   - Change `DEFAULT_PROJECTS="project1 project2 project3"` → Your projects

2. **`docker-compose.yml`** - Search for "TODO" and update:
   - `YOUR_NEXTCLOUD_USERNAME` (appears 1 time)
   - `YOUR_MYSQL_PASSWORD` (appears 2 times - use same password!)
   - `YOUR_MYSQL_ROOT_PASSWORD` (appears 1 time)
   - `YOUR_DOMAIN` (appears 1 time)
   - `admin123` → Your Nextcloud admin password

3. **`scripts/inbox_watcher.sh`** - Line 19:
   - Update `API_URL` if needed (default: localhost is fine)
   - Lines 54-59: Update project detection rules

#### Option B: Quick Replace (Advanced Users)
```bash
# Replace YOUR_DOMAIN with your actual domain
export MY_DOMAIN="aimem.yourdomain.com"
export MY_PHP_VERSION="7.4"
export MY_PROJECTS="project1 project2 project3"
export MY_NC_USER="myusername"
export MY_MYSQL_PASS="strongpassword123"
export MY_MYSQL_ROOT="rootpassword123"

sed -i "s/aimem.yourdomain.com/$MY_DOMAIN/g" scripts/install.sh
sed -i "s/PHP_VERSION=\"7.4\"/PHP_VERSION=\"$MY_PHP_VERSION\"/g" scripts/install.sh
sed -i "s/DEFAULT_PROJECTS=\"project1 project2 project3\"/DEFAULT_PROJECTS=\"$MY_PROJECTS\"/g" scripts/install.sh
sed -i "s/YOUR_NEXTCLOUD_USERNAME/$MY_NC_USER/g" docker-compose.yml
sed -i "s/YOUR_MYSQL_PASSWORD/$MY_MYSQL_PASS/g" docker-compose.yml
sed -i "s/YOUR_MYSQL_ROOT_PASSWORD/$MY_MYSQL_ROOT/g" docker-compose.yml
sed -i "s/YOUR_DOMAIN/$MY_DOMAIN/g" docker-compose.yml
```

---

### STEP 2: Install Valkyrie 🩸

```bash
cd /home/user/3Doc-Persistent-Memory
sudo bash scripts/install.sh
```

**What it does**:
- ✅ Installs nginx and PHP
- ✅ Creates `/opt/valkyrie/memory/` directory structure
- ✅ Deploys web interface to `/var/www/valkyrie/`
- ✅ Configures nginx virtual host
- ✅ Creates system user `valkyrie`
- ✅ Sets up CLI tool `vproject`

**Expected output**:
```
✓ INSTALLATION COMPLETE
📍 Web Interface: http://yourdomain.com
```

**Test it**:
```bash
curl http://localhost
# Should return HTML content
```

---

### STEP 3: Deploy Nextcloud (Optional) 🐳

**Skip this if you don't need Nextcloud integration**

```bash
cd /home/user/3Doc-Persistent-Memory

# Start Nextcloud containers
docker-compose up -d

# Wait 1-2 minutes for initialization
sleep 120

# Check status
docker-compose ps
```

**Expected output**:
```
nextcloud      running   0.0.0.0:8080->80/tcp
nextcloud_db   running
nextcloud_redis running
```

**Test it**:
```bash
curl http://localhost:8080
# Should return Nextcloud page
```

---

### STEP 4: Connect Nextcloud to Valkyrie 🔗

**Only if you deployed Nextcloud**

```bash
# Fix permissions (CRITICAL!)
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Scan files to detect Valkyrie folder
# Replace YOUR_USERNAME with your Nextcloud username
docker exec -u www-data nextcloud php occ files:scan YOUR_USERNAME

# Verify mount
docker exec nextcloud ls -la /var/www/html/data/YOUR_USERNAME/files/
# Should see "Valkyrie" folder
```

**Access Nextcloud**:
- URL: `http://yourdomain.com:8080`
- Username: `admin`
- Password: (what you set in docker-compose.yml)

**Verify**: You should see "Valkyrie" folder in Files!

---

### STEP 5: Install Inbox Watcher (Optional) 👀

**Skip this if you don't need automated file processing**

```bash
# Install dependencies
sudo apt install -y inotify-tools jq

# Copy watcher script
sudo cp scripts/inbox_watcher.sh /opt/valkyrie/inbox_watcher.sh
sudo chmod +x /opt/valkyrie/inbox_watcher.sh

# Create systemd service
sudo tee /etc/systemd/system/valkyrie-watcher.service > /dev/null <<EOF
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
EOF

# Start and enable service
sudo systemctl daemon-reload
sudo systemctl enable valkyrie-watcher
sudo systemctl start valkyrie-watcher

# Check status
sudo systemctl status valkyrie-watcher
```

**Test it**:
```bash
# Create test file
echo "Test content" > /opt/valkyrie/memory/_incoming/test.txt

# Watch logs
sudo tail -f /var/log/valkyrie_watcher.log
# Should see: "Detected: test.txt" → "Archived: test.txt"
```

---

## ✅ VERIFICATION CHECKLIST

### Valkyrie Installation
```bash
# 1. Web interface accessible
curl -I http://localhost | grep "200 OK"

# 2. API responding
curl http://localhost/api.php?action=list_projects

# 3. Memory directory exists
ls -la /opt/valkyrie/memory/projects/

# 4. CLI tool works
vproject list
```

### Nextcloud (if installed)
```bash
# 1. Containers running
docker-compose ps | grep "Up"

# 2. Valkyrie folder mounted
docker exec nextcloud ls /var/www/html/data/YOUR_USERNAME/files/Valkyrie

# 3. Web interface accessible
curl -I http://localhost:8080 | grep "200 OK"

# 4. Files visible in Nextcloud UI
# → Open http://yourdomain.com:8080 and check Files
```

### Inbox Watcher (if installed)
```bash
# 1. Service running
systemctl is-active valkyrie-watcher

# 2. Log file exists
ls -lh /var/log/valkyrie_watcher.log

# 3. Monitoring inbox
ps aux | grep inbox_watcher
```

---

## 🎉 SUCCESS! WHAT NOW?

### Using Valkyrie:

1. **Access Web Interface**:
   ```
   http://yourdomain.com
   ```

2. **Select a Project**:
   - Go to "Session Start" tab
   - Check one or more projects
   - Click "Copy Selected Memory for AI"

3. **Paste into AI**:
   - Paste the copied text into your AI chat
   - AI now has full context!

### Using Nextcloud Sync:

1. **Upload from Mobile**:
   - Install Nextcloud app
   - Navigate to Valkyrie/_incoming/
   - Upload session export file
   - Automatically processed in 1-2 seconds!

2. **Desktop Sync**:
   - Install Nextcloud desktop client
   - Sync Valkyrie folder
   - Direct access to memory files

### CLI Commands:

```bash
# List all projects
vproject list

# Create new project
vproject create my_new_project

# View project memory
vproject view my_new_project
```

---

## 🔧 TROUBLESHOOTING

### Problem: nginx won't start
```bash
# Check configuration
sudo nginx -t

# View error log
sudo tail -50 /var/log/nginx/error.log

# Common fix: Port already in use
sudo netstat -tlnp | grep :80
# Kill conflicting process or change port
```

### Problem: PHP not found
```bash
# Check PHP version
php -v

# Install correct version
sudo apt install php7.4-fpm php7.4-cli

# Verify FPM running
systemctl status php7.4-fpm
```

### Problem: Nextcloud container fails
```bash
# Check logs
docker-compose logs nextcloud

# Common issues:
# - Port 8080 in use: Change in docker-compose.yml
# - Database password mismatch: Verify passwords match
# - Volume permission: Check /opt/valkyrie/memory/ ownership
```

### Problem: Files not in Nextcloud
```bash
# Rescan files
docker exec -u www-data nextcloud php occ files:scan --all

# Check permissions
ls -la /opt/valkyrie/memory/
# Should show: drwxrwxr-x valkyrie valkyrie

# Fix if needed
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/
docker restart nextcloud
```

### Problem: Watcher not processing files
```bash
# Check service status
sudo systemctl status valkyrie-watcher

# View logs
sudo tail -100 /var/log/valkyrie_watcher.log

# Restart service
sudo systemctl restart valkyrie-watcher

# Test manually
sudo -u valkyrie /opt/valkyrie/inbox_watcher.sh
# Press Ctrl+C after testing
```

---

## 📊 DEPLOYMENT SUMMARY

### What You Have Now:

✅ **Valkyrie Memory System v4.2**
- Web interface at `http://yourdomain.com`
- RESTful API at `http://yourdomain.com/api.php`
- Memory storage at `/opt/valkyrie/memory/`
- CLI tool: `vproject`

✅ **Nextcloud Integration** (if deployed)
- Nextcloud at `http://yourdomain.com:8080`
- Valkyrie folder synced
- Cross-device access
- Mobile app support

✅ **Inbox Watcher** (if installed)
- Automated file processing
- Drop files → Auto-processed
- Systemd service running
- Logs at `/var/log/valkyrie_watcher.log`

### File Locations:

```
/opt/valkyrie/
├── memory/
│   ├── projects/          ← Your project memories
│   ├── global/            ← Cross-project context
│   ├── _incoming/         ← Drop files here
│   └── _archive/          ← Processed files
├── inbox_watcher.sh       ← Watcher script

/var/www/valkyrie/
├── index.html             ← Web interface
└── api.php                ← API backend

/etc/nginx/sites-available/
└── valkyrie               ← Nginx config
```

---

## 📚 NEXT STEPS

1. **Customize Projects**: Edit project names in install.sh and redeploy
2. **Setup SSL**: Install certbot for HTTPS
3. **Configure Backups**: Backup `/opt/valkyrie/memory/` regularly
4. **Mobile Setup**: Install Nextcloud app on phone
5. **Desktop Sync**: Install Nextcloud desktop client
6. **Integrate with AI**: Start using memory in AI sessions!

---

## 🆘 NEED MORE HELP?

- **Full Documentation**: See `DEPLOYMENT_CONFIG.md`
- **Nextcloud Guide**: See `2025-11-09_3Doc_Valkyrie_Memory_V4-2/NEXTCLOUD_DOCKER_INTEGRATION.md`
- **V4.2 Features**: See `2025-11-09_3Doc_Valkyrie_Memory_V4-2/V4.2_DEPLOYMENT_GUIDE.md`

---

**Version**: 4.2.0
**Last Updated**: 2025-11-10
**Status**: ✅ Ready to Deploy
