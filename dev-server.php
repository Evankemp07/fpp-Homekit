<?php
// Simple router for local development
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove leading slash
$requestPath = ltrim($requestPath, '/');

// If no path specified or root, show status.php
if (empty($requestPath) || $requestPath === '/') {
    $requestPath = 'status.php';
}

// Check if file exists
$filePath = __DIR__ . '/' . $requestPath;
if (file_exists($filePath) && is_file($filePath)) {
    // For PHP files, include them
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        require $filePath;
    } else {
        // For other files, serve them directly
        readfile($filePath);
    }
} else {
    http_response_code(404);
    echo "404 - File not found: " . htmlspecialchars($requestPath);
}
