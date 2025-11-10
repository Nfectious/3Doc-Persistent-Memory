<?php
/**
 * Valkyrie Memory System API v4.2
 * Added: Paste export processing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$MEMORY_BASE = '/opt/valkyrie/memory';
$PROJECTS_DIR = "$MEMORY_BASE/projects";
$GLOBAL_DIR = "$MEMORY_BASE/global";

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action
$action = $_GET['action'] ?? 'help';

// Route actions
switch ($action) {
    case 'get_memory':
        getMemory();
        break;
    case 'append':
        appendToMemory();
        break;
    case 'process_export':
        processExport();
        break;
    case 'create_project':
        createProject();
        break;
    case 'list_projects':
        listProjects();
        break;
    case 'stats':
        getStats();
        break;
    case 'upload':
        handleUpload();
        break;
    default:
        sendResponse(['error' => 'Unknown action'], 400);
}

/**
 * Process pasted export text
 */
function processExport() {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $exportText = $input['export_text'] ?? '';
    $targetProject = $input['project'] ?? 'auto';
    
    if (empty($exportText)) {
        sendResponse(['error' => 'Export text required'], 400);
        return;
    }
    
    // Parse export
    $parsed = parseValkyrieExport($exportText);
    
    if (!$parsed) {
        sendResponse(['error' => 'Invalid export format'], 400);
        return;
    }
    
    // Determine project
    if ($targetProject === 'auto') {
        $targetProject = detectProject($exportText);
    }
    
    $projectDir = ($targetProject === 'global') ? $GLOBAL_DIR : "$PROJECTS_DIR/$targetProject";
    
    if (!is_dir($projectDir)) {
        sendResponse(['error' => 'Project not found: ' . $targetProject], 404);
        return;
    }
    
    // Append to PROJECT_MEMORY.md
    $timestamp = date('Y-m-d H:i:s');
    $entry = "\n\n---\n";
    $entry .= "## Session Export - $timestamp\n\n";
    $entry .= "**Date**: {$parsed['date']}\n";
    $entry .= "**Time**: {$parsed['time']}\n";
    $entry .= "**Session**: {$parsed['session']}\n\n";
    
    if (!empty($parsed['decisions'])) {
        $entry .= "### Decisions\n";
        foreach ($parsed['decisions'] as $decision) {
            $entry .= "- $decision\n";
        }
        $entry .= "\n";
    }
    
    if (!empty($parsed['status'])) {
        $entry .= "### Status\n";
        $entry .= $parsed['status'] . "\n\n";
    }
    
    file_put_contents("$projectDir/PROJECT_MEMORY.md", $entry, FILE_APPEND | LOCK_EX);
    
    // Append insights to INSIGHTS_LOG.md
    if (!empty($parsed['insights'])) {
        $insightsEntry = "\n\n---\n";
        $insightsEntry .= "## $timestamp\n\n";
        foreach ($parsed['insights'] as $insight) {
            $insightsEntry .= "- $insight\n";
        }
        file_put_contents("$projectDir/INSIGHTS_LOG.md", $insightsEntry, FILE_APPEND | LOCK_EX);
    }
    
    // Append actions to NEXT_ACTIONS.md
    if (!empty($parsed['actions'])) {
        $actionsEntry = "\n\n---\n";
        $actionsEntry .= "## Updated: $timestamp\n\n";
        foreach ($parsed['actions'] as $action) {
            $actionsEntry .= "- $action\n";
        }
        file_put_contents("$projectDir/NEXT_ACTIONS.md", $actionsEntry, FILE_APPEND | LOCK_EX);
    }
    
    sendResponse([
        'success' => true,
        'project' => $targetProject,
        'timestamp' => $timestamp,
        'sections_processed' => [
            'decisions' => count($parsed['decisions'] ?? []),
            'insights' => count($parsed['insights'] ?? []),
            'actions' => count($parsed['actions'] ?? [])
        ]
    ]);
}

/**
 * Parse Valkyrie export format
 */
function parseValkyrieExport($text) {
    if (!preg_match('/=== VALKYRIE SESSION EXPORT ===/i', $text)) {
        return false;
    }
    
    $result = [
        'date' => '',
        'time' => '',
        'session' => '',
        'decisions' => [],
        'scripts' => [],
        'status' => '',
        'insights' => [],
        'actions' => []
    ];
    
    // Extract date
    if (preg_match('/DATE:\s*(.+)/i', $text, $matches)) {
        $result['date'] = trim($matches[1]);
    }
    
    // Extract time
    if (preg_match('/TIME:\s*(.+)/i', $text, $matches)) {
        $result['time'] = trim($matches[1]);
    }
    
    // Extract session
    if (preg_match('/SESSION:\s*(.+)/i', $text, $matches)) {
        $result['session'] = trim($matches[1]);
    }
    
    // Extract sections
    $result['decisions'] = extractSection($text, 'DECISIONS');
    $result['scripts'] = extractSection($text, 'SCRIPTS');
    $result['insights'] = extractSection($text, 'INSIGHTS');
    $result['actions'] = extractSection($text, 'ACTIONS');
    
    // Extract status (multi-line)
    if (preg_match('/\[STATUS\](.*?)\[(?:INSIGHTS|ACTIONS)/s', $text, $matches)) {
        $result['status'] = trim($matches[1]);
    }
    
    return $result;
}

/**
 * Extract section items
 */
function extractSection($text, $sectionName) {
    $pattern = '/\[' . $sectionName . '\](.*?)\[(?:\w+|\s*=)/s';
    if (!preg_match($pattern, $text, $matches)) {
        return [];
    }
    
    $content = trim($matches[1]);
    $lines = explode("\n", $content);
    $items = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and checkboxes
        $line = preg_replace('/^[-\*]\s*\[.\]\s*/', '', $line);
        $line = preg_replace('/^[-\*]\s*/', '', $line);
        
        if (!empty($line) && $line !== '') {
            $items[] = $line;
        }
    }
    
    return $items;
}

/**
 * Detect project from export content
 */
function detectProject($text) {
    $text = strtolower($text);
    
    $patterns = [
        'ebay_autods_bot' => ['ebay', 'autods', 'dropship'],
        'phoenix_commandops' => ['phoenix', 'commandops', 'dashboard'],
        'madison_mission' => ['madison', 'daughter'],
        'overthrow' => ['overthrow', 'accountability'],
        'truthvault' => ['truthvault', 'truth vault', 'documentation']
    ];
    
    foreach ($patterns as $project => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return $project;
            }
        }
    }
    
    return 'global';
}

/**
 * Get memory for selected projects
 */
function getMemory() {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $projects = $input['projects'] ?? [];
    
    if (empty($projects)) {
        sendResponse(['error' => 'No projects selected'], 400);
        return;
    }
    
    $memory = [];
    $memory[] = "# VALKYRIE MEMORY CONTEXT";
    $memory[] = "# Loaded at: " . date('Y-m-d H:i:s');
    $memory[] = "# Projects: " . implode(', ', $projects);
    $memory[] = "";
    $memory[] = "---";
    $memory[] = "";
    
    foreach ($projects as $project) {
        $projectDir = ($project === 'global') ? $GLOBAL_DIR : "$PROJECTS_DIR/$project";
        
        if (!is_dir($projectDir)) {
            $memory[] = "## ⚠️ PROJECT: $project (NOT FOUND)";
            $memory[] = "";
            continue;
        }
        
        $memory[] = "## 📁 PROJECT: $project";
        $memory[] = "";
        
        $files = [
            'PROJECT_MEMORY.md' => '📋 Project Memory',
            'INSIGHTS_LOG.md' => '💡 Insights Log',
            'NEXT_ACTIONS.md' => '✅ Next Actions'
        ];
        
        foreach ($files as $filename => $title) {
            $filepath = "$projectDir/$filename";
            
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                $memory[] = "### $title";
                $memory[] = "";
                $memory[] = trim($content);
                $memory[] = "";
            } else {
                $memory[] = "### $title";
                $memory[] = "";
                $memory[] = "_No data yet_";
                $memory[] = "";
            }
        }
        
        $memory[] = "---";
        $memory[] = "";
    }
    
    $memory[] = "# END OF MEMORY CONTEXT";
    
    $memoryText = implode("\n", $memory);
    
    sendResponse([
        'success' => true,
        'memory' => $memoryText,
        'projects' => $projects,
        'timestamp' => time()
    ]);
}

/**
 * Append text to memory
 */
function appendToMemory() {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $project = $input['project'] ?? '';
    $text = $input['text'] ?? '';
    $file = $input['file'] ?? 'PROJECT_MEMORY.md';
    
    if (empty($project) || empty($text)) {
        sendResponse(['error' => 'Project and text required'], 400);
        return;
    }
    
    $projectDir = ($project === 'global') ? $GLOBAL_DIR : "$PROJECTS_DIR/$project";
    
    if (!is_dir($projectDir)) {
        sendResponse(['error' => 'Project not found'], 404);
        return;
    }
    
    $filepath = "$projectDir/$file";
    $timestamp = date('Y-m-d H:i:s');
    $entry = "\n\n---\n[Appended: $timestamp]\n\n$text\n";
    
    if (file_put_contents($filepath, $entry, FILE_APPEND | LOCK_EX) === false) {
        sendResponse(['error' => 'Failed to write file'], 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'project' => $project,
        'file' => $file,
        'timestamp' => $timestamp
    ]);
}

/**
 * Create project
 */
function createProject() {
    global $PROJECTS_DIR;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $project = $input['project'] ?? '';
    
    if (empty($project)) {
        sendResponse(['error' => 'Project name required'], 400);
        return;
    }
    
    $project = preg_replace('/[^a-z0-9_-]/i', '_', $project);
    $projectDir = "$PROJECTS_DIR/$project";
    
    if (is_dir($projectDir)) {
        sendResponse(['error' => 'Project already exists'], 409);
        return;
    }
    
    if (!mkdir($projectDir, 0755, true)) {
        sendResponse(['error' => 'Failed to create project'], 500);
        return;
    }
    
    $files = [
        'PROJECT_MEMORY.md' => "# PROJECT: $project\n\nCreated: " . date('Y-m-d H:i:s') . "\n\n",
        'INSIGHTS_LOG.md' => "# INSIGHTS LOG: $project\n\n",
        'NEXT_ACTIONS.md' => "# NEXT ACTIONS: $project\n\n"
    ];
    
    foreach ($files as $filename => $content) {
        file_put_contents("$projectDir/$filename", $content);
    }
    
    sendResponse([
        'success' => true,
        'project' => $project,
        'path' => $projectDir
    ]);
}

/**
 * List projects
 */
function listProjects() {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $projects = [];
    
    if (is_dir($GLOBAL_DIR)) {
        $projects[] = [
            'id' => 'global',
            'name' => 'Global',
            'path' => $GLOBAL_DIR,
            'updated' => filemtime($GLOBAL_DIR)
        ];
    }
    
    if (is_dir($PROJECTS_DIR)) {
        $dirs = scandir($PROJECTS_DIR);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $path = "$PROJECTS_DIR/$dir";
            if (is_dir($path)) {
                $projects[] = [
                    'id' => $dir,
                    'name' => ucwords(str_replace('_', ' ', $dir)),
                    'path' => $path,
                    'updated' => filemtime($path)
                ];
            }
        }
    }
    
    sendResponse([
        'success' => true,
        'projects' => $projects,
        'count' => count($projects)
    ]);
}

/**
 * Get stats
 */
function getStats() {
    global $MEMORY_BASE;
    
    $totalSize = 0;
    $lastUpdated = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($MEMORY_BASE, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $mtime = $file->getMTime();
            if ($mtime > $lastUpdated) {
                $lastUpdated = $mtime;
            }
        }
    }
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($totalSize > 1024 && $unitIndex < count($units) - 1) {
        $totalSize /= 1024;
        $unitIndex++;
    }
    
    $totalMemory = round($totalSize, 2) . ' ' . $units[$unitIndex];
    
    $diff = time() - $lastUpdated;
    if ($diff < 3600) {
        $lastUpdatedStr = floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        $lastUpdatedStr = floor($diff / 3600) . 'h ago';
    } else {
        $lastUpdatedStr = floor($diff / 86400) . 'd ago';
    }
    
    sendResponse([
        'success' => true,
        'totalMemory' => $totalMemory,
        'lastUpdated' => $lastUpdatedStr,
        'timestamp' => time()
    ]);
}

/**
 * Handle file upload
 */
function handleUpload() {
    global $PROJECTS_DIR, $MEMORY_BASE;
    
    if (!isset($_FILES['file'])) {
        sendResponse(['error' => 'No file uploaded'], 400);
        return;
    }
    
    $file = $_FILES['file'];
    $project = $_POST['project'] ?? 'auto';
    
    $content = file_get_contents($file['tmp_name']);
    
    // Try to detect project
    if ($project === 'auto') {
        $project = detectProject($content);
    }
    
    // Save to _incoming
    $incomingDir = "$MEMORY_BASE/_incoming";
    if (!is_dir($incomingDir)) {
        mkdir($incomingDir, 0755, true);
    }
    
    $timestamp = date('YmdHis');
    $filename = "{$project}_{$timestamp}.txt";
    $destPath = "$incomingDir/$filename";
    
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        sendResponse(['error' => 'Failed to save file'], 500);
        return;
    }
    
    // Try to process if it's a Valkyrie export
    $parsed = parseValkyrieExport($content);
    if ($parsed) {
        // Process it automatically
        processExportContent($content, $project);
    }
    
    sendResponse([
        'success' => true,
        'project' => $project,
        'filename' => $filename,
        'path' => $destPath
    ]);
}

/**
 * Helper to process export content
 */
function processExportContent($content, $project) {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $parsed = parseValkyrieExport($content);
    if (!$parsed) return;
    
    $projectDir = ($project === 'global') ? $GLOBAL_DIR : "$PROJECTS_DIR/$project";
    if (!is_dir($projectDir)) return;
    
    $timestamp = date('Y-m-d H:i:s');
    
    // Append to files
    $entry = "\n\n---\n## Session Export - $timestamp\n\n";
    $entry .= "**Session**: {$parsed['session']}\n\n";
    
    if (!empty($parsed['decisions'])) {
        $entry .= "### Decisions\n";
        foreach ($parsed['decisions'] as $decision) {
            $entry .= "- $decision\n";
        }
    }
    
    file_put_contents("$projectDir/PROJECT_MEMORY.md", $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Send response
 */
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
