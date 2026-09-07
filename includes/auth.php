<?php
// includes/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

initSecurityHeaders();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'name'      => $_SESSION['name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'user',
        'avatar'    => $_SESSION['avatar'] ?? null,
        'google_id' => $_SESSION['google_id'] ?? null,
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        $loginUrl = getAppBaseUrl() . '/login.php';
        header('Location: ' . $loginUrl);
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        $homeUrl = getAppBaseUrl() . '/index.php';
        header('Location: ' . $homeUrl);
        exit;
    }
}

function setSession($user) {
    session_regenerate_id(true); // Protect against Session Fixation
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['name']      = $user['name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'] ?? 'user';
    $_SESSION['avatar']    = $user['avatar'] ?? null;
    $_SESSION['google_id'] = $user['google_id'] ?? null;
}
