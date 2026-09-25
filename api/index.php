<?php
// api/index.php - Serverless Router for Vercel

$projectRoot = dirname(__DIR__);
chdir($projectRoot);
set_include_path(get_include_path() . PATH_SEPARATOR . $projectRoot);

// Parse the request URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$urlPath = parse_url($requestUri, PHP_URL_PATH);
$path = trim($urlPath, '/');

// Root request -> index.php
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF']    = '/index.php';
    require $projectRoot . '/index.php';
    exit;
}

// Find matching target file
$targetFile = null;

if (is_file($projectRoot . '/' . $path)) {
    $targetFile = $projectRoot . '/' . $path;
} elseif (is_file($projectRoot . '/' . $path . '.php')) {
    $targetFile = $projectRoot . '/' . $path . '.php';
} elseif (is_file($projectRoot . '/' . $path . '/index.php')) {
    $targetFile = $projectRoot . '/' . $path . '/index.php';
}

if ($targetFile) {
    $realTarget = realpath($targetFile);
    $realRoot   = realpath($projectRoot);

    // Security check: ensure target is strictly inside project root and not sensitive
    if ($realTarget && strpos($realTarget, $realRoot) === 0) {
        $relPath = str_replace('\\', '/', substr($realTarget, strlen($realRoot) + 1));

        // Prevent direct web access to sensitive directories and config files
        if (preg_match('#^(includes|sql|\.git|\.env|composer)#i', $relPath)) {
            http_response_code(403);
            echo 'Access Denied';
            exit;
        }

        // If it's a PHP file, execute it
        if (pathinfo($realTarget, PATHINFO_EXTENSION) === 'php') {
            $_SERVER['SCRIPT_NAME'] = '/' . $relPath;
            $_SERVER['PHP_SELF']    = '/' . $relPath;
            require $realTarget;
            exit;
        }
    }
}

// Fallback to index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';
require $projectRoot . '/index.php';
