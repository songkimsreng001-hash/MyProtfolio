<?php
// dashboard/admin.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

requireLogin();
requireAdmin();

$flashMsg = null;
$flashType = 'success';
$defaultTab = 'dashboard';

// Handle delete user (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $flashMsg = 'Invalid security token. Please refresh and try again.';
        $flashType = 'error';
    } else {
        $delId = (int)($_POST['user_id'] ?? 0);
        if ($delId > 0 && $delId !== (int)$_SESSION['user_id']) { // prevent self-delete
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $delId);
            $stmt->execute();
            $stmt->close();
            $flashMsg = 'The user has been removed successfully.';
            $flashType = 'success';
        } else {
            $flashMsg = 'You cannot delete your own account or invalid user ID.';
            $flashType = 'error';
        }
    }
    $defaultTab = 'users';
}

// Handle delete message (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $flashMsg = 'Invalid security token. Please refresh and try again.';
        $flashType = 'error';
    } else {
        $delMsgId = (int)($_POST['message_id'] ?? 0);
        if ($delMsgId > 0) {
            $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->bind_param('i', $delMsgId);
            $stmt->execute();
            $stmt->close();
            $flashMsg = 'Message inquiry deleted successfully.';
            $flashType = 'success';
        }
    }
    $defaultTab = 'messages';
}

// Handle reply to message (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_message') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $flashMsg = 'Invalid security token. Please refresh and try again.';
        $flashType = 'error';
    } else {
        $msgId       = (int)($_POST['message_id'] ?? 0);
        $replyText   = trim($_POST['reply_content'] ?? '');
        $toEmail     = trim($_POST['recipient_email'] ?? '');
        $origSubject = trim($_POST['original_subject'] ?? 'Your Inquiry');
        $recipientName = trim($_POST['recipient_name'] ?? 'Valued User');

        if ($msgId > 0 && !empty($replyText) && !empty($toEmail)) {
            // Update database
            $stmt = $conn->prepare("UPDATE messages SET status = 'replied', admin_reply = ?, replied_at = NOW(), user_read_at = NULL, admin_read_at = NOW() WHERE id = ?");
            $stmt->bind_param('si', $replyText, $msgId);
            $stmt->execute();
            $stmt->close();

            // Send Email to user from Admin Email
            $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'songkimsreng001@gmail.com';
            $safeToEmail     = sanitizeHeaderString($toEmail);
            $safeAdminEmail  = sanitizeHeaderString($adminEmail);
            $safeOrigSubject = sanitizeHeaderString($origSubject);
            $emailSubject = "Re: " . $safeOrigSubject . " - Kimsreng Song";
            $emailBody = "Hello " . $recipientName . ",\n\n"
                       . "Thank you for contacting me. Here is my response to your inquiry:\n\n"
                       . "--------------------------------------------------\n"
                       . $replyText . "\n"
                       . "--------------------------------------------------\n\n"
                       . "Best regards,\n"
                       . "Kimsreng Song\n"
                       . "Full-Stack Developer\n"
                       . "Email: " . $adminEmail . "\n"
                       . "Website: " . getAppBaseUrl() . "\n";

            $headers = "From: Kimsreng Song <" . $safeAdminEmail . ">\r\n"
                     . "Reply-To: " . $safeAdminEmail . "\r\n"
                     . "X-Mailer: PHP/" . phpversion();

            @mail($safeToEmail, $emailSubject, $emailBody, $headers);

            $flashMsg = 'Your reply has been sent via email to ' . htmlspecialchars($toEmail) . ' and saved to the dashboard.';
            $flashType = 'success';
        } else {
            $flashMsg = 'Please enter a reply message before submitting.';
            $flashType = 'error';
        }
    }
    $defaultTab = 'messages';
}

// Search Users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
if (!empty($search)) {
    $defaultTab = 'users';
}

// Stats
$totalUsers   = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$totalAdmins  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];
$totalGoogle  = $conn->query("SELECT COUNT(*) as c FROM users WHERE google_id IS NOT NULL AND google_id != ''")->fetch_assoc()['c'];
$totalAll     = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];

// Message Stats
$totalMsgs    = $conn->query("SELECT COUNT(*) as c FROM messages")->fetch_assoc()['c'];
$pendingMsgs  = $conn->query("SELECT COUNT(*) as c FROM messages WHERE status = 'pending'")->fetch_assoc()['c'];
$repliedMsgs  = $conn->query("SELECT COUNT(*) as c FROM messages WHERE status = 'replied'")->fetch_assoc()['c'];

// Fetch Messages
$messages = $conn->query("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC");

// Recent items for Overview Tab
$recentMessages = $conn->query("SELECT m.*, u.avatar as user_avatar FROM messages m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 5");
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

$user = getCurrentUser();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-header d-flex align-items-center justify-content-between px-4 py-3 border-bottom" style="border-color:var(--border-subtle)!important;">
            <a href="admin.php" class="navbar-brand fw-bold mb-0 text-decoration-none" style="font-size:1.35rem;">
                Kimsreng<span class="text-accent">.</span>
            </a>
            <span class="badge bg-accent-subtle text-accent border border-accent-subtle px-2 py-1 rounded-pill" style="font-size:0.72rem;">Admin Panel</span>
        </div>

        <!-- Sidebar Admin Profile Card -->
        <div class="sidebar-profile-card mx-3 my-3 p-3 rounded-4" style="background:var(--accent-light);border:1px solid var(--border);">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar" style="width:44px;height:44px;">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <span><?= strtoupper(substr($user['name'] ?: 'A', 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-truncate flex-grow-1">
                    <div class="fw-bold text-truncate" style="font-size:0.95rem;color:var(--text-primary);"><?= htmlspecialchars($user['name']) ?></div>
                    <small class="text-muted text-truncate d-block" style="font-size:0.78rem;"><?= htmlspecialchars($user['email']) ?></small>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center" style="border-color:var(--border-subtle)!important;">
                <span class="badge bg-accent text-white" style="font-size:0.68rem;padding:3px 8px;">Administrator</span>
                <span class="text-success" style="font-size:0.75rem;"><i class="bi bi-circle-fill me-1" style="font-size:0.55rem;"></i>Active</span>
            </div>
        </div>

        <!-- Navigation Links (Click on any function to display only that function) -->
        <nav class="nav flex-column px-2 flex-grow-1" id="sidebarNav">
            <a class="nav-link nav-view-link active mb-1" href="#dashboard" data-view="dashboard">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a class="nav-link nav-view-link d-flex justify-content-between align-items-center mb-1" href="#messages" data-view="messages">
                <span><i class="bi bi-chat-left-text me-2"></i> Messages</span>
                <?php if ($pendingMsgs > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill px-2"><?= $pendingMsgs ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link nav-view-link d-flex justify-content-between align-items-center mb-1" href="#users" data-view="users">
                <span><i class="bi bi-people me-2"></i> Users</span>
                <span class="badge bg-accent-subtle text-accent rounded-pill px-2"><?= $totalAll ?></span>
            </a>
            <a class="nav-link mb-1" href="../index.php">
                <i class="bi bi-globe me-2"></i> View Portfolio
            </a>
            <hr style="border-color:var(--border-subtle);margin:12px 10px;">
            
            <!-- Sidebar Dark/Light Mode Switcher -->
            <div class="sidebar-theme-switch mx-3 mb-3 p-2 rounded-3 d-flex align-items-center justify-content-between" style="background:var(--bg-primary);border:1px solid var(--border);">
                <div class="d-flex align-items-center gap-2 ps-1">
                    <i class="bi bi-moon-stars-fill text-accent theme-icon-moon"></i>
                    <i class="bi bi-sun-fill text-warning theme-icon-sun"></i>
                    <span style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);">Appearance</span>
                </div>
                <button class="btn btn-theme-toggle" id="themeToggleBtn" type="button" aria-label="Toggle theme" title="Toggle theme">
                    <i class="bi bi-sun-fill theme-icon-sun"></i>
                <i class="bi bi-moon-stars-fill theme-icon-moon"></i>
            </button>
        </div>
            <a class="nav-link text-danger mb-2" href="#" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="admin-main flex-grow-1">
        <!-- Top Bar -->
        <div class="admin-topbar d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color:var(--border-subtle)!important;">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-accent d-lg-none py-1 px-2" type="button" id="sidebarToggleBtn" title="Toggle Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h1 class="page-title mb-0" id="currentViewTitle">Dashboard Overview</h1>
                    <div class="page-subtitle">Welcome, <?= htmlspecialchars($user['name']) ?>! &bull; <span class="text-accent fw-semibold"></span></div>
                </div>
            </div>
            <div class="d-none d-sm-flex align-items-center gap-3">
                <a href="../index.php" class="btn btn-sm btn-outline-accent">
                    <i class="bi bi-arrow-left me-1"></i>Portfolio Site
                </a>
                <span class="text-muted" style="font-size:0.85rem;"><i class="bi bi-calendar-event me-1"></i><?= date('D, M d, Y') ?></span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 1. DASHBOARD OVERVIEW VIEW -->
        <!-- ========================================================================= -->
        <div id="view-dashboard" class="admin-view-pane">
            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card cursor-pointer" onclick="switchAdminView('users')">
                        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-value"><?= $totalAll ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card cursor-pointer" onclick="switchAdminView('users')">
                        <div class="stat-icon" style="background:rgba(66,133,244,0.12);color:#4285F4;"><i class="bi bi-google"></i></div>
                        <div>
                            <div class="stat-label">Google Accounts</div>
                            <div class="stat-value"><?= $totalGoogle ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card cursor-pointer" onclick="switchAdminView('messages')">
                        <div class="stat-icon" style="background:rgba(234,88,12,0.12);color:#ea580c;"><i class="bi bi-chat-dots-fill"></i></div>
                        <div>
                            <div class="stat-label">Total Inquiries</div>
                            <div class="stat-value"><?= $totalMsgs ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card cursor-pointer" onclick="switchAdminView('messages')">
                        <div class="stat-icon" style="background:rgba(234,179,8,0.12);color:#eab308;"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="stat-label">Pending Replies</div>
                            <div class="stat-value text-warning"><?= $pendingMsgs ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overview Quick Preview Sections -->
            <div class="row g-4">
                <!-- Recent Messages Preview -->
                <div class="col-lg-6">
                    <div class="admin-table-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-chat-left-text me-2 text-accent"></i>Recent Inquiries</h6>
                            <button class="btn btn-sm btn-outline-accent py-1 px-2" onclick="switchAdminView('messages')">View All Messages &rarr;</button>
                        </div>
                        <div class="p-0">
                            <?php if ($recentMessages && $recentMessages->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($rm = $recentMessages->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-3" style="background:transparent;border-color:var(--border-subtle);">
                                            <div class="d-flex align-items-center gap-3 text-truncate">
                                                <div class="user-avatar-sm">
                                                    <?php if (!empty($rm['user_avatar'])): ?>
                                                        <img src="<?= htmlspecialchars($rm['user_avatar']) ?>" alt="<?= htmlspecialchars($rm['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                                                    <?php else: ?>
                                                        <span><?= strtoupper(substr($rm['name'] ?: 'U', 0, 1)) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-truncate">
                                                    <div class="fw-semibold text-truncate" style="font-size:0.9rem;"><?= htmlspecialchars($rm['subject']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($rm['name']) ?> &bull; <?= date('M d, H:i', strtotime($rm['created_at'])) ?></small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="overview-msg-status-<?= $rm['id'] ?>">
                                                    <?php if ($rm['status'] === 'replied'): ?>
                                                        <span class="badge bg-success-subtle text-success rounded-pill px-2">Replied</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2">Pending</span>
                                                    <?php endif; ?>
                                                </span>
                                                <button type="button" class="btn btn-sm btn-accent py-1 px-2" onclick="openAdminReplyChat(<?= $rm['id'] ?>, <?= (int)($rm['user_id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($rm['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($rm['email']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($rm['user_avatar'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($rm['subject'] ?? ''), ENT_QUOTES) ?>')" title="Reply in Live Chat">
                                                    <i class="bi bi-chat-dots-fill me-1"></i>Reply
                                                </button>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No messages received yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Users Preview -->
                <div class="col-lg-6">
                    <div class="admin-table-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-accent"></i>Recent Registered Users</h6>
                            <button class="btn btn-sm btn-outline-accent py-1 px-2" onclick="switchAdminView('users')">View All Users &rarr;</button>
                        </div>
                        <div class="p-0">
                            <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($ru = $recentUsers->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-3" style="background:transparent;border-color:var(--border-subtle);">
                                            <div class="d-flex align-items-center gap-3 text-truncate">
                                                <div class="user-avatar-sm">
                                                    <?php if (!empty($ru['avatar'])): ?>
                                                        <img src="<?= htmlspecialchars($ru['avatar']) ?>" alt="<?= htmlspecialchars($ru['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                                                    <?php else: ?>
                                                        <span><?= strtoupper(substr($ru['name'] ?: 'U', 0, 1)) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-truncate">
                                                    <div class="fw-semibold text-truncate" style="font-size:0.9rem;"><?= htmlspecialchars($ru['name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($ru['email']) ?></small>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if (!empty($ru['google_id'])): ?>
                                                    <span class="badge bg-google-auth rounded-pill"><i class="bi bi-google me-1"></i>Google</span>
                                                <?php else: ?>
                                                    <span class="badge bg-email-auth rounded-pill"><i class="bi bi-envelope me-1"></i>Email</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No users registered yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. MESSAGES VIEW -->
        <!-- ========================================================================= -->
        <div id="view-messages" class="admin-view-pane d-none">
            <!-- Messages Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;background:rgba(234,88,12,0.12);color:#ea580c;"><i class="bi bi-inbox-fill"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Total Inquiries</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= $totalMsgs ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;background:rgba(234,179,8,0.12);color:#eab308;"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Pending</div>
                            <div class="stat-value text-warning" style="font-size:1.4rem;"><?= $pendingMsgs ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;background:rgba(34,197,94,0.12);color:#22c55e;"><i class="bi bi-check2-all"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Replied</div>
                            <div class="stat-value text-success" style="font-size:1.4rem;"><?= $repliedMsgs ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Table Card -->
            <div class="admin-table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-envelope-paper me-2 text-accent"></i>Contact Inquiries &amp; Messages</h5>
                        <small class="text-muted">Messages sent by logged-in users via the portfolio contact form</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="width:260px;">
                            <span class="input-group-text bg-transparent" style="border-color:var(--border);">
                                <i class="bi bi-search text-accent"></i>
                            </span>
                            <input type="text" 
                                   id="messageSearchInput" 
                                   class="form-control" 
                                   placeholder="Type to search messages..." 
                                   autocomplete="off" 
                                   autocorrect="off" 
                                   autocapitalize="off" 
                                   spellcheck="false">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clearMessageSearchBtn" title="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill"><?= $pendingMsgs ?> Pending</span>
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><?= $repliedMsgs ?> Replied</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="messagesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sender</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Received</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="messagesTableBody">
                            <?php if ($messages && $messages->num_rows > 0): ?>
                                <?php $mIndex = 1; while ($msg = $messages->fetch_assoc()): ?>
                                <tr id="admin-msg-row-<?= $msg['id'] ?>" class="message-row">
                                    <td style="color:var(--text-muted);"><?= $mIndex++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar-sm">
                                                <?php if (!empty($msg['user_avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($msg['user_avatar']) ?>" alt="<?= htmlspecialchars($msg['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                                                <?php else: ?>
                                                    <span><?= strtoupper(substr($msg['name'] ?: 'U', 0, 1)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="fw-semibold d-block"><?= htmlspecialchars($msg['name']) ?></span>
                                                <?php if ($msg['user_id']): ?>
                                                    <span style="font-size:0.75rem;color:var(--text-muted);"><i class="bi bi-patch-check-fill text-accent"></i> Verified Account</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color:var(--text-muted);">
                                        <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-decoration-none" style="color:inherit;">
                                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-truncate d-inline-block" style="max-width:220px;">
                                            <?= htmlspecialchars($msg['subject']) ?>
                                        </span>
                                    </td>
                                    <td id="admin-msg-status-<?= $msg['id'] ?>">
                                        <?php if ($msg['status'] === 'replied'): ?>
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="bi bi-check2-circle me-1"></i>Replied</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill"><i class="bi bi-clock me-1"></i>Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.85rem;">
                                        <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Live Chat Reply Button -->
                                            <button type="button" class="btn btn-sm btn-accent px-2 py-1" onclick="openAdminReplyChat(<?= $msg['id'] ?>, <?= (int)($msg['user_id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($msg['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['email']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['user_avatar'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['subject'] ?? ''), ENT_QUOTES) ?>')" title="Reply in Live Chat">
                                                <i class="bi bi-chat-dots-fill me-1"></i>Reply
                                            </button>
                                            <!-- Open in Email App Button -->
                                            <a href="<?= $mailToUrl ?>" class="btn btn-sm btn-outline-accent px-2 py-1" title="Open in Email App">
                                                <i class="bi bi-envelope-arrow-up"></i>
                                            </a>
                                            <!-- Delete Message Button -->
                                            <button class="btn btn-sm btn-delete-user px-2 py-1" onclick="deleteMessage(<?= $msg['id'] ?>)" title="Delete message">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- REPLY MODAL -->
                                        <div class="modal fade" id="replyModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;box-shadow:var(--card-shadow);">
                                                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                        <div>
                                                            <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-quote me-2 text-accent"></i>Message from <?= htmlspecialchars($msg['name']) ?></h5>
                                                            <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?> &bull; Received <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?></small>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body px-4 py-3">
                                                        <!-- Original Subject & Message Box -->
                                                        <div class="mb-3 p-3 rounded-3" style="background:var(--bg-primary);border:1px solid var(--border-subtle);">
                                                            <div class="fw-bold mb-1 text-accent">Subject: <?= htmlspecialchars($msg['subject']) ?></div>
                                                            <p class="mb-0" style="white-space:pre-line;color:var(--text-secondary);font-size:0.95rem;line-height:1.6;"><?= htmlspecialchars($msg['message']) ?></p>
                                                            <?php if (!empty($msg['attachment_url'])): ?>
                                                                <div class="mt-2 pt-2 border-top" style="border-color:var(--border-subtle)!important;">
                                                                    <div class="fw-semibold text-accent mb-1" style="font-size:0.8rem;"><i class="bi bi-paperclip me-1"></i>Attached File:</div>
                                                                    <?php if ($msg['attachment_type'] === 'image'): ?>
                                                                        <a href="../<?= htmlspecialchars($msg['attachment_url']) ?>" target="_blank">
                                                                            <img src="../<?= htmlspecialchars($msg['attachment_url']) ?>" class="rounded" style="max-width:220px;max-height:160px;object-fit:cover;" alt="Attachment">
                                                                        </a>
                                                                    <?php elseif ($msg['attachment_type'] === 'video'): ?>
                                                                        <video src="../<?= htmlspecialchars($msg['attachment_url']) ?>" controls class="rounded" style="max-width:280px;max-height:160px;"></video>
                                                                    <?php else: ?>
                                                                        <a href="../<?= htmlspecialchars($msg['attachment_url']) ?>" download="<?= htmlspecialchars($msg['attachment_name'] ?? 'file') ?>" class="btn btn-sm btn-outline-accent">
                                                                            <i class="bi bi-download me-1"></i><?= htmlspecialchars($msg['attachment_name'] ?? 'Download Attachment') ?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (!empty($msg['admin_reply'])): ?>
                                                            <!-- Previous Reply History -->
                                                            <div class="mb-3 p-3 rounded-3" style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.25);">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Your Previous Reply:</span>
                                                                    <small class="text-muted"><?= $msg['replied_at'] ? date('M d, Y h:i A', strtotime($msg['replied_at'])) : '' ?></small>
                                                                </div>
                                                                <p class="mb-0" style="white-space:pre-line;color:var(--text-primary);font-size:0.92rem;"><?= htmlspecialchars($msg['admin_reply']) ?></p>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Reply Form -->
                                                        <form method="POST">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="reply_message">
                                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                            <input type="hidden" name="recipient_email" value="<?= htmlspecialchars($msg['email']) ?>">
                                                            <input type="hidden" name="recipient_name" value="<?= htmlspecialchars($msg['name']) ?>">
                                                            <input type="hidden" name="original_subject" value="<?= htmlspecialchars($msg['subject']) ?>">

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold"><i class="bi bi-reply me-1 text-accent"></i>Write Reply (will be emailed to <?= htmlspecialchars($msg['email']) ?>):</label>
                                                                <textarea name="reply_content" class="form-control" rows="4" placeholder="Dear <?= htmlspecialchars($msg['name']) ?>, thank you for reaching out..." required><?= !empty($msg['admin_reply']) ? htmlspecialchars($msg['admin_reply']) : '' ?></textarea>
                                                            </div>

                                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-sm btn-accent" data-bs-dismiss="modal" onclick="openAdminReplyChat(<?= $msg['id'] ?>, <?= (int)($msg['user_id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($msg['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['email']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['user_avatar'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($msg['subject'] ?? ''), ENT_QUOTES) ?>')">
                                                                        <i class="bi bi-chat-dots-fill me-1"></i>Open in Drift Live Chat
                                                                    </button>
                                                                    <a href="<?= $mailToUrl ?>" class="btn btn-sm btn-outline-accent">
                                                                        <i class="bi bi-envelope-at me-1"></i>Mail Client
                                                                    </a>
                                                                </div>
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-sm" style="background:var(--bg-secondary);color:var(--text-muted);border:1px solid var(--border);" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-sm btn-accent px-3">
                                                                        <i class="bi bi-send-fill me-1"></i>Send Response via Email
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5" style="color:var(--text-muted);">
                                        <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                                        No contact inquiries received yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 3. USERS VIEW -->
        <!-- ========================================================================= -->
        <div id="view-users" class="admin-view-pane d-none">
            <!-- Users Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Total Users</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= $totalAll ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;background:rgba(66,133,244,0.12);color:#4285F4;"><i class="bi bi-google"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Google Accounts</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= $totalGoogle ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card py-3">
                        <div class="stat-icon" style="width:44px;height:44px;font-size:1.2rem;background:rgba(251,191,36,0.12);color:#fbbf24;"><i class="bi bi-shield-fill"></i></div>
                        <div>
                            <div class="stat-label" style="font-size:0.75rem;">Admins</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= $totalAdmins ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table Card with Fixed Auto-fill Search -->
            <div class="admin-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-people me-2 text-accent"></i>Registered Users</h5>
                    <!-- Automatic Live Search Box -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width:260px;">
                            <span class="input-group-text bg-transparent" style="border-color:var(--border);">
                                <i class="bi bi-search text-accent"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   id="userSearchInput" 
                                   class="form-control" 
                                   placeholder="Type to search users..." 
                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                                   autocomplete="off" 
                                   autocorrect="off" 
                                   autocapitalize="off" 
                                   spellcheck="false">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clearUserSearchBtn" title="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Auth Type</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php $i = 1; while ($u = $users->fetch_assoc()): ?>
                                <tr class="user-row">
                                    <td style="color:var(--text-muted);"><?= $i++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar-sm">
                                                <?php if (!empty($u['avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($u['avatar']) ?>" alt="<?= htmlspecialchars($u['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                                                <?php else: ?>
                                                    <span><?= strtoupper(substr($u['name'] ?: 'U', 0, 1)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="fw-medium"><?= htmlspecialchars($u['name']) ?></span>
                                        </div>
                                    </td>
                                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if (!empty($u['google_id'])): ?>
                                            <span class="badge bg-google-auth"><i class="bi bi-google me-1"></i>Google</span>
                                        <?php else: ?>
                                            <span class="badge bg-email-auth"><i class="bi bi-envelope-fill me-1"></i>Email</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge badge-role-admin px-2 py-1 rounded-pill">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-role-user px-2 py-1 rounded-pill">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted);">
                                        <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-delete-user"
                                            onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5" style="color:var(--text-muted);">
                                        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                                        <?= !empty($search) ? 'No users found for "' . htmlspecialchars($search) . '"' : 'No users registered yet.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// SPA-like View / Function Switcher for Sidebar
function switchAdminView(viewName) {
    const validViews = ['dashboard', 'messages', 'users'];
    if (!validViews.includes(viewName)) {
        viewName = 'dashboard';
    }

    // Hide all view panes
    document.querySelectorAll('.admin-view-pane').forEach(pane => {
        pane.classList.add('d-none');
    });

    // Show target view pane
    const targetPane = document.getElementById('view-' + viewName);
    if (targetPane) {
        targetPane.classList.remove('d-none');
    }

    // Update active nav link
    document.querySelectorAll('.nav-view-link').forEach(link => {
        if (link.getAttribute('data-view') === viewName) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Update Title in Top Bar
    const titleMap = {
        'dashboard': 'Dashboard Overview',
        'messages': 'Messages & Contact Inquiries',
        'users': 'Registered Users Management'
    };
    const titleEl = document.getElementById('currentViewTitle');
    if (titleEl && titleMap[viewName]) {
        titleEl.textContent = titleMap[viewName];
    }

    // Update URL hash without reload
    window.location.hash = viewName;

    // Close mobile sidebar if open
    const adminSidebar = document.getElementById('adminSidebar');
    if (adminSidebar) {
        adminSidebar.classList.remove('show-mobile');
    }
}

// Attach click handlers to sidebar navigation links
document.querySelectorAll('.nav-view-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const view = this.getAttribute('data-view');
        switchAdminView(view);
    });
});

// Initialize active view on page load
document.addEventListener('DOMContentLoaded', () => {
    let hash = window.location.hash.replace('#', '').toLowerCase();
    if (hash === 'users-section' || hash === 'user') hash = 'users';
    if (hash === 'messages-section' || hash === 'message') hash = 'messages';

    const initialView = hash || '<?= $defaultTab ?>' || 'dashboard';
    switchAdminView(initialView);

    // Initial filter if input already has a value
    if (userSearchInput && userSearchInput.value.trim() !== '') {
        filterUsersTable();
    }
    if (messageSearchInput && messageSearchInput.value.trim() !== '') {
        filterMessagesTable();
    }
});

// =========================================================================
// AUTOMATIC LIVE SEARCH (USERS & MESSAGES)
// Just enter text and search automatically in real-time
// =========================================================================
function safeEscape(str) {
    if (!str) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(str).replace(/[&<>"']/g, m => map[m]);
}

// 1. Users Live Search
const userSearchInput = document.getElementById('userSearchInput');
const clearUserSearchBtn = document.getElementById('clearUserSearchBtn');
const usersTableBody = document.getElementById('usersTableBody');

function filterUsersTable() {
    if (!userSearchInput || !usersTableBody) return;
    const query = userSearchInput.value.trim().toLowerCase();

    // Toggle clear button
    if (clearUserSearchBtn) {
        if (query.length > 0) {
            clearUserSearchBtn.classList.remove('d-none');
        } else {
            clearUserSearchBtn.classList.add('d-none');
        }
    }

    const rows = usersTableBody.querySelectorAll('tr.user-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = (row.textContent || '').toLowerCase();
        if (!query || text.includes(query)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let noMatchRow = document.getElementById('noUsersFoundRow');
    if (visibleCount === 0 && rows.length > 0) {
        if (!noMatchRow) {
            noMatchRow = document.createElement('tr');
            noMatchRow.id = 'noUsersFoundRow';
            usersTableBody.appendChild(noMatchRow);
        }
        noMatchRow.innerHTML = `
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-search fs-3 d-block mb-2 text-accent"></i>
                <div>No users found matching "<strong>${safeEscape(query)}</strong>"</div>
                <button type="button" class="btn btn-sm btn-outline-accent mt-2" onclick="clearUserSearch()">Clear Search</button>
            </td>
        `;
        noMatchRow.style.display = '';
    } else if (noMatchRow) {
        noMatchRow.style.display = 'none';
    }
}

function clearUserSearch() {
    if (userSearchInput) {
        userSearchInput.value = '';
        filterUsersTable();
        userSearchInput.focus();
    }
}

if (userSearchInput) {
    userSearchInput.addEventListener('input', filterUsersTable);
    userSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            clearUserSearch();
        } else if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
}
if (clearUserSearchBtn) {
    clearUserSearchBtn.addEventListener('click', clearUserSearch);
}

// 2. Messages Live Search
const messageSearchInput = document.getElementById('messageSearchInput');
const clearMessageSearchBtn = document.getElementById('clearMessageSearchBtn');
const messagesTableBody = document.getElementById('messagesTableBody');

function filterMessagesTable() {
    if (!messageSearchInput || !messagesTableBody) return;
    const query = messageSearchInput.value.trim().toLowerCase();

    // Toggle clear button
    if (clearMessageSearchBtn) {
        if (query.length > 0) {
            clearMessageSearchBtn.classList.remove('d-none');
        } else {
            clearMessageSearchBtn.classList.add('d-none');
        }
    }

    const rows = messagesTableBody.querySelectorAll('tr.message-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = (row.textContent || '').toLowerCase();
        if (!query || text.includes(query)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let noMatchRow = document.getElementById('noMessagesFoundRow');
    if (visibleCount === 0 && rows.length > 0) {
        if (!noMatchRow) {
            noMatchRow = document.createElement('tr');
            noMatchRow.id = 'noMessagesFoundRow';
            messagesTableBody.appendChild(noMatchRow);
        }
        noMatchRow.innerHTML = `
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-search fs-3 d-block mb-2 text-accent"></i>
                <div>No inquiries found matching "<strong>${safeEscape(query)}</strong>"</div>
                <button type="button" class="btn btn-sm btn-outline-accent mt-2" onclick="clearMessageSearch()">Clear Search</button>
            </td>
        `;
        noMatchRow.style.display = '';
    } else if (noMatchRow) {
        noMatchRow.style.display = 'none';
    }
}

function clearMessageSearch() {
    if (messageSearchInput) {
        messageSearchInput.value = '';
        filterMessagesTable();
        messageSearchInput.focus();
    }
}

if (messageSearchInput) {
    messageSearchInput.addEventListener('input', filterMessagesTable);
    messageSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            clearMessageSearch();
        } else if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
}
if (clearMessageSearchBtn) {
    clearMessageSearchBtn.addEventListener('click', clearMessageSearch);
}

// Theme-Aware Logout Confirmation
function confirmLogout() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    Swal.fire({
        title: 'Sign Out?',
        text: 'Are you sure you want to log out of the Admin Panel?',
        icon: 'question',
        background: isLight ? '#ffffff' : '#0f0f1a',
        color: isLight ? '#0f172a' : '#e2e8f0',
        showCancelButton: true,
        confirmButtonColor: '#6c63ff',
        cancelButtonColor: isLight ? '#cbd5e1' : '#374151',
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i>Logout',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../logout.php';
        }
    });
}

const adminCsrfToken = <?= json_encode(getCsrfToken()) ?>;

function postAdminAction(action, fields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin.php';
    
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = 'csrf_token';
    tokenInput.value = adminCsrfToken;
    form.appendChild(tokenInput);

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);

    if (fields) {
        for (const [key, val] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

function deleteUser(id, name) {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    Swal.fire({
        title: 'Delete User?',
        html: `Are you sure you want to delete <b>${name}</b>? This cannot be undone.`,
        icon: 'warning',
        background: isLight ? '#ffffff' : '#0f0f1a',
        color: isLight ? '#0f172a' : '#e2e8f0',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: isLight ? '#cbd5e1' : '#374151',
        confirmButtonText: '<i class="bi bi-trash me-1"></i>Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            postAdminAction('delete_user', { user_id: id });
        }
    });
}

function deleteMessage(id) {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    Swal.fire({
        title: 'Delete Message?',
        html: `Are you sure you want to delete this message inquiry?`,
        icon: 'warning',
        background: isLight ? '#ffffff' : '#0f0f1a',
        color: isLight ? '#0f172a' : '#e2e8f0',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: isLight ? '#cbd5e1' : '#374151',
        confirmButtonText: '<i class="bi bi-trash me-1"></i>Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            postAdminAction('delete_message', { message_id: id });
        }
    });
}

// Mobile sidebar toggle handler
const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
const adminSidebar = document.getElementById('adminSidebar');
if (sidebarToggleBtn && adminSidebar) {
    sidebarToggleBtn.addEventListener('click', () => {
        adminSidebar.classList.toggle('show-mobile');
    });
}

<?php if ($flashMsg): ?>
const isLightMsg = document.documentElement.getAttribute('data-theme') === 'light';
Swal.fire({
    icon: '<?= $flashType ?>',
    title: '<?= $flashType === 'success' ? 'Success' : 'Notice' ?>',
    text: '<?= addslashes($flashMsg) ?>',
    background: isLightMsg ? '#ffffff' : '#0f0f1a',
    color: isLightMsg ? '#0f172a' : '#e2e8f0',
    confirmButtonColor: '#6c63ff'
});
<?php endif; ?>
</script>
