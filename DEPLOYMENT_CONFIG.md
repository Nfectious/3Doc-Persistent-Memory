# 🚀 DEPLOYMENT CONFIGURATION CHECKLIST

This file contains all the placeholders you need to update before deploying Valkyrie Memory System on your Contabo server.

---

## ⚙️ CONFIGURATION VALUES TO UPDATE

### 1. **Domain Configuration**
- **Your Domain**: `_________________________`
- **Example**: `aimem.contaboserver.com`

### 2. **Nextcloud Configuration**
- **Nextcloud Username**: `_________________________`
- **MySQL Root Password**: `_________________________`
- **MySQL User Password**: `_________________________`
- **Nextcloud Admin Password**: `_________________________`

### 3. **PHP Version**
- **Current PHP Version**: `_________________________`
- **Example**: `7.4` or `8.1` or `8.2`
- **To check**: Run `php -v` on your server

### 4. **Project Names**
List the projects you want to create (space-separated):
- **Default Projects**: `_________________________`
- **Example**: `my_project1 my_project2 my_project3`

---

## 📝 FILES TO UPDATE

### File 1: `scripts/install.sh`

**Lines 30-35** - Update these variables:

```bash
# TODO: Update these placeholders before running
DOMAIN="aimem.yourdomain.com"  # ← Change to: YOUR_DOMAIN
PHP_VERSION="7.4"               # ← Change to: YOUR_PHP_VERSION

# Default projects to create (customize as needed)
DEFAULT_PROJECTS="project1 project2 project3"  # ← Change to: YOUR_PROJECT_NAMES
```

**What to change**:
- Replace `aimem.yourdomain.com` with your actual domain
- Replace `7.4` with your PHP version (check with `php -v`)
- Replace `project1 project2 project3` with your actual project names

---

### File 2: `docker-compose.yml`

**Lines 37-38** - Nextcloud username:
```yaml
- /opt/valkyrie/memory:/var/www/html/data/YOUR_NEXTCLOUD_USERNAME/files/Valkyrie:rw
```
**Change**: `YOUR_NEXTCLOUD_USERNAME` → Your actual Nextcloud username

**Line 48** - MySQL password:
```yaml
- MYSQL_PASSWORD=YOUR_MYSQL_PASSWORD
```
**Change**: `YOUR_MYSQL_PASSWORD` → Choose a strong password

**Line 52** - Nextcloud admin password:
```yaml
- NEXTCLOUD_ADMIN_PASSWORD=admin123  # TODO: Change this!
```
**Change**: `admin123` → Choose a strong admin password

**Line 55** - Trusted domains:
```yaml
- NEXTCLOUD_TRUSTED_DOMAINS=YOUR_DOMAIN localhost 127.0.0.1
```
**Change**: `YOUR_DOMAIN` → Your actual domain

**Line 74** - MySQL root password:
```yaml
- MYSQL_ROOT_PASSWORD=YOUR_MYSQL_ROOT_PASSWORD
```
**Change**: `YOUR_MYSQL_ROOT_PASSWORD` → Choose a strong root password

**Line 80** - MySQL password (must match line 48):
```yaml
- MYSQL_PASSWORD=YOUR_MYSQL_PASSWORD
```
**Change**: `YOUR_MYSQL_PASSWORD` → Same password as line 48

---

### File 3: `scripts/inbox_watcher.sh`

**Line 19** - API URL:
```bash
API_URL="http://localhost/valkyrie/api.php?action=process_export"
```
**Change**: Replace `localhost` with your domain if accessing externally, or leave as-is for local access

**Lines 54-59** - Project detection rules:
```bash
case "$LOWER" in
    *project1*) PROJECT="project1" ;;
    *project2*) PROJECT="project2" ;;
    *project3*) PROJECT="project3" ;;
esac
```
**Change**: Update project names to match your actual projects

---

## 🔧 QUICK REPLACEMENT COMMANDS

Use these commands to quickly update placeholders:

### 1. Update Domain (Replace YOUR_DOMAIN with your actual domain)
```bash
cd /home/user/3Doc-Persistent-Memory

# Update install.sh
sed -i 's/aimem\.yourdomain\.com/YOUR_DOMAIN/g' scripts/install.sh

# Update docker-compose.yml
sed -i 's/YOUR_DOMAIN/YOUR_ACTUAL_DOMAIN/g' docker-compose.yml
```

### 2. Update PHP Version (Replace 8.1 with your version)
```bash
sed -i 's/PHP_VERSION="7.4"/PHP_VERSION="8.1"/g' scripts/install.sh
```

### 3. Update Project Names
```bash
sed -i 's/DEFAULT_PROJECTS="project1 project2 project3"/DEFAULT_PROJECTS="myproject1 myproject2"/g' scripts/install.sh
```

### 4. Update Nextcloud Username
```bash
sed -i 's/YOUR_NEXTCLOUD_USERNAME/myusername/g' docker-compose.yml
```

---

## ✅ PRE-DEPLOYMENT CHECKLIST

Before running `sudo bash scripts/install.sh`, verify:

- [ ] Updated `DOMAIN` in `scripts/install.sh`
- [ ] Updated `PHP_VERSION` in `scripts/install.sh`
- [ ] Updated `DEFAULT_PROJECTS` in `scripts/install.sh`
- [ ] Updated `YOUR_NEXTCLOUD_USERNAME` in `docker-compose.yml` (3 places)
- [ ] Updated `YOUR_MYSQL_PASSWORD` in `docker-compose.yml` (2 places - must match!)
- [ ] Updated `YOUR_MYSQL_ROOT_PASSWORD` in `docker-compose.yml`
- [ ] Updated `NEXTCLOUD_ADMIN_PASSWORD` in `docker-compose.yml`
- [ ] Updated `YOUR_DOMAIN` in `docker-compose.yml`
- [ ] Updated `API_URL` in `scripts/inbox_watcher.sh` (if needed)
- [ ] Updated project detection rules in `scripts/inbox_watcher.sh`

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Update Configuration Files
Follow the instructions above to update all placeholder values.

### Step 2: Run Valkyrie Installation
```bash
cd /home/user/3Doc-Persistent-Memory
sudo bash scripts/install.sh
```

### Step 3: Deploy Nextcloud (Optional)
```bash
cd /home/user/3Doc-Persistent-Memory
docker-compose up -d
```

### Step 4: Fix Permissions for Nextcloud
```bash
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/
```

### Step 5: Scan Nextcloud Files
```bash
docker exec -u www-data nextcloud php occ files:scan YOUR_NEXTCLOUD_USERNAME
```

### Step 6: Install Inbox Watcher (Optional)
```bash
# Copy systemd service file
sudo cp scripts/inbox_watcher.sh /opt/valkyrie/inbox_watcher.sh
sudo chmod +x /opt/valkyrie/inbox_watcher.sh

# Create systemd service
sudo nano /etc/systemd/system/valkyrie-watcher.service
```

Paste this content:
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

Then start the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable valkyrie-watcher
sudo systemctl start valkyrie-watcher
```

### Step 7: Verify Installation
```bash
# Check Valkyrie web interface
curl http://YOUR_DOMAIN

# Check Nextcloud
curl http://YOUR_DOMAIN:8080

# Check watcher service
sudo systemctl status valkyrie-watcher
```

---

## 📊 SUMMARY OF CHANGES FROM v4.1 → v4.2

### ✅ Updated Files:
1. `src/web/index.html` - Updated to v4.2 with paste functionality
2. `src/web/api.php` - Updated to v4.2 with `process_export` endpoint
3. `scripts/install.sh` - Added configuration variables, fixed nginx config
4. `scripts/inbox_watcher.sh` - Added configuration section
5. `docker-compose.yml` - **NEW FILE** - Complete Nextcloud setup

### ✅ Fixes Applied:
1. ✅ Nginx `limit_req_zone` directive removed (was in wrong location)
2. ✅ PHP version now configurable
3. ✅ Domain name configurable
4. ✅ Project names configurable
5. ✅ All placeholders clearly marked with TODO comments
6. ✅ Added client_max_body_size 10M for file uploads

### ✅ New Features:
1. ✅ Paste functionality for session exports (v4.2)
2. ✅ Better export parsing in API (v4.2)
3. ✅ Complete Docker Compose setup for Nextcloud
4. ✅ Clear deployment configuration checklist

---

## 🆘 NEED HELP?

### Common Issues:

**Issue**: nginx won't start
- **Check**: `sudo nginx -t`
- **Fix**: Look for configuration errors in `/etc/nginx/sites-available/valkyrie`

**Issue**: PHP-FPM not found
- **Check**: `systemctl status phpX.X-fpm` (replace X.X with your version)
- **Fix**: Install correct PHP version: `sudo apt install phpX.X-fpm`

**Issue**: Nextcloud not starting
- **Check**: `docker-compose logs -f nextcloud`
- **Fix**: Verify all passwords match between services

**Issue**: Files not showing in Nextcloud
- **Check**: Permissions on `/opt/valkyrie/memory/`
- **Fix**: `sudo chown -R 33:33 /opt/valkyrie/memory/`

---

## 📚 Additional Documentation

- **Nextcloud Integration Guide**: `2025-11-09_3Doc_Valkyrie_Memory_V4-2/NEXTCLOUD_DOCKER_INTEGRATION.md`
- **V4.2 Deployment Guide**: `2025-11-09_3Doc_Valkyrie_Memory_V4-2/V4.2_DEPLOYMENT_GUIDE.md`
- **README**: `README.md`

---

**Version**: 4.2.0
**Last Updated**: 2025-11-10
**Status**: Ready for deployment after placeholder updates
