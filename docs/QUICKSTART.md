# Quick Start Guide

Get Valkyrie Memory System running in 5 minutes.

## Prerequisites

- Linux server (Ubuntu 20.04+ or Debian 10+)
- Root/sudo access
- Internet connection

## Installation

### 1. Download

```bash
git clone https://github.com/yourusername/valkyrie-memory-system.git
cd valkyrie-memory-system
```

### 2. Install

```bash
chmod +x scripts/install.sh
sudo bash scripts/install.sh
```

### 3. Access

Open in browser:
```
http://YOUR-SERVER-IP/valkyrie
```

Or configure DNS and access via domain:
```
http://aimem.yourdomain.com
```

## First Use

### 1. Open Dashboard

You'll see the main dashboard with default projects:
- ebay_autods_bot
- phoenix_commandops
- madison_mission
- overthrow
- truthvault

### 2. Select Project

**Option A: From Dashboard**
- Click any project in the list
- Auto-switches to Session Start

**Option B: Session Start Tab**
- Click "🚀 Session Start" tab
- Check project checkboxes
- Can select multiple projects

### 3. Copy Memory

```
1. Projects selected
2. Click: 📋 Copy Selected Memory for AI
3. Paste into your AI session
```

That's it! Your AI now has full project context.

## Daily Workflow

### Morning: Start Work Session

```bash
# Option 1: Web Interface
1. Open: http://aimem.yourdomain.com
2. Session Start tab
3. Select project
4. Copy memory
5. Paste to AI

# Option 2: CLI
vproject view my_project | pbcopy  # Mac
vproject view my_project | xclip   # Linux
```

### During Work: Quick Notes

```bash
# Quick append via web
1. Quick Actions tab
2. Select project
3. Type note
4. Click Append

# Or via API
curl -X POST http://aimem.yourdomain.com/api.php?action=append \
  -H "Content-Type: application/json" \
  -d '{"project":"my_project","text":"Important insight here"}'
```

### End of Day: Upload Session

```
1. Export your AI session (if supported)
2. Upload via web interface
3. Auto-routes to correct project
```

## Common Tasks

### Create New Project

```bash
# CLI
vproject create my_new_project

# Or via web
1. Manage tab
2. Enter project name
3. Click Create
```

### Multi-Project Context

```
1. Session Start tab
2. Check multiple projects:
   ☑ Global
   ☑ project_one
   ☑ project_two
3. Copy memory (all combined)
```

### Preview Before Copy

```
1. Select project(s)
2. Click: 👁️ Preview Memory
3. Review content
4. Click: 📋 Copy if good
```

## Configuration

### Custom Domain

```bash
# 1. Configure DNS
# Point aimem.yourdomain.com to your server IP

# 2. Update Nginx
sudo nano /etc/nginx/sites-available/valkyrie

# Change:
server_name _;
# To:
server_name aimem.yourdomain.com;

# 3. Add SSL (optional but recommended)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d aimem.yourdomain.com

# 4. Restart
sudo systemctl restart nginx
```

### Custom Projects

Edit default projects by modifying:
```bash
sudo nano /opt/valkyrie/memory/projects/
```

### Sync Integration

```bash
# Install Nextcloud client or similar
sudo apt install nextcloud-desktop

# Sync /opt/valkyrie/memory/ folder
# Configure in Nextcloud settings
```

## Troubleshooting

### Can't access web interface

```bash
# Check Nginx status
sudo systemctl status nginx

# Check PHP status
sudo systemctl status php7.4-fpm

# Check firewall
sudo ufw status
sudo ufw allow 80/tcp
```

### Copy button doesn't work

```bash
# Check browser console (F12)
# Check API is accessible
curl http://localhost/valkyrie/api.php?action=list_projects
```

### Permissions errors

```bash
# Fix ownership
sudo chown -R valkyrie:valkyrie /opt/valkyrie/memory
sudo chmod -R 750 /opt/valkyrie/memory

# Fix web directory
sudo chown -R www-data:www-data /var/www/valkyrie
sudo chmod -R 755 /var/www/valkyrie
```

## Next Steps

- [Full Documentation](installation.md)
- [Usage Guide](usage.md)
- [API Reference](api.md)
- [Troubleshooting](troubleshooting.md)

## Support

- **Issues**: https://github.com/yourusername/valkyrie-memory-system/issues
- **Discussions**: https://github.com/yourusername/valkyrie-memory-system/discussions
- **Email**: support@valkyriehq.com *(placeholder)*

---

**You're ready! Start building persistent memory for your AI workflows.** 🩸
