#!/bin/bash
# ============================================================
# Valkyrie Memory System — Setup Script
# Run once on Phoenix as root
# Creates /opt/memory structure, vsave, vmem, and README
# ============================================================

set -e

BASE="/opt/memory/projects"
PROJECT="valkyrie_scans_crypto"

echo "Setting up Valkyrie Memory System..."

# --- Directory Structure ---
mkdir -p "$BASE/$PROJECT"
mkdir -p "/opt/memory/bin"

echo "✅ Directories created"

# --- vsave script ---
cat > /usr/local/bin/vsave << 'EOF'
#!/bin/bash
# vsave <project> <doctype>
# Writes stdin to the correct memory file
# doctype: memory | insights | actions

BASE="/opt/memory/projects"
PROJECT="$1"
TYPE="$2"

if [[ -z "$PROJECT" || -z "$TYPE" ]]; then
  echo "Usage: vsave <project> <memory|insights|actions>"
  exit 1
fi

if [[ ! -d "$BASE/$PROJECT" ]]; then
  echo "❌ Project not found: $BASE/$PROJECT"
  echo "   Create it with: mkdir -p $BASE/$PROJECT"
  exit 1
fi

case "$TYPE" in
  memory)
    FILE="$BASE/$PROJECT/PROJECT_MEMORY.md"
    if [ ! -t 0 ]; then
      cat > "$FILE"
      echo "✅ PROJECT_MEMORY.md saved → $FILE"
    else
      nano "$FILE"
    fi
    ;;
  insights)
    FILE="$BASE/$PROJECT/INSIGHTS_LOG.md"
    if [ ! -t 0 ]; then
      echo "" >> "$FILE"
      cat >> "$FILE"
      echo "✅ INSIGHTS_LOG.md appended → $FILE"
    else
      nano "$FILE"
    fi
    ;;
  actions)
    FILE="$BASE/$PROJECT/NEXT_ACTIONS.md"
    if [ ! -t 0 ]; then
      cat > "$FILE"
      echo "✅ NEXT_ACTIONS.md saved → $FILE"
    else
      nano "$FILE"
    fi
    ;;
  *)
    echo "❌ Type must be: memory | insights | actions"
    exit 1
    ;;
esac
EOF
chmod +x /usr/local/bin/vsave

echo "✅ vsave installed"

# --- vmem script ---
cat > /usr/local/bin/vmem << 'EOF'
#!/bin/bash
# vmem <project>
# Prints all 3 memory files for a project
# Paste output as first message to Claude to restore context

BASE="/opt/memory/projects"
PROJECT="$1"

if [[ -z "$PROJECT" ]]; then
  echo "Usage: vmem <project>"
  echo ""
  echo "Available projects:"
  ls "$BASE"
  exit 1
fi

if [[ ! -d "$BASE/$PROJECT" ]]; then
  echo "❌ Project not found: $PROJECT"
  echo "Available projects:"
  ls "$BASE"
  exit 1
fi

echo "========================================"
echo " VALKYRIE MEMORY LOAD — $PROJECT"
echo " $(date '+%Y-%m-%d %H:%M')"
echo "========================================"

for DOC in PROJECT_MEMORY.md INSIGHTS_LOG.md NEXT_ACTIONS.md; do
  FILE="$BASE/$PROJECT/$DOC"
  echo ""
  echo "--- $DOC ---"
  if [[ -f "$FILE" ]]; then
    cat "$FILE"
  else
    echo "[NOT FOUND — run vsave to create]"
  fi
done

echo ""
echo "========================================"
echo " END MEMORY LOAD"
echo "========================================"
EOF
chmod +x /usr/local/bin/vmem

echo "✅ vmem installed"

# --- vprojects script --- lists all projects ---
cat > /usr/local/bin/vprojects << 'EOF'
#!/bin/bash
# vprojects — list all memory projects and their last update
BASE="/opt/memory/projects"
echo "========================================"
echo " Valkyrie Memory Projects"
echo "========================================"
for dir in "$BASE"/*/; do
  PROJECT=$(basename "$dir")
  MEMORY="$dir/PROJECT_MEMORY.md"
  if [[ -f "$MEMORY" ]]; then
    MODIFIED=$(date -r "$MEMORY" '+%Y-%m-%d %H:%M')
    STATUS=$(grep "^\*\*Status:\*\*" "$MEMORY" | head -1 | sed 's/\*\*Status:\*\* //')
    echo "  $PROJECT"
    echo "    Last updated: $MODIFIED"
    echo "    Status: $STATUS"
  else
    echo "  $PROJECT — [no PROJECT_MEMORY.md]"
  fi
done
echo "========================================"
EOF
chmod +x /usr/local/bin/vprojects

echo "✅ vprojects installed"

# --- Git init ---
cd /opt/memory
git init -q
cat > .gitignore << 'EOF'
*.swp
*.tmp
.DS_Store
EOF
git add .
git commit -q -m "init: Valkyrie memory system"

echo "✅ Git initialized at /opt/memory"

# --- README ---
cat > /opt/memory/README.md << 'EOF'
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
EOF

git add .
git commit -q -m "docs: README added"

echo "✅ README written"
echo ""
echo "========================================"
echo " Setup Complete"
echo "========================================"
echo ""
echo "Commands available:"
echo "  vmem <project>                    — load context"
echo "  vsave <project> <memory|insights|actions>  — save docs"
echo "  vprojects                         — list all projects"
echo ""
echo "Project directory:"
echo "  /opt/memory/projects/valkyrie_scans_crypto/"
echo ""
echo "Next step:"
echo "  Paste the 3 memory docs generated this session using vsave"
echo "  Then run: vmem valkyrie_scans_crypto to verify"
echo "========================================"
