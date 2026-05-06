# Valkyrie Memory System
**Server:** Phoenix — 109.199.104.166
**Base path:** /opt/memory/projects/
**Installed:** $(date '+%Y-%m-%d')

## Purpose
Persistent project memory across Claude sessions.
Solves context loss, session length limits, and MS-related continuity needs.

## Commands

### vmem — load project context
```bash
vmem valkyrie_scans_crypto
```
Copy full output. Paste as first message to Claude. Context restored.

### vsave — save Claude output to memory files
```bash
# Save PROJECT_MEMORY (overwrites):
cat > /tmp/mem.md << 'PASTE'
[paste Claude output here]
PASTE
vsave valkyrie_scans_crypto memory < /tmp/mem.md

# Append to INSIGHTS_LOG (never overwrites):
cat > /tmp/ins.md << 'PASTE'
[paste Claude output here]
PASTE
vsave valkyrie_scans_crypto insights < /tmp/ins.md

# Save NEXT_ACTIONS (overwrites):
cat > /tmp/act.md << 'PASTE'
[paste Claude output here]
PASTE
vsave valkyrie_scans_crypto actions < /tmp/act.md
```

### vprojects — list all projects
```bash
vprojects
```

## File Types
| File | Behavior | Purpose |
|------|----------|---------|
| PROJECT_MEMORY.md | Overwrite each session | Current state snapshot |
| INSIGHTS_LOG.md | Append each session | Cumulative lessons learned |
| NEXT_ACTIONS.md | Overwrite each session | Current priority queue |

## Projects
| Project | Path | Description |
|---------|------|-------------|
| valkyrie_scans_crypto | /opt/memory/projects/valkyrie_scans_crypto/ | Valkyrie Crypto Intel platform |

## Adding a New Project
```bash
mkdir -p /opt/memory/projects/[project_name]
```

## Session Workflow

### End of session (in Claude):
Run the 3-file generation prompt. Copy each output block.

### End of session (on Phoenix):
```bash
# Store all 3 files
cat > /tmp/mem.md << 'PASTE'
[PROJECT_MEMORY content]
PASTE
vsave valkyrie_scans_crypto memory < /tmp/mem.md

cat > /tmp/ins.md << 'PASTE'
[INSIGHTS_LOG content]
PASTE
vsave valkyrie_scans_crypto insights < /tmp/ins.md

cat > /tmp/act.md << 'PASTE'
[NEXT_ACTIONS content]
PASTE
vsave valkyrie_scans_crypto actions < /tmp/act.md

# Commit to git
cd /opt/memory && git add . && git commit -m "session: $(date '+%Y-%m-%d')"
```

### Start of next session (on Phoenix):
```bash
vmem valkyrie_scans_crypto
```
Paste output as first message to Claude.

### Start of next session (in Claude):
Paste vmem output, then run health check:
```bash
systemctl is-active valkyrie-backend valkyrie-scanner valkyrie-sniper
curl -s http://127.0.0.1:8000/status | python3 -c "import json,sys; d=json.load(sys.stdin); print('Backend OK | Model:', d.get('logic_engine'), '| Scans:', d.get('total_scans'), '| Regime:', d.get('regime'))"
tail -n 5 /opt/valkyrie/logs/sniper.log
tail -n 5 /opt/valkyrie/logs/scanner.log
```

## Backup
Git history at /opt/memory/.git
To push to remote when ready:
```bash
cd /opt/memory
git remote add origin [your repo url]
git push -u origin main
```
