<?php
// contact-send.php
// Handles contact message submission from authenticated users

header('Content-Type: application/json');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Condition: User must be logged in or registered before they can contact
if (!isLoggedIn()) {
    echo json_encode([
        'status'  => 'error',
        'code'    => 'auth_required',
        'message' => 'You must be logged in or registered to send a message to the Admin.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// CSRF validation
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Security token invalid or expired. Please refresh the page and try again.'
    ]);
    exit;
}

// Rate limit contact inquiries (max 5 per 5 minutes per IP/session)
$rate = checkRateLimit('contact_send', 5, 300);
if (!$rate['allowed']) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'You are sending messages too quickly. Please wait ' . ceil($rate['retry_after'] / 60) . ' minute(s) before sending another inquiry.'
    ]);
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];
$name   = trim($user['name'] ?? $_POST['name'] ?? 'Anonymous User');
$email  = trim($user['email'] ?? $_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($subject) || empty($message)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Please provide both a subject and a message.'
    ]);
    exit;
}

// 1. Save message to database
$stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param('issss', $userId, $name, $email, $subject, $message);

if (!$stmt->execute()) {
    recordRateLimitAttempt('contact_send', 300);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: Could not record your message.'
    ]);
    $stmt->close();
    exit;
}

$messageId = $stmt->insert_id;
$stmt->close();
recordRateLimitAttempt('contact_send', 300);

// 2. Send email notification to Admin with CRLF injection sanitization
$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'songkimsreng001@gmail.com';
$cleanName    = sanitizeHeaderString($name);
$cleanSubject = sanitizeHeaderString($subject);
$cleanEmail   = sanitizeHeaderString($email);

$mailSubject = "[Portfolio Contact] " . $cleanSubject . " - from " . $cleanName;

$mailBody = "You have received a new contact message on your Portfolio website.\n\n"
          . "===============================================\n"
          . "SENDER DETAILS:\n"
          . "Name:    " . $cleanName . "\n"
          . "Email:   " . $cleanEmail . "\n"
          . "Date:    " . date('Y-m-d H:i:s') . "\n"
          . "===============================================\n\n"
          . "SUBJECT: " . $cleanSubject . "\n\n"
          . "MESSAGE:\n" . $message . "\n\n"
          . "===============================================\n"
          . "You can reply to this user directly in your Admin Dashboard:\n"
          . getAppBaseUrl() . "/dashboard/admin.php\n";

$safeHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$headers = "From: " . $cleanName . " <no-reply@" . $safeHost . ">\r\n"
         . "Reply-To: " . $cleanEmail . "\r\n"
         . "X-Mailer: PHP/" . phpversion();

// Attempt to send email
@mail($adminEmail, $mailSubject, $mailBody, $headers);

echo json_encode([
    'status'   => 'success',
    'message'  => 'Thank you, ' . htmlspecialchars($name) . '! Your message has been sent to Admin (' . htmlspecialchars($adminEmail) . '). You will receive a response soon.',
    'message_id' => $messageId
]);
