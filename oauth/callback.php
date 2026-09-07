<?php
// oauth/callback.php
// Handles Google OAuth 2.0 authorization code exchange and user session creation

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$baseUrl = getAppBaseUrl();

// If user is already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: ' . $baseUrl . '/index.php');
    exit;
}

// Rate limit OAuth attempts (max 15 attempts per 5 minutes per IP)
if (!checkRateLimit('oauth_callback', 15, 300)) {
    header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Too many OAuth requests. Please wait a few minutes and try again.'));
    exit;
}
recordRateLimitAttempt('oauth_callback', 300);

$code  = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if (!empty($error)) {
    header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Google sign in was cancelled or encountered an error: ' . $error));
    exit;
}

if (empty($code)) {
    header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Missing authorization code from Google.'));
    exit;
}

// 1. Exchange authorization code for access token & ID token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokenData = json_decode($response, true);

if ($httpCode !== 200 || empty($tokenData['access_token'])) {
    $errDetail = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Token exchange failed');
    header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Google authentication failed: ' . $errDetail));
    exit;
}

$accessToken = $tokenData['access_token'];

// 2. Fetch user profile from Google UserInfo endpoint
$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$userResponse = curl_exec($ch);
$userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$profile = json_decode($userResponse, true);

if ($userHttpCode !== 200 || empty($profile['email'])) {
    header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Unable to retrieve profile from Google.'));
    exit;
}

$googleId = $profile['sub'] ?? '';
$email    = trim(strtolower($profile['email']));
$name     = trim($profile['name'] ?? explode('@', $email)[0]);
$avatar   = $profile['picture'] ?? null;

// 3. Database operations
// Check if user exists by google_id
$stmt = $conn->prepare("SELECT id, name, email, role, avatar, google_id FROM users WHERE google_id = ?");
$stmt->bind_param('s', $googleId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    // Check if user exists with the same email
    $stmt = $conn->prepare("SELECT id, name, email, role, avatar, google_id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Link google_id and update avatar
        $up = $conn->prepare("UPDATE users SET google_id = ?, avatar = COALESCE(avatar, ?) WHERE id = ?");
        $up->bind_param('ssi', $googleId, $avatar, $user['id']);
        $up->execute();
        $up->close();

        $user['google_id'] = $googleId;
        if (empty($user['avatar'])) {
            $user['avatar'] = $avatar;
        }
    } else {
        // Register new user
        $ins = $conn->prepare("INSERT INTO users (name, email, google_id, avatar, role, password) VALUES (?, ?, ?, ?, 'user', NULL)");
        $ins->bind_param('ssss', $name, $email, $googleId, $avatar);
        if ($ins->execute()) {
            $newId = $ins->insert_id;
            $user = [
                'id'        => $newId,
                'name'      => $name,
                'email'     => $email,
                'role'      => 'user',
                'avatar'    => $avatar,
                'google_id' => $googleId,
            ];
        } else {
            header('Location: ' . $baseUrl . '/login.php?error=' . urlencode('Database registration failed. Please try again.'));
            exit;
        }
        $ins->close();
    }
} else {
    // Update avatar if changed
    if ($avatar && $user['avatar'] !== $avatar) {
        $up = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $up->bind_param('si', $avatar, $user['id']);
        $up->execute();
        $up->close();
        $user['avatar'] = $avatar;
    }
}

// 4. Start session and redirect to absolute base path
setSession($user);

if ($user['role'] === 'admin') {
    header('Location: ' . $baseUrl . '/dashboard/admin.php');
} else {
    header('Location: ' . $baseUrl . '/index.php');
}
exit;
