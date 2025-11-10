<?php
/**
 * Valkyrie Memory System API v4.1
 * Multi-Project Memory Management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$MEMORY_BASE = '/opt/valkyrie/memory';
$PROJECTS_DIR = "$MEMORY_BASE/projects";
$GLOBAL_DIR = "$MEMORY_BASE/global";

// Handle OPTIONS request
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
        
        // Read the 3 memory files
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
 * Append text to a project's memory
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
    
    // Append with timestamp
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
 * Create a new project
 */
function createProject() {
    global $PROJECTS_DIR;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $project = $input['project'] ?? '';
    
    if (empty($project)) {
        sendResponse(['error' => 'Project name required'], 400);
        return;
    }
    
    // Sanitize project name
    $project = preg_replace('/[^a-z0-9_-]/i', '_', $project);
    
    $projectDir = "$PROJECTS_DIR/$project";
    
    if (is_dir($projectDir)) {
        sendResponse(['error' => 'Project already exists'], 409);
        return;
    }
    
    // Create directory
    if (!mkdir($projectDir, 0755, true)) {
        sendResponse(['error' => 'Failed to create project directory'], 500);
        return;
    }
    
    // Create initial files
    $files = [
        'PROJECT_MEMORY.md' => "# PROJECT: $project\n\nCreated: " . date('Y-m-d H:i:s') . "\n\n## Overview\n\n",
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
 * List all projects
 */
function listProjects() {
    global $PROJECTS_DIR, $GLOBAL_DIR;
    
    $projects = [];
    
    // Add global
    if (is_dir($GLOBAL_DIR)) {
        $projects[] = [
            'id' => 'global',
            'name' => 'Global',
            'path' => $GLOBAL_DIR,
            'updated' => filemtime($GLOBAL_DIR)
        ];
    }
    
    // Add project folders
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
 * Get system stats
 */
function getStats() {
    global $MEMORY_BASE;
    
    $totalSize = 0;
    $lastUpdated = 0;
    
    // Calculate total size recursively
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($MEMORY_BASE, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
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
    
    // Format size
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($totalSize > 1024 && $unitIndex < count($units) - 1) {
        $totalSize /= 1024;
        $unitIndex++;
    }
    
    $totalMemory = round($totalSize, 2) . ' ' . $units[$unitIndex];
    
    // Format last updated
    $now = time();
    $diff = $now - $lastUpdated;
    
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
        'timestamp' => $now
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
    
    // Read file content
    $content = file_get_contents($file['tmp_name']);
    
    // Try to determine project from content (if auto)
    if ($project === 'auto') {
        // Simple heuristic: look for project keywords in content
        if (stripos($content, 'ebay') !== false || stripos($content, 'autods') !== false) {
            $project = 'ebay_autods_bot';
        } elseif (stripos($content, 'phoenix') !== false) {
            $project = 'phoenix_commandops';
        } elseif (stripos($content, 'madison') !== false) {
            $project = 'madison_mission';
        } else {
            $project = 'global';
        }
    }
    
    // Save to _incoming for processing
    $incomingDir = "$MEMORY_BASE/_incoming";
    if (!is_dir($incomingDir)) {
        mkdir($incomingDir, 0755, true);
    }
    
    $timestamp = date('YmdHis');
    $filename = "{$project}_{$timestamp}.json";
    $destPath = "$incomingDir/$filename";
    
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        sendResponse(['error' => 'Failed to save file'], 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'project' => $project,
        'filename' => $filename,
        'path' => $destPath
    ]);
}

/**
 * Send JSON response
 */
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
