"""
Valkyrie Memory API
Serves and receives project memory files for Claude session continuity.
Endpoint: notes.bsapservices.com
"""

from fastapi import FastAPI, HTTPException, Header, Request
from fastapi.responses import PlainTextResponse
from fastapi.middleware.cors import CORSMiddleware
import os
from pathlib import Path
from dotenv import load_dotenv

load_dotenv()

API_KEY      = os.getenv("MEMORY_API_KEY")
MEMORY_BASE  = Path(os.getenv("MEMORY_BASE_PATH", "/opt/memory/projects"))
PORT         = int(os.getenv("PORT", 8002))

app = FastAPI(title="Valkyrie Memory API", docs_url=None, redoc_url=None)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://claude.ai"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)

VALID_DOCS = {
    "memory":   "PROJECT_MEMORY.md",
    "insights": "INSIGHTS_LOG.md",
    "actions":  "NEXT_ACTIONS.md",
}

def verify_key(x_api_key: str = Header(None), key: str = None):
    token = x_api_key or key
    if not token or token != API_KEY:
        raise HTTPException(status_code=403, detail="Invalid or missing API key")

def project_path(project: str) -> Path:
    path = MEMORY_BASE / project
    if not path.exists():
        raise HTTPException(status_code=404, detail=f"Project not found: {project}")
    return path

# --- GET all 3 docs for a project ---
@app.get("/memory/{project}", response_class=PlainTextResponse)
async def get_memory(project: str, x_api_key: str = Header(None), key: str = None):
    verify_key(x_api_key, key)
    base = project_path(project)

    output = []
    output.append(f"======================================")
    output.append(f" VALKYRIE MEMORY LOAD — {project}")
    output.append(f"======================================")

    for doctype, filename in VALID_DOCS.items():
        filepath = base / filename
        output.append(f"\n--- {filename} ---")
        if filepath.exists():
            output.append(filepath.read_text())
        else:
            output.append("[NOT FOUND — no data saved yet]")

    output.append(f"\n======================================")
    output.append(f" END MEMORY LOAD")
    output.append(f"======================================")

    return "\n".join(output)

# --- GET single doc ---
@app.get("/memory/{project}/{doctype}", response_class=PlainTextResponse)
async def get_single_doc(project: str, doctype: str, x_api_key: str = Header(None), key: str = None):
    verify_key(x_api_key, key)
    if doctype not in VALID_DOCS:
        raise HTTPException(status_code=400, detail=f"Invalid doctype. Use: {list(VALID_DOCS.keys())}")
    base = project_path(project)
    filepath = base / VALID_DOCS[doctype]
    if not filepath.exists():
        raise HTTPException(status_code=404, detail="Document not found")
    return filepath.read_text()

# --- POST update docs ---
@app.post("/memory/{project}/{doctype}")
async def save_doc(project: str, doctype: str, request: Request, x_api_key: str = Header(None)):
    verify_key(x_api_key)
    if doctype not in VALID_DOCS:
        raise HTTPException(status_code=400, detail=f"Invalid doctype. Use: {list(VALID_DOCS.keys())}")

    base = project_path(project)
    filepath = base / VALID_DOCS[doctype]
    content = await request.body()

    if not content:
        raise HTTPException(status_code=400, detail="Empty body — nothing to save")

    # Insights appends, others overwrite
    if doctype == "insights":
        with open(filepath, "a") as f:
            f.write("\n" + content.decode("utf-8"))
    else:
        filepath.write_bytes(content)

    # Git commit
    os.system(f'cd /opt/memory && git add . && git commit -q -m "api: {project}/{doctype} updated"')

    return {"status": "saved", "project": project, "doctype": doctype, "file": str(filepath)}

# --- Health check ---
@app.get("/health")
async def health():
    return {"status": "ok", "service": "valkyrie-memory-api"}

# --- List projects ---
@app.get("/projects", response_class=PlainTextResponse)
async def list_projects(x_api_key: str = Header(None)):
    verify_key(x_api_key)
    projects = [d.name for d in MEMORY_BASE.iterdir() if d.is_dir()]
    return "\n".join(projects)
