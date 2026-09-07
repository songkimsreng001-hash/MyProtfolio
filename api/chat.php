<?php
// api/chat.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = getCurrentUser();

if (!$user) {
    echo json_encode([
        'status'  => 'error',
        'code'    => 'auth_required',
        'message' => 'Please log in to chat with the Admin.'
    ]);
    exit;
}

$userId    = (int)$user['id'];
$userName  = trim($user['name']);
$userEmail = trim($user['email']);
$isAdmin   = ($user['role'] === 'admin');

// Handle POST: Reactions or User replies / sends a message/file to Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? ($_GET['action'] ?? ''));

    // Handle Message Reaction (Emoji reaction to message bubble)
    if ($action === 'react') {
        $msgId = (int)($_POST['message_id'] ?? 0);
        $emoji = trim($_POST['emoji'] ?? '');

        if ($msgId <= 0 || empty($emoji)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid message or emoji.'
            ]);
            exit;
        }

        // Fetch existing reactions
        $stmt = $conn->prepare("SELECT id, reactions FROM messages WHERE id = ?");
        $stmt->bind_param('i', $msgId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Message not found.'
            ]);
            exit;
        }

        $target = trim($_POST['target'] ?? 'msg');
        if ($target !== 'reply') {
            $target = 'msg';
        }

        $reactions = ['msg' => [], 'reply' => []];
        if (!empty($row['reactions'])) {
            $decoded = json_decode($row['reactions'], true);
            if (is_array($decoded)) {
                if (isset($decoded['msg']) || isset($decoded['reply'])) {
                    $reactions['msg'] = is_array($decoded['msg'] ?? null) ? $decoded['msg'] : [];
                    $reactions['reply'] = is_array($decoded['reply'] ?? null) ? $decoded['reply'] : [];
                } else {
                    // Flat format fallback
                    $reactions['msg'] = $decoded;
                }
            }
        }

        // Toggle user reaction for target
        if (!isset($reactions[$target][$emoji]) || !is_array($reactions[$target][$emoji])) {
            $reactions[$target][$emoji] = [];
        }

        $userIndex = array_search($userId, $reactions[$target][$emoji]);
        if ($userIndex !== false) {
            // User already reacted -> remove reaction (toggle off)
            array_splice($reactions[$target][$emoji], $userIndex, 1);
            if (empty($reactions[$target][$emoji])) {
                unset($reactions[$target][$emoji]);
            }
        } else {
            // Add user reaction
            $reactions[$target][$emoji][] = $userId;
        }

        $hasAnyReactions = !empty($reactions['msg']) || !empty($reactions['reply']);
        $jsonToSave = $hasAnyReactions ? json_encode($reactions, JSON_UNESCAPED_UNICODE) : null;
        $upStmt = $conn->prepare("UPDATE messages SET reactions = ? WHERE id = ?");
        $upStmt->bind_param('si', $jsonToSave, $msgId);
        $upStmt->execute();
        $upStmt->close();

        // Helper to format target reactions
        $formatList = function($targetMap, $uId) {
            $list = [];
            foreach ($targetMap as $em => $users) {
                if (!empty($users)) {
                    $list[] = [
                        'emoji'        => $em,
                        'count'        => count($users),
                        'user_reacted' => in_array($uId, $users)
                    ];
                }
            }
            return $list;
        };

        echo json_encode([
            'status'     => 'success',
            'message_id' => $msgId,
            'target'     => $target,
            'reactions'  => [
                'msg'   => $formatList($reactions['msg'], $userId),
                'reply' => $formatList($reactions['reply'], $userId)
            ]
        ]);
        exit;
    }

    $message   = trim($_POST['message'] ?? '');
    $subject   = trim($_POST['subject'] ?? 'Direct Message to Admin');
    $replyToId = (int)($_POST['reply_to_id'] ?? 0);

    // Handle File Attachment (Picture, Video, File)
    $attachmentUrl  = null;
    $attachmentType = null;
    $attachmentName = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];

        // Enforce 10MB maximum file size
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Attachment exceeds maximum allowed size of 10MB.'
            ]);
            exit;
        }

        $origName = basename($file['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $vidExts = ['mp4', 'webm', 'mov', 'mkv', 'avi'];
        $docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'csv', 'json'];

        $allowedMimes = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'mp4'  => ['video/mp4'],
            'webm' => ['video/webm'],
            'mov'  => ['video/quicktime'],
            'mkv'  => ['video/x-matroska', 'video/mkv', 'application/octet-stream'],
            'avi'  => ['video/x-msvideo', 'video/avi'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt'  => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
            'rar'  => ['application/x-rar-compressed', 'application/octet-stream', 'application/vnd.rar'],
            'txt'  => ['text/plain'],
            'csv'  => ['text/plain', 'text/csv', 'application/csv'],
            'json' => ['application/json', 'text/plain']
        ];

        if (!isset($allowedMimes[$ext])) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'File extension not supported. Uploads of this type are prohibited.'
            ]);
            exit;
        }

        // Validate MIME type with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!$detectedMime || !in_array($detectedMime, $allowedMimes[$ext], true)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'File content does not match its file extension.'
            ]);
            exit;
        }

        if (in_array($ext, $imgExts, true)) {
            $attachmentType = 'image';
        } elseif (in_array($ext, $vidExts, true)) {
            $attachmentType = 'video';
        } else {
            $attachmentType = 'file';
        }

        $uploadDir = __DIR__ . '/../uploads/chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $newFileName = 'chat_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $attachmentUrl  = 'uploads/chat/' . $newFileName;
            $attachmentName = substr(preg_replace('/[^\w\.\-\s]/', '_', $origName), 0, 100);
        }
    }

    if (empty($message) && empty($attachmentUrl)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please enter a message or choose a file.'
        ]);
        exit;
    }

    if (empty($subject)) {
        $subject = 'Direct Message to Admin';
    }

    if ($isAdmin) {
        $replyToId    = (int)($_POST['reply_to_id'] ?? 0);
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $targetEmail  = trim($_POST['target_email'] ?? '');

        // If reply_to_id is not passed directly, find the latest pending or latest message for this user
        if ($replyToId <= 0 && ($targetUserId > 0 || !empty($targetEmail))) {
            if ($targetUserId > 0 && !empty($targetEmail)) {
                $fStmt = $conn->prepare("SELECT id FROM messages WHERE (user_id = ? OR email = ?) ORDER BY (status = 'pending') DESC, created_at DESC LIMIT 1");
                $fStmt->bind_param('is', $targetUserId, $targetEmail);
            } elseif ($targetUserId > 0) {
                $fStmt = $conn->prepare("SELECT id FROM messages WHERE user_id = ? ORDER BY (status = 'pending') DESC, created_at DESC LIMIT 1");
                $fStmt->bind_param('i', $targetUserId);
            } else {
                $fStmt = $conn->prepare("SELECT id FROM messages WHERE email = ? ORDER BY (status = 'pending') DESC, created_at DESC LIMIT 1");
                $fStmt->bind_param('s', $targetEmail);
            }
            if ($fStmt) {
                $fStmt->execute();
                $fRow = $fStmt->get_result()->fetch_assoc();
                if ($fRow) {
                    $replyToId = (int)$fRow['id'];
                }
                $fStmt->close();
            }
        }

        if ($replyToId > 0) {
            // Fetch original message to retrieve user's email and name for notifications
            $origStmt = $conn->prepare("SELECT id, user_id, name, email, subject FROM messages WHERE id = ?");
            $origStmt->bind_param('i', $replyToId);
            $origStmt->execute();
            $origMsg = $origStmt->get_result()->fetch_assoc();
            $origStmt->close();

            $stmt = $conn->prepare("UPDATE messages SET status = 'replied', admin_reply = ?, attachment_url = COALESCE(?, attachment_url), attachment_type = COALESCE(?, attachment_type), attachment_name = COALESCE(?, attachment_name), replied_at = NOW(), user_read_at = NULL, admin_read_at = NOW() WHERE id = ?");
            $stmt->bind_param('ssssi', $message, $attachmentUrl, $attachmentType, $attachmentName, $replyToId);
            $stmt->execute();
            $stmt->close();

            // Send email notification to user
            $toEmail  = $origMsg['email'] ?? $targetEmail;
            $toName   = $origMsg['name'] ?? 'User';
            $origSub  = $origMsg['subject'] ?? 'Your Inquiry';
            $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'songkimsreng001@gmail.com';

            if (!empty($toEmail)) {
                $safeToEmail    = sanitizeHeaderString($toEmail);
                $safeToName     = sanitizeHeaderString($toName);
                $safeOrigSub    = sanitizeHeaderString($origSub);
                $safeAdminEmail = sanitizeHeaderString($adminEmail);

                $emailSubject = "Re: " . $safeOrigSub . " - Kimsreng Song";
                $emailBody = "Hello " . $toName . ",\n\n"
                           . "Thank you for reaching out. Here is my reply to your inquiry:\n\n"
                           . "--------------------------------------------------\n"
                           . $message . "\n"
                           . "--------------------------------------------------\n\n"
                           . ($attachmentUrl ? "Attachment: " . getAppBaseUrl() . "/" . $attachmentUrl . "\n\n" : "")
                           . "You can view and continue our live chat on my portfolio website:\n"
                           . getAppBaseUrl() . "\n\n"
                           . "Best regards,\n"
                           . "Kimsreng Song\n"
                           . "Full-Stack Developer\n";
                $headers = "From: Kimsreng Song <" . $safeAdminEmail . ">\r\n"
                         . "Reply-To: " . $safeAdminEmail . "\r\n"
                         . "X-Mailer: PHP/" . phpversion();
                @mail($safeToEmail, $emailSubject, $emailBody, $headers);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Reply sent to ' . htmlspecialchars($toName) . ' successfully!',
                'data'    => [
                    'id'              => $replyToId,
                    'admin_reply'     => htmlspecialchars($message),
                    'attachment_url'  => $attachmentUrl,
                    'attachment_type' => $attachmentType,
                    'attachment_name' => htmlspecialchars($attachmentName ?? ''),
                    'replied_at'      => date('g:i A'),
                    'status'          => 'replied'
                ]
            ]);
            exit;
        }
    }

    // Rate limit user messages (max 20 messages per minute per user)
    if (!$isAdmin) {
        if (!checkRateLimit('chat_msg_' . $userId, 20, 60)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'You are sending messages too quickly. Please wait a moment.'
            ]);
            exit;
        }
        recordRateLimitAttempt('chat_msg_' . $userId, 60);
    }

    // User is sending a new message / reply / attachment to Admin
    $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, subject, message, status, attachment_url, attachment_type, attachment_name, admin_read_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NULL)");
    $stmt->bind_param('isssssss', $userId, $userName, $userEmail, $subject, $message, $attachmentUrl, $attachmentType, $attachmentName);

    if (!$stmt->execute()) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to send message to database.'
        ]);
        $stmt->close();
        exit;
    }

    $msgId = $stmt->insert_id;
    $stmt->close();

    // Send email alert to Admin
    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'songkimsreng001@gmail.com';
    $safeUserName  = sanitizeHeaderString($userName);
    $safeUserEmail = sanitizeHeaderString($userEmail);
    $safeSubject   = sanitizeHeaderString($subject);
    $safeHost      = sanitizeHeaderString(preg_replace('/[^a-zA-Z0-9\.\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));

    $mailSubject = "[Portfolio Chat] New message from " . $safeUserName . " - " . $safeSubject;
    $mailBody = "You have received a new chat message on your Portfolio website.\n\n"
              . "===============================================\n"
              . "SENDER DETAILS:\n"
              . "Name:    " . $userName . "\n"
              . "Email:   " . $userEmail . "\n"
              . "Date:    " . date('Y-m-d H:i:s') . "\n"
              . "===============================================\n\n"
              . "SUBJECT: " . $subject . "\n\n"
              . "MESSAGE:\n" . $message . "\n\n"
              . ($attachmentUrl ? "ATTACHMENT: " . getAppBaseUrl() . "/" . $attachmentUrl . " (" . $attachmentName . ")\n\n" : "")
              . "===============================================\n"
              . "Reply directly in your Admin Dashboard:\n"
              . getAppBaseUrl() . "/dashboard/admin.php#messages\n";

    $headers = "From: " . $safeUserName . " <no-reply@" . $safeHost . ">\r\n"
             . "Reply-To: " . $safeUserEmail . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    @mail($adminEmail, $mailSubject, $mailBody, $headers);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Message sent to Admin successfully!',
        'data'    => [
            'id'              => $msgId,
            'subject'         => htmlspecialchars($subject),
            'message'         => htmlspecialchars($message),
            'attachment_url'  => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'attachment_name' => htmlspecialchars($attachmentName ?? ''),
            'status'          => 'pending',
            'created_at'      => date('g:i A'),
            'time_label'      => 'Just now'
        ]
    ]);
    exit;
}

// Handle GET: Fetch chat history
if (!$isAdmin) {
    if (isset($_GET['mark_read']) && $_GET['mark_read'] === '1') {
        $upStmt = $conn->prepare("UPDATE messages SET user_read_at = NOW() WHERE (user_id = ? OR email = ?) AND status = 'replied' AND user_read_at IS NULL");
        $upStmt->bind_param('is', $userId, $userEmail);
        $upStmt->execute();
        $upStmt->close();
    }

    $stmt = $conn->prepare("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE (m.user_id = ? OR m.email = ?) ORDER BY m.created_at ASC");
    $stmt->bind_param('is', $userId, $userEmail);
} else {
    // Admin viewing chat history: optionally filter by target user
    $targetUserId = (int)($_GET['target_user_id'] ?? 0);
    $targetEmail  = trim($_GET['target_email'] ?? '');
    $targetMsgId  = (int)($_GET['target_msg_id'] ?? ($_GET['message_id'] ?? 0));

    if ($targetMsgId > 0 && empty($targetEmail) && $targetUserId <= 0) {
        $findStmt = $conn->prepare("SELECT user_id, email FROM messages WHERE id = ?");
        $findStmt->bind_param('i', $targetMsgId);
        $findStmt->execute();
        $fRow = $findStmt->get_result()->fetch_assoc();
        if ($fRow) {
            $targetUserId = (int)$fRow['user_id'];
            $targetEmail  = $fRow['email'];
        }
        $findStmt->close();
    }

    if ($targetUserId > 0 || !empty($targetEmail)) {
        if ($targetUserId > 0 && !empty($targetEmail)) {
            $stmt = $conn->prepare("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE (m.user_id = ? OR m.email = ?) ORDER BY m.created_at ASC");
            $stmt->bind_param('is', $targetUserId, $targetEmail);
        } elseif ($targetUserId > 0) {
            $stmt = $conn->prepare("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE m.user_id = ? ORDER BY m.created_at ASC");
            $stmt->bind_param('i', $targetUserId);
        } else {
            $stmt = $conn->prepare("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE m.email = ? ORDER BY m.created_at ASC");
            $stmt->bind_param('s', $targetEmail);
        }
    } else {
        // Fallback: all messages
        $stmt = $conn->prepare("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.created_at ASC");
    }
}

$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $parsedReactions = ['msg' => [], 'reply' => []];
    if (!empty($row['reactions'])) {
        $rDecoded = json_decode($row['reactions'], true);
        if (is_array($rDecoded)) {
            $msgMap = [];
            $replyMap = [];
            if (isset($rDecoded['msg']) || isset($rDecoded['reply'])) {
                $msgMap = is_array($rDecoded['msg'] ?? null) ? $rDecoded['msg'] : [];
                $replyMap = is_array($rDecoded['reply'] ?? null) ? $rDecoded['reply'] : [];
            } else {
                $msgMap = $rDecoded;
            }
            foreach ($msgMap as $em => $users) {
                if (is_array($users) && !empty($users)) {
                    $parsedReactions['msg'][] = [
                        'emoji'        => $em,
                        'count'        => count($users),
                        'user_reacted' => in_array($userId, $users)
                    ];
                }
            }
            foreach ($replyMap as $em => $users) {
                if (is_array($users) && !empty($users)) {
                    $parsedReactions['reply'][] = [
                        'emoji'        => $em,
                        'count'        => count($users),
                        'user_reacted' => in_array($userId, $users)
                    ];
                }
            }
        }
    }

    $messages[] = [
        'id'              => (int)$row['id'],
        'user_id'         => (int)$row['user_id'],
        'subject'         => htmlspecialchars($row['subject']),
        'user_name'       => htmlspecialchars($row['name']),
        'user_email'      => htmlspecialchars($row['email']),
        'user_avatar'     => !empty($row['user_avatar']) ? $row['user_avatar'] : null,
        'message'         => htmlspecialchars($row['message']),
        'attachment_url'  => !empty($row['attachment_url']) ? $row['attachment_url'] : null,
        'attachment_type' => !empty($row['attachment_type']) ? $row['attachment_type'] : null,
        'attachment_name' => !empty($row['attachment_name']) ? htmlspecialchars($row['attachment_name']) : null,
        'reactions'       => $parsedReactions,
        'status'          => $row['status'],
        'admin_reply'     => !empty($row['admin_reply']) ? htmlspecialchars($row['admin_reply']) : null,
        'replied_at'      => $row['replied_at'] ? date('g:i A', strtotime($row['replied_at'])) : null,
        'created_at'      => date('g:i A', strtotime($row['created_at'])),
        'created_full'    => date('M j, Y g:i A', strtotime($row['created_at'])),
        'date_group'      => date('Y-m-d', strtotime($row['created_at'])) === date('Y-m-d') ? 'Today' : date('M j, Y', strtotime($row['created_at']))
    ];
}
$stmt->close();

$targetUser = null;
if ($isAdmin && count($messages) > 0) {
    $lastMsg = end($messages);
    $targetUser = [
        'id'     => $lastMsg['user_id'],
        'name'   => $lastMsg['user_name'],
        'email'  => $lastMsg['user_email'],
        'avatar' => $lastMsg['user_avatar']
    ];
}

echo json_encode([
    'status'      => 'success',
    'is_admin'    => $isAdmin,
    'admin'       => [
        'name'   => 'Kimsreng Song',
        'role'   => 'Full-Stack Developer',
        'avatar' => 'assets/images/Kimsreng Song.JPG',
        'status' => 'Online'
    ],
    'user'        => [
        'id'     => $userId,
        'name'   => $userName,
        'email'  => $userEmail,
        'avatar' => $user['avatar'] ?? null
    ],
    'target_user' => $targetUser,
    'messages'    => $messages
]);
