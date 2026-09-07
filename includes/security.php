<?php
// includes/security.php
// Centralized Security Helper for Antigravity Portfolio

/**
 * Emit essential HTTP security headers
 */
function initSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}

/**
 * Retrieve or generate the session CSRF token
 */
function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output an HTML hidden input containing the CSRF token
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from request
 * 
 * @param string|null $token Provided token (if null, checks $_POST['csrf_token'] or HTTP header)
 * @return bool
 */
function verifyCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }

    return hash_equals($sessionToken, (string)$token);
}

/**
 * Sanitize header string to prevent CRLF Email Header Injection
 * 
 * @param string $str
 * @return string
 */
function sanitizeHeaderString($str) {
    return trim(str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], '', (string)$str));
}

/**
 * Check and enforce rate limiting based on key (IP + Action)
 * 
 * @param string $action Action key (e.g. 'login', 'contact')
 * @param int $maxAttempts Maximum allowed attempts
 * @param int $decaySeconds Lockout duration in seconds
 * @return array ['allowed' => bool, 'retry_after' => int, 'remaining' => int]
 */
function checkRateLimit($action, $maxAttempts = 5, $decaySeconds = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = md5($action . '_' . $ip);
    $now = time();

    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'reset_at' => $now + $decaySeconds
        ];
    }

    $entry = &$_SESSION['rate_limits'][$key];

    // Reset window if expired
    if ($now > $entry['reset_at']) {
        $entry['attempts'] = 0;
        $entry['reset_at'] = $now + $decaySeconds;
    }

    if ($entry['attempts'] >= $maxAttempts) {
        return [
            'allowed'     => false,
            'retry_after' => max(1, $entry['reset_at'] - $now),
            'remaining'   => 0
        ];
    }

    return [
        'allowed'     => true,
        'retry_after' => 0,
        'remaining'   => $maxAttempts - $entry['attempts']
    ];
}

/**
 * Record a failed attempt for rate limiting
 * 
 * @param string $action Action key
 * @param int $decaySeconds Lockout duration
 */
function recordRateLimitAttempt($action, $decaySeconds = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = md5($action . '_' . $ip);
    $now = time();

    if (!isset($_SESSION['rate_limits'][$key]) || $now > $_SESSION['rate_limits'][$key]['reset_at']) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 1,
            'reset_at' => $now + $decaySeconds
        ];
    } else {
        $_SESSION['rate_limits'][$key]['attempts']++;
    }
}

/**
 * Reset rate limit after successful action
 * 
 * @param string $action Action key
 */
function resetRateLimit($action) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = md5($action . '_' . $ip);
    unset($_SESSION['rate_limits'][$key]);
}

