<?php
/**
 * Development Server for FPP HomeKit Plugin
 * 
 * Usage: php dev-server.php
 * Then visit: http://localhost:8000
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate FPP plugin context
$plugin = 'fpp-Homekit';
$pluginDir = __DIR__;

// Provide json() helper function if not available (FPP compatibility)
if (!function_exists('json')) {
    function json($data) {
        header('Content-Type: application/json');
        return json_encode($data);
    }
}

// Simple router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);
$query = parse_url($requestUri, PHP_URL_QUERY);

// Handle API endpoints
if (strpos($path, '/api/plugin/' . $plugin) === 0) {
    $apiPath = str_replace('/api/plugin/' . $plugin . '/', '', $path);
    
    // Include API file
    require_once __DIR__ . '/api.php';
    
    // Route to appropriate API function
    switch ($apiPath) {
        case 'status':
            header('Content-Type: application/json');
            echo fppHomekitStatus();
            exit;
            
        case 'qr-code':
            fppHomekitQRCode();
            exit;
            
        case 'pairing-info':
            header('Content-Type: application/json');
            echo fppHomekitPairingInfo();
            exit;
            
        case 'playlists':
            header('Content-Type: application/json');
            echo fppHomekitPlaylists();
            exit;
            
        case 'config':
            if ($requestMethod === 'POST') {
                header('Content-Type: application/json');
                echo fppHomekitSaveConfig();
            } else {
                header('Content-Type: application/json');
                echo fppHomekitGetConfig();
            }
            exit;
            
        case 'restart':
            if ($requestMethod === 'POST') {
                header('Content-Type: application/json');
                echo fppHomekitRestart();
            }
            exit;
            
        case 'log':
            if ($requestMethod === 'GET') {
                header('Content-Type: application/json');
                echo fppHomekitLog();
            }
            exit;
    }
}

// Handle page requests
$page = isset($_GET['page']) ? $_GET['page'] : 'status.php';

// Map page names to files
$pageMap = [
    'status.php' => 'status.php',
    'content.php' => 'content.php',
    'about.php' => 'about.php',
    'help.php' => 'help/help.php',
];

if (isset($pageMap[$page])) {
    $pageFile = $pageMap[$page];
    
    // Set up plugin context
    global $plugin, $settings;
    $settings = ['pluginDirectory' => $pluginDir];
    
    // Output page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FPP HomeKit - <?php echo htmlspecialchars($page); ?></title>
    </head>
    <body style="margin: 0; padding: 0; background: var(--bg-secondary, #f5f5f7);">
        <?php
        require_once __DIR__ . '/' . $pageFile;
        ?>
    </body>
    </html>
    <?php
} else {
    // Default to status page
    header('Location: ?page=status.php');
    exit;
}

