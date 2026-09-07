<?php
// google-auth.php
// Handles Google OAuth / Identity Services (GIS) token verification and sign in / sign up

header('Content-Type: application/json');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Accept both POST form data and JSON body
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?? [];
$credential = $_POST['credential'] ?? $jsonData['credential'] ?? '';

if (empty($credential)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Google credential token.']);
    exit;
}

// Rate limit Google Auth verification requests (max 15 attempts per 5 minutes per IP)
$rateLimit = checkRateLimit('google_auth', 15, 300);
if (!$rateLimit['allowed']) {
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Too many authentication attempts. Please try again in ' . $rateLimit['retry_after'] . ' seconds.'
    ]);
    exit;
}

// Strictly verify with Google TokenInfo API
$payload = null;

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $payload = json_decode($response, true);
    }
}

if (!$payload) {
    // Fallback using stream context to query Google TokenInfo API
    $opts = [
        'http' => [
            'method'  => 'GET',
            'timeout' => 5,
        ],
        'ssl' => [
            'verify_peer' => true,
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential), false, $context);
    if ($response) {
        $payload = json_decode($response, true);
    }
}

// Validate Google signature & audience claims (never trust unverified payload)
$validIssuers = ['https://accounts.google.com', 'accounts.google.com'];
$isValidAudience = isset($payload['aud']) && $payload['aud'] === GOOGLE_CLIENT_ID;
$isValidIssuer   = isset($payload['iss']) && in_array($payload['iss'], $validIssuers, true);
$isEmailVerified = !empty($payload['email']) && (!isset($payload['email_verified']) || $payload['email_verified'] === true || $payload['email_verified'] === 'true');

if (!$payload || !$isValidAudience || !$isValidIssuer || !$isEmailVerified) {
    recordRateLimitAttempt('google_auth', 300);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or unverified Google account credentials.']);
    exit;
}

resetRateLimit('google_auth');

$googleId = $payload['sub'] ?? '';
$email    = trim(strtolower($payload['email']));
$name     = trim($payload['name'] ?? explode('@', $email)[0]);
$avatar   = $payload['picture'] ?? null;

// 1. Check if user exists by google_id
$stmt = $conn->prepare("SELECT id, name, email, role, avatar, google_id FROM users WHERE google_id = ?");
$stmt->bind_param('s', $googleId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // 2. Check if user exists with the same email
    $stmt = $conn->prepare("SELECT id, name, email, role, avatar, google_id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Link google_id and update avatar if not already set
        $upStmt = $conn->prepare("UPDATE users SET google_id = ?, avatar = COALESCE(avatar, ?) WHERE id = ?");
        $upStmt->bind_param('ssi', $googleId, $avatar, $user['id']);
        $upStmt->execute();
        $upStmt->close();

        $user['google_id'] = $googleId;
        if (empty($user['avatar'])) {
            $user['avatar'] = $avatar;
        }
    } else {
        // 3. Register new user with Google credentials
        $insStmt = $conn->prepare("INSERT INTO users (name, email, google_id, avatar, role, password) VALUES (?, ?, ?, ?, 'user', NULL)");
        $insStmt->bind_param('ssss', $name, $email, $googleId, $avatar);
        if ($insStmt->execute()) {
            $newId = $insStmt->insert_id;
            $user = [
                'id'        => $newId,
                'name'      => $name,
                'email'     => $email,
                'role'      => 'user',
                'avatar'    => $avatar,
                'google_id' => $googleId
            ];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create user from Google profile.']);
            exit;
        }
        $insStmt->close();
    }
} else {
    // Update avatar if changed on Google
    if ($avatar && $user['avatar'] !== $avatar) {
        $upStmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $upStmt->bind_param('si', $avatar, $user['id']);
        $upStmt->execute();
        $upStmt->close();
        $user['avatar'] = $avatar;
    }
}

// Log in the user
setSession($user);

$baseUrl = getAppBaseUrl();
echo json_encode([
    'status'   => 'success',
    'message'  => 'Welcome, ' . htmlspecialchars($user['name']) . '!',
    'redirect' => ($user['role'] === 'admin') ? ($baseUrl . '/dashboard/admin.php') : ($baseUrl . '/index.php'),
    'user'     => [
        'id'     => $user['id'],
        'name'   => $user['name'],
        'email'  => $user['email'],
        'role'   => $user['role'],
        'avatar' => $user['avatar']
    ]
]);
