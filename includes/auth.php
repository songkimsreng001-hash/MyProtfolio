<?php
// includes/auth.php
// Authentication & Session Management Helper

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

// Safe session initialization
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
                    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
                    (isset($_SERVER['HTTP_HOST']) && (
                        strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false ||
                        strpos($_SERVER['HTTP_HOST'], 'localhost') === false
                    ));

        // 7 days cookie lifetime for persistent session
        $cookieLifetime = 604800;
        session_set_cookie_params([
            'lifetime' => $cookieLifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.gc_maxlifetime', (string)$cookieLifetime);
    }
    @session_start();
}

initSecurityHeaders();

/**
 * Check if a user is currently logged in
 *
 * @return bool
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);
}

/**
 * Check if the currently logged in user is an administrator
 *
 * @return bool
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    $role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
    return strtolower((string)$role) === 'admin';
}

/**
 * Get data array of current authenticated user or null
 *
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $id       = (int)($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
    $name     = (string)($_SESSION['name'] ?? ($_SESSION['user']['name'] ?? ''));
    $email    = (string)($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? ''));
    $role     = (string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? 'user'));
    $avatar   = $_SESSION['avatar'] ?? ($_SESSION['user']['avatar'] ?? null);
    $googleId = $_SESSION['google_id'] ?? ($_SESSION['user']['google_id'] ?? null);

    return [
        'id'        => $id,
        'name'      => $name,
        'email'     => $email,
        'role'      => $role,
        'avatar'    => $avatar,
        'google_id' => $googleId,
    ];
}

/**
 * Enforce authentication requirement; redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $loginUrl = getAppBaseUrl() . '/login.php';
        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
        } else {
            echo '<script>window.location.href=' . json_encode($loginUrl) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }
        exit;
    }
}

/**
 * Enforce administrator requirement; redirect to home if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        $homeUrl = getAppBaseUrl() . '/index.php';
        if (!headers_sent()) {
            header('Location: ' . $homeUrl);
        } else {
            echo '<script>window.location.href=' . json_encode($homeUrl) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }
        exit;
    }
}

/**
 * Establish authenticated user session
 *
 * @param array $user
 */
function setSession($user) {
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_regenerate_id(true); // Protect against Session Fixation
    }

    $id       = (int)($user['id'] ?? 0);
    $name     = (string)($user['name'] ?? '');
    $email    = (string)($user['email'] ?? '');
    $role     = (string)($user['role'] ?? 'user');
    $avatar   = $user['avatar'] ?? null;
    $googleId = $user['google_id'] ?? null;

    // Direct session keys
    $_SESSION['user_id']   = $id;
    $_SESSION['name']      = $name;
    $_SESSION['email']     = $email;
    $_SESSION['role']      = $role;
    $_SESSION['avatar']    = $avatar;
    $_SESSION['google_id'] = $googleId;

    // Structured user array for compatibility
    $_SESSION['user'] = [
        'id'        => $id,
        'name'      => $name,
        'email'     => $email,
        'role'      => $role,
        'avatar'    => $avatar,
        'google_id' => $googleId,
    ];
}

/**
 * Safely terminate the current session and clear cookies
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    @session_destroy();
}
