# API Reference

REST API documentation for Valkyrie Memory System v4.1.

## Base URL

```
http://your-domain.com/valkyrie/api.php
```

## Authentication

Currently no authentication required (v4.1). Recommended for internal/private networks only.

Future versions will include API keys and JWT tokens.

## Endpoints

### List Projects

Get all available projects.

```
GET /api.php?action=list_projects
```

**Response:**
```json
{
  "success": true,
  "projects": [
    {
      "id": "global",
      "name": "Global",
      "path": "/opt/valkyrie/memory/global",
      "updated": 1699200000
    },
    {
      "id": "ebay_autods_bot",
      "name": "Ebay Autods Bot",
      "path": "/opt/valkyrie/memory/projects/ebay_autods_bot",
      "updated": 1699199000
    }
  ],
  "count": 2
}
```

---

### Get Memory

Retrieve memory for selected project(s).

```
POST /api.php?action=get_memory
Content-Type: application/json

{
  "projects": ["global", "ebay_autods_bot"]
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| projects | array | Yes | Array of project IDs to load |

**Response:**
```json
{
  "success": true,
  "memory": "# VALKYRIE MEMORY CONTEXT\n...",
  "projects": ["global", "ebay_autods_bot"],
  "timestamp": 1699200000
}
```

**Example:**
```bash
curl -X POST http://aimem.yourdomain.com/api.php?action=get_memory \
  -H "Content-Type: application/json" \
  -d '{"projects":["ebay_autods_bot"]}'
```

---

### Append to Memory

Add text to a project's memory file.

```
POST /api.php?action=append
Content-Type: application/json

{
  "project": "ebay_autods_bot",
  "text": "Important note here",
  "file": "PROJECT_MEMORY.md"
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| project | string | Yes | Project ID |
| text | string | Yes | Content to append |
| file | string | No | Target file (default: PROJECT_MEMORY.md) |

**File Options:**
- `PROJECT_MEMORY.md` (default)
- `INSIGHTS_LOG.md`
- `NEXT_ACTIONS.md`

**Response:**
```json
{
  "success": true,
  "project": "ebay_autods_bot",
  "file": "PROJECT_MEMORY.md",
  "timestamp": "2025-11-07 14:30:00"
}
```

**Example:**
```bash
curl -X POST http://aimem.yourdomain.com/api.php?action=append \
  -H "Content-Type: application/json" \
  -d '{"project":"ebay_autods_bot","text":"Updated pricing strategy"}'
```

---

### Create Project

Create a new project with memory files.

```
POST /api.php?action=create_project
Content-Type: application/json

{
  "project": "new_project_name"
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| project | string | Yes | New project ID (alphanumeric, underscore, dash) |

**Response:**
```json
{
  "success": true,
  "project": "new_project_name",
  "path": "/opt/valkyrie/memory/projects/new_project_name"
}
```

**Example:**
```bash
curl -X POST http://aimem.yourdomain.com/api.php?action=create_project \
  -H "Content-Type: application/json" \
  -d '{"project":"my_new_project"}'
```

---

### System Stats

Get system statistics.

```
GET /api.php?action=stats
```

**Response:**
```json
{
  "success": true,
  "totalMemory": "47.23 KB",
  "lastUpdated": "2h ago",
  "timestamp": 1699200000
}
```

**Example:**
```bash
curl http://aimem.yourdomain.com/api.php?action=stats
```

---

### Upload Session Export

Upload AI session export file.

```
POST /api.php?action=upload
Content-Type: multipart/form-data

file: [file data]
project: "auto" or "project_id"
```

**Form Data:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| file | file | Yes | Session export JSON file |
| project | string | No | Project ID or "auto" for auto-detection |

**Response:**
```json
{
  "success": true,
  "project": "ebay_autods_bot",
  "filename": "ebay_autods_bot_20251107143000.json",
  "path": "/opt/valkyrie/memory/_incoming/ebay_autods_bot_20251107143000.json"
}
```

**Example:**
```bash
curl -X POST http://aimem.yourdomain.com/api.php?action=upload \
  -F "file=@session_export.json" \
  -F "project=auto"
```

---

## Error Handling

### Error Response Format

```json
{
  "error": "Error message here",
  "code": 400
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 404 | Not Found - Project doesn't exist |
| 409 | Conflict - Project already exists |
| 500 | Server Error |

---

## Rate Limiting

API requests are rate-limited to prevent abuse:
- **Limit**: 10 requests per second
- **Burst**: 20 requests
- **Scope**: Per IP address

**Rate Limit Headers:**
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 8
X-RateLimit-Reset: 1699200060
```

---

## Examples

### JavaScript (Fetch)

```javascript
// Get memory for multiple projects
async function loadMemory(projects) {
  const response = await fetch('/api.php?action=get_memory', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ projects })
  });
  
  const data = await response.json();
  
  if (data.success) {
    return data.memory;
  } else {
    throw new Error(data.error);
  }
}

// Usage
const memory = await loadMemory(['global', 'ebay_autods_bot']);
console.log(memory);
```

### Python (Requests)

```python
import requests

# Get memory
url = 'http://aimem.yourdomain.com/api.php?action=get_memory'
payload = {
    'projects': ['global', 'ebay_autods_bot']
}

response = requests.post(url, json=payload)
data = response.json()

if data['success']:
    memory = data['memory']
    print(memory)
else:
    print(f"Error: {data['error']}")
```

### PHP

```php
<?php
// Append to project memory
$url = 'http://aimem.yourdomain.com/api.php?action=append';
$data = [
    'project' => 'ebay_autods_bot',
    'text' => 'Important update',
    'file' => 'PROJECT_MEMORY.md'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);

if ($response['success']) {
    echo "Appended successfully\n";
} else {
    echo "Error: {$response['error']}\n";
}
?>
```

### Bash (cURL)

```bash
#!/bin/bash

# Get memory for project
get_memory() {
    local project="$1"
    curl -s -X POST http://aimem.yourdomain.com/api.php?action=get_memory \
      -H "Content-Type: application/json" \
      -d "{\"projects\":[\"$project\"]}" \
      | jq -r '.memory'
}

# Append note
append_note() {
    local project="$1"
    local text="$2"
    curl -s -X POST http://aimem.yourdomain.com/api.php?action=append \
      -H "Content-Type: application/json" \
      -d "{\"project\":\"$project\",\"text\":\"$text\"}"
}

# Usage
get_memory "ebay_autods_bot" > memory.txt
append_note "ebay_autods_bot" "Updated pricing strategy"
```

---

## Webhooks (Future)

Planned for v5.0:
- POST to webhook URL on memory updates
- Real-time sync notifications
- Custom integration triggers

---

## SDK Libraries (Future)

Planned official SDKs:
- Python SDK
- JavaScript/TypeScript SDK
- PHP SDK
- Go SDK

---

## API Versioning

Current version: **v4.1**

API version is included in responses:
```json
{
  "api_version": "4.1.0",
  "success": true,
  ...
}
```

Breaking changes will increment major version (v5.0, v6.0, etc.)

---

## Support

- **Issues**: https://github.com/yourusername/valkyrie-memory-system/issues
- **API Questions**: Tag with `api` label
- **Feature Requests**: Tag with `api` and `enhancement`

---

**API Reference v4.1.0**  
Last Updated: 2025-11-07
