# 🔗 Existing Nextcloud Integration Guide

**For users who already have Nextcloud running in Docker**

You **DO NOT** need the MySQL database or a new Nextcloud installation. This guide shows how to integrate Valkyrie with your existing Nextcloud container.

---

## 🎯 What You Need to Do

1. **Find your Nextcloud docker-compose.yml**
2. **Add one line** to mount Valkyrie memory
3. **Restart your container**
4. **Fix permissions**
5. **Scan files**

That's it! **No database needed.**

---

## 📋 Quick Integration (5 Minutes)

### Step 1: Find Your Nextcloud Setup

```bash
# Find your docker-compose.yml
find / -name "docker-compose.yml" -path "*/nextcloud/*" 2>/dev/null

# Or find container name
docker ps | grep nextcloud
```

Note the path to your docker-compose.yml and your container name!

### Step 2: Backup Your Config

```bash
# ALWAYS backup first!
cp /path/to/your/docker-compose.yml /path/to/your/docker-compose.yml.backup.$(date +%Y%m%d)
```

### Step 3: Add Valkyrie Mount

Edit your existing docker-compose.yml:

```bash
nano /path/to/your/docker-compose.yml
```

Find your `nextcloud` service and add this line to the `volumes:` section:

```yaml
services:
  nextcloud:
    # ... existing config ...
    volumes:
      # ... your existing volumes ...
      - /opt/valkyrie/memory:/var/www/html/data/YOUR_NEXTCLOUD_USERNAME/files/Valkyrie:rw
```

**IMPORTANT**: Replace `YOUR_NEXTCLOUD_USERNAME` with your actual Nextcloud username!

**Example:**
If your username is `admin`, the line should be:
```yaml
- /opt/valkyrie/memory:/var/www/html/data/admin/files/Valkyrie:rw
```

If your username is `john`, the line should be:
```yaml
- /opt/valkyrie/memory:/var/www/html/data/john/files/Valkyrie:rw
```

### Step 4: Restart Nextcloud

```bash
# Navigate to your docker-compose directory
cd /path/to/your/nextcloud/directory

# Restart with new mount
docker-compose down
docker-compose up -d

# Verify it's running
docker-compose ps
```

### Step 5: Fix Permissions

```bash
# Set correct ownership (www-data UID is usually 33)
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/
```

### Step 6: Scan Files in Nextcloud

```bash
# Replace YOUR_CONTAINER_NAME and YOUR_USERNAME
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan YOUR_USERNAME

# Example:
# docker exec -u www-data nextcloud php occ files:scan admin
```

### Step 7: Verify in Nextcloud

1. Open your Nextcloud in a browser
2. Log in
3. Go to Files
4. You should see "Valkyrie" folder!

---

## 🔧 If You Don't Use Docker Compose

If you started Nextcloud with a `docker run` command instead:

### Step 1: Find Your Current Run Command

```bash
# Get container details
docker inspect YOUR_CONTAINER_NAME | grep -A 100 "Cmd"

# See current mounts
docker inspect YOUR_CONTAINER_NAME | grep -A 20 "Mounts"
```

### Step 2: Recreate Container with New Mount

```bash
# Stop current container
docker stop YOUR_CONTAINER_NAME

# Remove container (your data is safe in volumes!)
docker rm YOUR_CONTAINER_NAME

# Run with new mount (add to your existing command)
docker run -d \
  --name YOUR_CONTAINER_NAME \
  [... all your existing parameters ...] \
  -v /opt/valkyrie/memory:/var/www/html/data/YOUR_USERNAME/files/Valkyrie:rw \
  [... rest of your parameters ...]
```

---

## ✅ Verification

### Check 1: Mount is Present

```bash
docker exec YOUR_CONTAINER_NAME ls -la /var/www/html/data/YOUR_USERNAME/files/
# Should show "Valkyrie" directory
```

### Check 2: Files Visible in Nextcloud

```bash
# Scan files
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan YOUR_USERNAME

# Check via web interface
# Open Nextcloud → Files → Should see "Valkyrie" folder
```

### Check 3: Can Upload Files

```bash
# Create test file
echo "Test from server" > /opt/valkyrie/memory/_incoming/test.txt

# Check in Nextcloud web interface
# Go to: Files → Valkyrie → _incoming → Should see test.txt
```

---

## 🐛 Troubleshooting

### Issue: Valkyrie folder not showing

**Solution 1**: Force rescan
```bash
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan --all
```

**Solution 2**: Check permissions
```bash
ls -la /opt/valkyrie/memory/
# Should show: drwxrwxr-x ... 33 www-data

# Fix if wrong
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/
```

**Solution 3**: Restart Nextcloud
```bash
docker restart YOUR_CONTAINER_NAME
# Wait 30 seconds
docker exec -u www-data YOUR_CONTAINER_NAME php occ files:scan --all
```

### Issue: Permission denied errors

**Solution**:
```bash
# Check UID of www-data in container
docker exec YOUR_CONTAINER_NAME id www-data
# Note the UID (usually 33)

# Set ownership to that UID
sudo chown -R 33:33 /opt/valkyrie/memory/
sudo chmod -R 775 /opt/valkyrie/memory/

# Add local users to www-data group
sudo usermod -a -G www-data valkyrie
sudo usermod -a -G www-data $USER
```

### Issue: Mount not working after restart

**Solution**: Check your docker-compose.yml syntax
```bash
# Validate docker-compose.yml
cd /path/to/your/nextcloud/directory
docker-compose config

# If errors, check:
# - Proper YAML indentation (use spaces, not tabs)
# - Volume path is absolute (/opt/valkyrie/memory)
# - Username is correct
```

---

## 📊 What You DON'T Need

Since you have existing Nextcloud:

❌ **MySQL/MariaDB** - You already have your database
❌ **New Nextcloud container** - You use your existing one
❌ **Redis** (unless you want to add it) - Optional
❌ **New docker-compose.yml** - Just edit your existing one

---

## ✅ What You DO Need

✅ **Valkyrie Installation** - Run `scripts/install.sh`
✅ **One volume mount line** - Added to your existing docker-compose.yml
✅ **Permission fix** - `chown -R 33:33 /opt/valkyrie/memory/`
✅ **File scan** - `docker exec ... php occ files:scan`

---

## 🎯 Complete Example

Here's what a typical existing docker-compose.yml looks like before and after:

### BEFORE:
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
    environment:
      - MYSQL_HOST=db
      - MYSQL_DATABASE=nextcloud
      - MYSQL_USER=nextcloud
      - MYSQL_PASSWORD=mypassword

  db:
    image: mariadb
    # ... your existing db config ...

volumes:
  nextcloud_data:
```

### AFTER (only change - added one line):
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
      - /opt/valkyrie/memory:/var/www/html/data/admin/files/Valkyrie:rw  # ← ADDED THIS
    environment:
      - MYSQL_HOST=db
      - MYSQL_DATABASE=nextcloud
      - MYSQL_USER=nextcloud
      - MYSQL_PASSWORD=mypassword

  db:
    image: mariadb
    # ... your existing db config ...

volumes:
  nextcloud_data:
```

That's it! One line added, then restart.

---

## 🚀 Next Steps After Integration

Once Valkyrie folder appears in Nextcloud:

1. **Install Inbox Watcher** (optional) - See `agent.md` Step 9
2. **Configure Mobile App** - See `agent.md` Mobile Access section
3. **Start Using Valkyrie** - Upload files, sync across devices

---

## 📱 Your Workflow Now

### From Server:
```bash
# Edit memory directly
nano /opt/valkyrie/memory/projects/project1/PROJECT_MEMORY.md

# Or via API
curl http://localhost/api.php?action=append -d '{"project":"project1","text":"note"}'
```

### From Nextcloud Web:
- Browse to: Files → Valkyrie → projects → project1
- Edit files directly in Nextcloud
- Upload to _incoming/ for auto-processing

### From Mobile:
- Open Nextcloud app
- Navigate to Valkyrie folder
- Upload session exports to _incoming/
- Auto-processed in 1-2 seconds!

### From Desktop:
- Install Nextcloud desktop client
- Sync Valkyrie folder
- Edit files locally
- Auto-syncs to server

---

## ⏱️ Total Integration Time

- **Find docker-compose.yml**: 1 minute
- **Add mount line**: 1 minute
- **Restart container**: 1 minute
- **Fix permissions**: 1 minute
- **Scan files**: 1 minute

**Total**: ~5 minutes

---

**No database needed. No new Nextcloud needed. Just one volume mount!**

---

**For detailed agent integration**, see: [agent.md](agent.md)
**For full deployment guide**, see: [QUICK_DEPLOY.md](QUICK_DEPLOY.md)
