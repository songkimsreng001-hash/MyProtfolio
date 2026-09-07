<?php
// api/notifications.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

function formatTimeAgo($timestamp) {
    if (!$timestamp) return 'Recently';
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

$user = getCurrentUser();

if (!$user) {
    echo json_encode([
        'status'               => 'success',
        'logged_in'            => false,
        'unread_count'         => 0,
        'not_replied_count'    => 0,
        'unread_replies_count' => 0,
        'notifications'        => []
    ]);
    exit;
}

$userId    = (int)$user['id'];
$userEmail = $user['email'];
$isAdmin   = ($user['role'] === 'admin');

// Handle POST actions (mark as read)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        if ($isAdmin) {
            $conn->query("UPDATE messages SET admin_read_at = NOW() WHERE admin_read_at IS NULL");
        } else {
            $stmt = $conn->prepare("UPDATE messages SET user_read_at = NOW() WHERE (user_id = ? OR email = ?) AND status = 'replied' AND user_read_at IS NULL");
            $stmt->bind_param('is', $userId, $userEmail);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read.']);
        exit;
    }
    
    if ($action === 'mark_read') {
        $msgId = (int)($_POST['message_id'] ?? 0);
        if ($msgId > 0) {
            if ($isAdmin) {
                $stmt = $conn->prepare("UPDATE messages SET admin_read_at = NOW() WHERE id = ?");
                $stmt->bind_param('i', $msgId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("UPDATE messages SET user_read_at = NOW() WHERE id = ? AND (user_id = ? OR email = ?)");
                $stmt->bind_param('iis', $msgId, $userId, $userEmail);
                $stmt->execute();
                $stmt->close();
            }
        }
        echo json_encode(['status' => 'success', 'message' => 'Notification marked as read.']);
        exit;
    }
}

// Handle GET: Fetch Notifications
$notifications       = [];
$unreadCount         = 0;
$notRepliedCount     = 0;
$unreadRepliesCount  = 0;

if ($isAdmin) {
    // Admin Notifications: count total messages not replied yet (status = 'pending')
    $res = $conn->query("SELECT COUNT(*) as c FROM messages WHERE status = 'pending'");
    if ($res) {
        $notRepliedCount = (int)$res->fetch_assoc()['c'];
    }
    $unreadCount = $notRepliedCount;

    $stmt = $conn->prepare("SELECT id, name, email, subject, message, status, created_at, admin_read_at FROM messages ORDER BY created_at DESC LIMIT 15");
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        $isPending = ($row['status'] === 'pending');
        $notifications[] = [
            'id'         => (int)$row['id'],
            'type'       => ($isPending ? 'inquiry_pending' : 'inquiry_replied'),
            'title'      => ($isPending ? '⚠️ Unreplied: ' : '') . 'Message from ' . htmlspecialchars($row['name']),
            'subject'    => htmlspecialchars($row['subject']),
            'preview'    => htmlspecialchars(mb_substr($row['message'], 0, 90) . (mb_strlen($row['message']) > 90 ? '...' : '')),
            'time_ago'   => formatTimeAgo($row['created_at']),
            'created_at' => $row['created_at'],
            'is_unread'  => $isPending,
            'status'     => $row['status'],
            'url'        => 'dashboard/admin.php#messages'
        ];
    }
    $stmt->close();
} else {
    // Regular User Notifications:
    // 1. Count user messages that are NOT replied yet by Admin
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM messages WHERE (user_id = ? OR email = ?) AND status = 'pending'");
    $stmt->bind_param('is', $userId, $userEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $notRepliedCount = (int)$res->fetch_assoc()['c'];
    }
    $stmt->close();

    // 2. Count unread replies from Admin
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM messages WHERE (user_id = ? OR email = ?) AND status = 'replied' AND user_read_at IS NULL");
    $stmt->bind_param('is', $userId, $userEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $unreadRepliesCount = (int)$res->fetch_assoc()['c'];
    }
    $stmt->close();

    // Unread count for navbar badge:
    // If admin has replied, show the reply count (highest priority)
    // Otherwise show not_replied_count if waiting
    $unreadCount = ($unreadRepliesCount > 0) ? $unreadRepliesCount : $notRepliedCount;

    // Fetch user messages
    $stmt = $conn->prepare("SELECT id, subject, message, status, admin_reply, replied_at, user_read_at, created_at FROM messages WHERE (user_id = ? OR email = ?) ORDER BY COALESCE(replied_at, created_at) DESC LIMIT 15");
    $stmt->bind_param('is', $userId, $userEmail);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if ($row['status'] === 'replied' && !empty($row['admin_reply'])) {
            $isUnread = is_null($row['user_read_at']);
            $notifications[] = [
                'id'         => (int)$row['id'],
                'type'       => 'admin_reply',
                'title'      => 'Kimsreng Song replied to your message',
                'subject'    => htmlspecialchars($row['subject']),
                'preview'    => htmlspecialchars(mb_substr($row['admin_reply'], 0, 90) . (mb_strlen($row['admin_reply']) > 90 ? '...' : '')),
                'full_reply' => htmlspecialchars($row['admin_reply']),
                'user_msg'   => htmlspecialchars($row['message']),
                'time_ago'   => formatTimeAgo($row['replied_at']),
                'timestamp'  => $row['replied_at'],
                'is_unread'  => $isUnread,
                'status'     => 'replied'
            ];
        } else {
            $notifications[] = [
                'id'         => (int)$row['id'],
                'type'       => 'pending_review',
                'title'      => '⏳ Not replied yet by Admin',
                'subject'    => htmlspecialchars($row['subject']),
                'preview'    => 'Waiting for response from Kimsreng Song...',
                'user_msg'   => htmlspecialchars($row['message']),
                'time_ago'   => formatTimeAgo($row['created_at']),
                'timestamp'  => $row['created_at'],
                'is_unread'  => true,
                'status'     => 'pending'
            ];
        }
    }
    $stmt->close();
}

echo json_encode([
    'status'               => 'success',
    'logged_in'            => true,
    'user_role'            => $user['role'],
    'user_name'            => $user['name'],
    'unread_count'         => $unreadCount,
    'not_replied_count'    => $notRepliedCount,
    'unread_replies_count' => $unreadRepliesCount,
    'notifications'        => $notifications
]);

