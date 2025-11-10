# Changelog

All notable changes to the Valkyrie Memory System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.1.0] - 2025-11-07

### Added
- **Project Dashboard**: Visual overview of all projects on main screen
- **Multi-Project Selection**: Select multiple projects to combine contexts
- **Memory Preview**: Preview memory contents before copying
- **Visual Status Indicators**: Active (✅) and inactive (⏸️) project states
- **Activity Timestamps**: Show last update time for each project (2h ago, 5h ago, etc.)
- **Tabbed Interface**: Organized navigation (Dashboard, Session Start, Quick Actions, Manage)
- **Stats Panel**: Total projects, active count, memory size, last updated
- **Quick Actions Tab**: Fast append functionality
- **Project Management Tab**: Create new projects, upload exports

### Fixed
- **Global Project Loading**: Fixed bug where global memory files weren't loading
- **Copy Memory Error**: Resolved JavaScript error when clicking copy button
- **Empty Memory Files**: Auto-creates memory files if they don't exist
- **API Error Handling**: Improved error messages and fallback handling

### Changed
- Improved UI with color-coded status indicators
- Enhanced mobile responsiveness
- Better error messages for troubleshooting
- Clearer project nomenclature (removed confusing "Cross-Project" label)

### Technical
- Complete API rewrite for multi-project support
- Added `get_memory` endpoint with project array parameter
- Enhanced PHP error handling and validation
- Improved JavaScript error handling and user feedback

## [4.0.0] - 2025-11-05

### Added
- **Project-Based Architecture**: Separate memory spaces for each project
- **Web Interface**: Terminal-style web GUI for memory management
- **One-Click Copy**: Copy entire project memory with single button
- **Quick Append**: Add notes to project memory from web interface
- **Session Export Upload**: Upload AI session exports to project memory
- **CLI Tools**: Command-line interface for power users
- **Global Project**: Cross-project context storage

### Changed
- Migrated from single memory to project-based organization
- Replaced command-line only workflow with web interface
- Improved file organization structure

### Technical
- PHP REST API for all operations
- Nginx configuration for web serving
- Markdown-based storage format
- File-system first approach (no database)

## [3.0.0] - 2025-10-20

### Added
- **3-File Memory System**: PROJECT_MEMORY.md, INSIGHTS_LOG.md, NEXT_ACTIONS.md
- **Structured Format**: Consistent markdown formatting
- **Append-Only Operations**: Safe concurrent writes
- **Session Start Script**: Load memory at session start

### Changed
- Moved from single file to three specialized files
- Improved organization and categorization

## [2.0.0] - 2025-10-01

### Added
- **Persistent Storage**: Memory survives between sessions
- **Sync Integration**: Works with Nextcloud/Dropbox
- **Cross-Platform Support**: Linux and Windows compatibility

### Changed
- Moved from temporary files to permanent storage
- Added backup functionality

## [1.0.0] - 2025-09-15

### Added
- **Initial Release**: Basic memory persistence
- **Single File System**: Simple append-only memory file
- **Manual Copy/Paste**: Text file based workflow

---

## Version History Summary

| Version | Date | Key Feature |
|---------|------|-------------|
| 4.1.0 | 2025-11-07 | Multi-project selection, dashboard |
| 4.0.0 | 2025-11-05 | Web interface, project-based |
| 3.0.0 | 2025-10-20 | 3-file memory system |
| 2.0.0 | 2025-10-01 | Persistent storage, sync |
| 1.0.0 | 2025-09-15 | Initial release |

---

## Upgrade Guide

### From 4.0.x to 4.1.0
```bash
# Automatic upgrade script provided
sudo bash upgrade_v4_to_v4.1.sh

# Test at /index_v4.1.html before going live
sudo bash go_live_v4.1.sh
```

### From 3.x to 4.0.0
```bash
# Migration script handles data conversion
sudo bash migrate_v3_to_v4.sh
```

See [docs/migration.md](docs/migration.md) for detailed upgrade instructions.

---

## Breaking Changes

### Version 4.0.0
- **Storage Structure**: Moved from `/opt/valkyrie/memory/*.md` to `/opt/valkyrie/memory/projects/*/`
- **API Changes**: New REST endpoints, old CLI-only interface deprecated
- **Configuration**: New config file format required

Migration tools provided for seamless upgrade.

---

## Deprecation Notices

### Version 4.0.0
- **Single Memory File**: Deprecated in favor of project-based organization
- **Direct File Editing**: Recommend using web interface or API instead

---

## Security Updates

### Version 4.1.0
- Improved input validation in API endpoints
- Better error handling to prevent information leakage
- Enhanced file permission checks

### Version 4.0.0
- Added CSRF protection for API endpoints
- Implemented request rate limiting
- Sanitized file path inputs

---

## Known Issues

### Version 4.1.0
- None reported yet

### Version 4.0.0
- Fixed in 4.1.0: Global project loading issue
- Fixed in 4.1.0: Copy button JavaScript error

---

## Future Plans

See [ROADMAP.md](ROADMAP.md) for upcoming features and planned improvements.

---

**Legend**:
- `Added` - New features
- `Changed` - Changes in existing functionality
- `Deprecated` - Soon-to-be removed features
- `Removed` - Removed features
- `Fixed` - Bug fixes
- `Security` - Security improvements
