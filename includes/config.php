<?php
// includes/config.php
// Application Configuration

// Google OAuth 2.0 Credentials
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}

if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', 'http://localhost/portfolio/oauth/callback');
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
