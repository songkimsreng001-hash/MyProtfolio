<?php
// includes/db.php
require_once __DIR__ . '/config.php';

// Detect whether running locally or on production server
$isLocal = (
    php_sapi_name() === 'cli' ||
    in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
    in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
);

// Database Configurations
if ($isLocal) {
    // Local XAMPP Environment
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'portfolio');
} else {
    // Live / Production Hosting Environment (Hostinger)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u715047215_portfolio');
    define('DB_PASS', 'Ashraf@123');
    define('DB_NAME', 'u715047215_portfolio');
}

// Attempt connection with graceful fallback
$conn = null;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Throwable $e) {
    // Fallback: If production credentials failed on local machine, try local root
    try {
        $conn = new mysqli('127.0.0.1', 'root', '', 'portfolio');
    } catch (Throwable $fallbackError) {
        error_log('Database connection error: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode([
            'status'  => 'error',
            'message' => 'Database service temporarily unavailable. Please try again shortly.'
        ]));
    }
}

if (!$conn || $conn->connect_error) {
    error_log('Database connection error: ' . ($conn ? $conn->connect_error : 'Unknown error'));
    http_response_code(500);
    die(json_encode([
        'status'  => 'error',
        'message' => 'Database service temporarily unavailable. Please try again shortly.'
    ]));
}

$conn->set_charset('utf8mb4');

// Auto-check and add missing columns/tables if needed (guard to run once per session)
if (!isset($_SESSION['portfolio_schema_verified'])) {
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

// Auto-create messages table if not present
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");

// Ensure columns exist
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
    $_SESSION['portfolio_schema_verified'] = true;
}
