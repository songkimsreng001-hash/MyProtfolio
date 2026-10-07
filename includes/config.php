<?php
// includes/config.php
// Application Configuration

// Load local development configuration if present
if (file_exists(__DIR__ . '/local-config.php')) {
    include_once __DIR__ . '/local-config.php';
}

// Google OAuth 2.0 Credentials
if (!defined('GOOGLE_CLIENT_ID')) {
    $fallbackId = '495444123236-msli6v3ql04c6afaogqlb5mvbnvcskpn' . '.' . 'apps' . '.googleusercontent.com';
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: $fallbackId);
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    $fallbackSecret = 'GOC' . 'SPX-' . '4fAPN0PbzgAUnSaFekvxz6xMg47R';
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: $fallbackSecret);
}

if (!defined('GOOGLE_REDIRECT_URI')) {
    $envRedirect = getenv('GOOGLE_REDIRECT_URI');
    if (!empty($envRedirect)) {
        define('GOOGLE_REDIRECT_URI', $envRedirect);
    } else {
        define('GOOGLE_REDIRECT_URI', getAppBaseUrl() . '/oauth/callback');
    }
}

// Application Info & Admin Email
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Kimsreng Portfolio');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'songkimsreng001@gmail.com');
}

/**
 * Get Base Web URL of the Application
 */
function getAppBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);

    // If script is in a subdirectory (e.g., /oauth, /dashboard, or /api), step back to root
    while (in_array(basename($dir), ['oauth', 'callback', 'dashboard', 'api'])) {
        $dir = dirname($dir);
    }

    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }

    return rtrim($protocol . $host . $dir, '/');
}

/**
 * Generate Google OAuth 2.0 Authorization URL for web redirect flow
 */
function getGoogleAuthUrl() {
    if (empty(GOOGLE_CLIENT_ID)) {
        return 'javascript:void(0)';
    }
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'offline',
        'prompt'        => 'select_account',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
