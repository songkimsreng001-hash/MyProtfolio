<?php
// includes/db.php
// Database Connection & Schema Management (Supports TiDB Cloud & Local MySQL)

require_once __DIR__ . '/config.php';

// Detect whether running locally or on production server
$isVercel = !empty(getenv('VERCEL')) ||
            (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false);

$isLocal = !$isVercel && (
    (!empty($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) ||
    (!empty($_SERVER['SERVER_NAME']) && (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false)) ||
    (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST']))
);

// TiDB Cloud Serverless Configuration
$tidbHost = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$tidbPort = 4000;
$tidbUser = '5h8RDADJ5sK8taX.root';
$tidbPass = 'jg2Hs8LlAdedWjPE';
$tidbName = 'portfolio';

// Check for DATABASE_URL environment variable (common in Vercel / Cloud PaaS)
$envDbUrl = getenv('DATABASE_URL');
$parsedHost = null;
$parsedPort = null;
$parsedUser = null;
$parsedPass = null;
$parsedName = null;

if (!empty($envDbUrl)) {
    $parsed = parse_url($envDbUrl);
    if ($parsed) {
        $parsedHost = $parsed['host'] ?? null;
        $parsedPort = isset($parsed['port']) ? (int)$parsed['port'] : null;
        $parsedUser = isset($parsed['user']) ? urldecode($parsed['user']) : null;
        $parsedPass = isset($parsed['pass']) ? urldecode($parsed['pass']) : null;
        $parsedName = isset($parsed['path']) ? trim($parsed['path'], '/') : null;
    }
}

// Resolve final database credentials
if (!defined('DB_HOST')) {
    if (!empty($parsedHost)) {
        define('DB_HOST', $parsedHost);
        define('DB_PORT', $parsedPort ?: 4000);
        define('DB_USER', $parsedUser ?: $tidbUser);
        define('DB_PASS', $parsedPass !== null ? $parsedPass : $tidbPass);
        define('DB_NAME', !empty($parsedName) ? $parsedName : $tidbName);
    } elseif (getenv('DB_HOST')) {
        define('DB_HOST', getenv('DB_HOST'));
        define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : 4000);
        define('DB_USER', getenv('DB_USER') ?: $tidbUser);
        $pass = getenv('DB_PASS');
        if ($pass === false || $pass === '') {
            $pass = getenv('DB_PASSWORD');
        }
        define('DB_PASS', ($pass !== false && $pass !== '') ? $pass : $tidbPass);
        define('DB_NAME', getenv('DB_NAME') ?: $tidbName);
    } elseif ($isLocal) {
        // Local XAMPP Environment (falls back to TiDB Cloud if local MySQL is offline)
        define('DB_HOST', '127.0.0.1');
        define('DB_PORT', 3306);
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'portfolio');
    } else {
        // Live Production (Vercel) default to TiDB Cloud Serverless
        define('DB_HOST', $tidbHost);
        define('DB_PORT', $tidbPort);
        define('DB_USER', $tidbUser);
        define('DB_PASS', $tidbPass);
        define('DB_NAME', $tidbName);
    }
}

/**
 * Helper to establish a MySQL / TiDB connection with SSL support
 */
function createDbConnection($host, $user, $pass, $name, $port) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $mysqli = mysqli_init();
    if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
    }

    $isTiDB = (
        strpos($host, 'tidbcloud.com') !== false ||
        $port == 4000 ||
        getenv('DB_SSL') === 'true'
    );

    if ($isTiDB) {
        // TiDB Cloud requires SSL/TLS encrypted connection
        $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
        $ok = @$mysqli->real_connect($host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL);
    } else {
        $ok = @$mysqli->real_connect($host, $user, $pass, $name, $port);
    }

    if ($ok && !$mysqli->connect_error) {
        return $mysqli;
    }
    return null;
}

// Attempt primary database connection
$conn = null;
try {
    $conn = createDbConnection(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
} catch (Throwable $e) {
    $conn = null;
    error_log('Database connection error (' . DB_HOST . '): ' . $e->getMessage());
}

// Fallback 1: If local MySQL failed or is offline on local machine, try TiDB Cloud!
if (!$conn && $isLocal) {
    try {
        $conn = createDbConnection($tidbHost, $tidbUser, $tidbPass, $tidbName, $tidbPort);
    } catch (Throwable $fallbackEx) {
        $conn = null;
        error_log('TiDB Cloud local fallback connection error: ' . $fallbackEx->getMessage());
    }
}

// Fallback 2: If on Vercel/Production and custom env failed, fallback to TiDB Cloud
if (!$conn && !$isLocal && DB_HOST !== $tidbHost) {
    try {
        $conn = createDbConnection($tidbHost, $tidbUser, $tidbPass, $tidbName, $tidbPort);
    } catch (Throwable $tidbEx) {
        $conn = null;
        error_log('TiDB Cloud production fallback error: ' . $tidbEx->getMessage());
    }
}

// Ensure $conn is null on error so scripts handle offline states gracefully
if (!$conn || $conn->connect_error) {
    $conn = null;
}

// Auto-check and add missing columns/tables if connected (guard to run once per session)
if ($conn && (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['portfolio_schema_verified']))) {
    try {
        $conn->set_charset('utf8mb4');

        // 1. Ensure users table exists
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            google_id VARCHAR(100) DEFAULT NULL,
            password VARCHAR(255) DEFAULT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            avatar VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $columns = [];
        $colResult = $conn->query("SHOW COLUMNS FROM users");
        if ($colResult) {
            while ($row = $colResult->fetch_assoc()) {
                $columns[$row['Field']] = $row;
            }
            
            if (!isset($columns['google_id'])) {
                $conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) DEFAULT NULL AFTER email");
            }
            if (!isset($columns['avatar'])) {
                $conn->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER role");
            }
            if (!isset($columns['updated_at'])) {
                $conn->query("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
            // Ensure password allows NULL for pure Google sign in accounts
            if (isset($columns['password']) && $columns['password']['Null'] === 'NO') {
                $conn->query("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
            }
        }

        // 2. Ensure messages table exists
        $conn->query("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending', 'replied') DEFAULT 'pending',
            admin_reply TEXT DEFAULT NULL,
            replied_at TIMESTAMP NULL DEFAULT NULL,
            user_read_at TIMESTAMP NULL DEFAULT NULL,
            admin_read_at TIMESTAMP NULL DEFAULT NULL,
            attachment_url VARCHAR(255) NULL DEFAULT NULL,
            attachment_type VARCHAR(50) NULL DEFAULT NULL,
            attachment_name VARCHAR(255) NULL DEFAULT NULL,
            reactions TEXT NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        // Ensure columns exist on messages
        $msgCols = [];
        $msgRes = $conn->query("SHOW COLUMNS FROM messages");
        if ($msgRes) {
            while ($r = $msgRes->fetch_assoc()) {
                $msgCols[$r['Field']] = $r;
            }
            if (!isset($msgCols['user_read_at'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN user_read_at TIMESTAMP NULL DEFAULT NULL AFTER replied_at");
            }
            if (!isset($msgCols['admin_read_at'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN admin_read_at TIMESTAMP NULL DEFAULT NULL AFTER user_read_at");
            }
            if (!isset($msgCols['attachment_url'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN attachment_url VARCHAR(255) NULL DEFAULT NULL AFTER admin_reply");
            }
            if (!isset($msgCols['attachment_type'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN attachment_type VARCHAR(50) NULL DEFAULT NULL AFTER attachment_url");
            }
            if (!isset($msgCols['attachment_name'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN attachment_name VARCHAR(255) NULL DEFAULT NULL AFTER attachment_type");
            }
            if (!isset($msgCols['reactions'])) {
                $conn->query("ALTER TABLE messages ADD COLUMN reactions TEXT NULL DEFAULT NULL AFTER attachment_name");
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['portfolio_schema_verified'] = true;
        }
    } catch (Throwable $schemaEx) {
        error_log('Database schema check error: ' . $schemaEx->getMessage());
    }
}
