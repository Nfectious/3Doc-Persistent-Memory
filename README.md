# 🩸 Valkyrie Memory System

<div align="center">

![Version](https://img.shields.io/badge/version-4.1.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Platform](https://img.shields.io/badge/platform-Linux%20|%20Windows-lightgrey)
![Status](https://img.shields.io/badge/status-production-success)

**Multi-Project Memory Management for AI Sessions**

*Never lose context. Never restart from zero. Never explain your project again.*

[Features](#-features) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Demo](#-demo) • [Contributing](#-contributing)

</div>

---

## 📖 Overview

Valkyrie Memory System is a **project-based memory persistence solution** designed to solve the frustrating problem of losing context when working with AI assistants. Whether you hit token limits, switch sessions, or restart conversations, Valkyrie ensures your project context is always preserved and instantly loadable.

### The Problem

Working with AI on multiple technical projects creates constant friction:
- 🔄 Token limits force session restarts
- 📝 Re-explaining context wastes time
- 🔀 Multiple projects cause context bleeding
- 💾 No persistent memory between sessions
- 🤯 Cognitive load from managing mental state

### The Solution

**Valkyrie Memory System** provides:
- ✅ **Project-based organization** - Clean separation between distinct projects
- ✅ **One-click context loading** - Copy entire project memory instantly
- ✅ **Multi-project support** - Combine contexts when working across domains
- ✅ **Web interface** - Accessible from any device, anywhere
- ✅ **Persistent storage** - Memory survives sessions, reboots, everything
- ✅ **Zero setup complexity** - Deploy in 5 minutes

---

## ✨ Features

### 🎯 Core Functionality

- **Multi-Project Memory**: Separate memory spaces for each project
- **One-Click Copy**: Load entire project context with a single button
- **Multi-Select**: Combine multiple projects for cross-context work
- **Memory Preview**: See what you're loading before copying
- **Quick Append**: Fast notes directly to project memory
- **Visual Dashboard**: See all projects, status, and activity at a glance

### 🎨 User Experience

- **Web Interface**: Terminal-style design, responsive, mobile-friendly
- **Visual Indicators**: Active/inactive projects, timestamps, stats
- **Tabbed Navigation**: Dashboard, Session Start, Quick Actions, Management
- **Auto-Sync**: Integrates with Nextcloud or any sync service
- **Cross-Platform**: Works on Linux, Windows, mobile devices

### 🏗️ Architecture

- **Project-Based Storage**: `/opt/valkyrie/memory/projects/`
- **3-File System**: PROJECT_MEMORY.md, INSIGHTS_LOG.md, NEXT_ACTIONS.md
- **RESTful API**: JSON endpoints for all operations
- **File-System First**: Plain markdown files, no database required
- **Sync-Friendly**: Works with Dropbox, Nextcloud, Google Drive

---

## 🚀 Quick Start

### Prerequisites

- Linux server (Ubuntu 20.04+ recommended) or Windows with WSL
- Nginx web server
- PHP 7.4+
- SSH access with sudo privileges

### Installation

```bash
# 1. Download repository
git clone https://github.com/yourusername/valkyrie-memory-system.git
cd valkyrie-memory-system

# 2. Run installer
chmod +x install.sh
sudo bash install.sh

# 3. Access web interface
# http://your-server-ip/valkyrie
# or configure your domain: http://aimem.yourdomain.com
```

### First Use

1. **Open the dashboard** - See your initial projects
2. **Select a project** - Click any project or use Session Start tab
3. **Copy memory** - Click "📋 Copy Selected Memory for AI"
4. **Paste to AI** - Start your AI session with full context loaded

---

## 📊 Demo

### Dashboard View
```
╔══════════════════════════════════════════════════════╗
║  📊 VALKYRIE MEMORY COMMAND CENTER                   ║
╠══════════════════════════════════════════════════════╣
║  📁 ACTIVE PROJECTS                                  ║
║  ┌────────────────────────────────────────────────┐ ║
║  │ ✅ ebay_autods_bot          2h ago             │ ║
║  │ ✅ phoenix_commandops        5h ago             │ ║
║  │ ✅ madison_mission           1d ago             │ ║
║  │ ⏸️  overthrow                3d ago             │ ║
║  │ ⏸️  truthvault               1w ago             │ ║
║  └────────────────────────────────────────────────┘ ║
║                                                      ║
║  📊 STATS: 5 Projects | 3 Active | 47KB Memory      ║
╚══════════════════════════════════════════════════════╝
```

### Multi-Project Selection
```
Select project(s) to load:
☑ Global (cross-project context)
☑ ebay_autods_bot
☑ phoenix_commandops
☐ madison_mission

[📋 Copy Selected Memory for AI]

→ Loads all 3 contexts combined into AI session
```

---

## 🗂️ Project Structure

```
valkyrie-memory-system/
├── src/
│   ├── web/
│   │   ├── index.html          # Main web interface
│   │   ├── api.php             # REST API
│   │   └── assets/             # CSS, JS, images
│   ├── cli/
│   │   ├── vproject             # CLI management tool
│   │   └── session_start.sh    # Session helper
│   └── config/
│       └── config.php          # Configuration
├── scripts/
│   ├── install.sh              # Main installer
│   ├── upgrade.sh              # Version upgrade
│   └── backup.sh               # Backup utility
├── docs/
│   ├── installation.md         # Installation guide
│   ├── usage.md                # Usage guide
│   ├── api.md                  # API documentation
│   └── architecture.md         # System architecture
├── examples/
│   └── memory-templates/       # Example memory files
├── tests/
│   └── api-tests.sh            # API tests
├── .github/
│   ├── workflows/
│   │   └── ci.yml              # CI/CD pipeline
│   └── ISSUE_TEMPLATE/         # Issue templates
├── LICENSE                     # MIT License
├── README.md                   # This file
├── CHANGELOG.md                # Version history
├── CONTRIBUTING.md             # Contribution guide
└── SECURITY.md                 # Security policy
```

---

## 📚 Documentation

### User Documentation
- [Installation Guide](docs/installation.md) - Complete setup instructions
- [Usage Guide](docs/usage.md) - How to use Valkyrie effectively
- [CLI Reference](docs/cli-reference.md) - Command-line tools
- [Configuration](docs/configuration.md) - Customization options

### Developer Documentation
- [API Reference](docs/api.md) - REST API endpoints
- [Architecture](docs/architecture.md) - System design
- [Contributing](CONTRIBUTING.md) - How to contribute
- [Development Setup](docs/development.md) - Local dev environment

### Advanced Topics
- [Multi-Server Setup](docs/multi-server.md) - Distributed deployment
- [Integration Guide](docs/integration.md) - Sync services, automation
- [Troubleshooting](docs/troubleshooting.md) - Common issues and solutions
- [Migration Guide](docs/migration.md) - Upgrading between versions

---

## 💡 Use Cases

### 1. Multi-Project Development
**Scenario:** Working on 5 different coding projects simultaneously  
**Solution:** Each project has isolated memory. Switch contexts instantly.

### 2. Long-Running Projects
**Scenario:** Project spans weeks/months with multiple AI sessions  
**Solution:** Continuous memory. Never lose progress between sessions.

### 3. Cross-Domain Work
**Scenario:** Need context from multiple projects at once  
**Solution:** Multi-select projects to combine contexts.

### 4. Team Collaboration
**Scenario:** Multiple team members working with AI on shared projects  
**Solution:** Sync memory folder via Nextcloud/Dropbox for shared context.

### 5. Personal Knowledge Base
**Scenario:** Building personal documentation and insights over time  
**Solution:** Insights log accumulates knowledge. Searchable history.

---

## 🔧 Configuration

### Basic Configuration
```php
// config/config.php
define('MEMORY_BASE', '/opt/valkyrie/memory');
define('WEB_ROOT', '/var/www/valkyrie');
define('DOMAIN', 'aimem.yourdomain.com');
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name aimem.yourdomain.com;
    root /var/www/valkyrie;
    index index.html;
    
    location /api.php {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Custom Projects
```bash
# Create new project via CLI
vproject create my_new_project

# Or via API
curl -X POST http://aimem.yourdomain.com/api.php?action=create_project \
  -H "Content-Type: application/json" \
  -d '{"project":"my_new_project"}'
```

---

## 🛣️ Roadmap

### Version 4.1 (Current)
- ✅ Multi-project selection
- ✅ Visual dashboard
- ✅ Memory preview
- ✅ Project status indicators

### Version 4.2 (Planned)
- 🔄 Real-time sync status
- 🔍 Search across all projects
- 📊 Analytics and insights
- 🎨 Theme customization

### Version 5.0 (Future)
- 🔐 Authentication system
- 👥 Multi-user support
- 🌐 Cloud deployment option
- 📱 Native mobile app
- 🤖 AI integration APIs

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:
- Code of conduct
- Development setup
- Pull request process
- Coding standards
- Testing requirements

### Quick Contribution
```bash
# 1. Fork the repository
# 2. Create feature branch
git checkout -b feature/amazing-feature

# 3. Make changes and commit
git commit -m "Add amazing feature"

# 4. Push to branch
git push origin feature/amazing-feature

# 5. Open Pull Request
```

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Built to solve real memory persistence problems in AI workflows
- Inspired by the need for continuity across AI sessions
- Designed for developers, by developers
- Special thanks to the AI community for feedback and testing

---

## 📞 Support

### Getting Help
- 📖 [Documentation](docs/)
- 🐛 [Issue Tracker](https://github.com/yourusername/valkyrie-memory-system/issues)
- 💬 [Discussions](https://github.com/yourusername/valkyrie-memory-system/discussions)

### Reporting Issues
Found a bug? Please [open an issue](https://github.com/yourusername/valkyrie-memory-system/issues/new) with:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- System information

---

## 🔗 Links

- **Homepage**: [valkyriehq.com](https://valkyriehq.com) *(placeholder)*
- **Documentation**: [docs.valkyriehq.com](https://docs.valkyriehq.com) *(placeholder)*
- **Blog**: [blog.valkyriehq.com](https://blog.valkyriehq.com) *(placeholder)*

---

## 📈 Stats

![GitHub stars](https://img.shields.io/github/stars/yourusername/valkyrie-memory-system?style=social)
![GitHub forks](https://img.shields.io/github/forks/yourusername/valkyrie-memory-system?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/yourusername/valkyrie-memory-system?style=social)

---

<div align="center">

**Built with 🩸 by [Operation Phoenix](https://github.com/yourusername)**

*Never lose context again.*

</div>
