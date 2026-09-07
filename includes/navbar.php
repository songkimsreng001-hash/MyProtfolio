<?php
// includes/navbar.php
require_once __DIR__ . '/auth.php';
$user = getCurrentUser();

// Use PHP_SELF to detect subfolder
$prefix = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../' : '';
?>
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $prefix ?>index.php">
            Kimsreng<span class="text-accent">.</span>
        </a>
        
        <div class="d-flex align-items-center gap-2 order-lg-3">
            <?php if ($user): ?>
                <!-- Notification Bell Dropdown -->
                <div class="dropdown position-relative me-1" id="notificationDropdownContainer">
                    <button class="btn btn-nav-icon position-relative" id="notificationBellBtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <span class="notification-badge d-none" id="navbarNotificationBadge">0</span>
                        <span class="notification-pulse d-none" id="navbarNotificationPulse"></span>
                    </button>
                    
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu shadow-lg p-0" aria-labelledby="notificationBellBtn">
                        <div class="notification-dropdown-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-accent"><i class="bi bi-bell me-1"></i>Notifications</span>
                                <span class="badge bg-danger rounded-pill d-none" id="notifDropdownBadge">0 new</span>
                            </div>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" id="markAllReadBtn" style="font-size:0.75rem;">
                                Mark all read
                            </button>
                        </div>
                        
                        <!-- Notification Summary Header -->
                        <div class="px-3 py-2 bg-body-tertiary border-bottom d-flex align-items-center justify-content-between" style="font-size:0.78rem;">
                            <span class="text-muted" id="notifStatusSummary">Checking updates...</span>
                            <a href="javascript:void(0)" onclick="openChatWidget()" class="text-accent text-decoration-none fw-semibold">
                                <i class="bi bi-chat-dots me-1"></i>Open Chat
                            </a>
                        </div>
                        
                        <!-- Notification List Stream -->
                        <div class="notification-list-stream" id="notificationListStream">
                            <div class="text-center py-4 text-muted" style="font-size:0.82rem;">
                                <div class="spinner-border spinner-border-sm text-accent mb-2"></div>
                                <div>Loading notifications...</div>
                            </div>
                        </div>

                        <div class="notification-dropdown-footer text-center p-2 border-top">
                            <a href="javascript:void(0)" onclick="openChatWidget()" class="btn btn-accent btn-sm w-100 py-1" style="font-size:0.8rem;">
                                <i class="bi bi-chat-text-fill me-1"></i>Chat with Admin
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dark / Light Mode Toggle Button -->
            <button class="btn btn-theme-toggle" id="themeToggleBtn" type="button" aria-label="Toggle light or dark theme" title="Toggle theme">
                <i class="bi bi-sun-fill theme-icon-sun"></i>
                <i class="bi bi-moon-stars-fill theme-icon-moon"></i>
            </button>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>index.php#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>index.php#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>index.php#skills">Skills</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>index.php#projects">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>index.php#contact">Contact</a></li>
            </ul>
            <ul class="navbar-nav align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 profile-toggle"
                           href="#" id="profileDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="profile-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="profile-avatar-img" referrerpolicy="no-referrer">
                                <?php else: ?>
                                    <span><?= strtoupper(substr($user['name'] ?: 'U', 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-lg-inline user-display-name"><?= htmlspecialchars($user['name']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
                            <li class="dropdown-header px-3 py-2">
                                <div class="fw-semibold text-truncate"><?= htmlspecialchars($user['name']) ?></div>
                                <small class="text-muted text-truncate d-block"><?= htmlspecialchars($user['email']) ?></small>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-accent mt-1">Admin</span>
                                <?php endif; ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($user['role'] === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $prefix ?>dashboard/admin.php">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $prefix ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-accent btn-sm px-3" href="<?= $prefix ?>register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
function confirmLogout() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to sign out?',
        icon: 'question',
        background: isLight ? '#ffffff' : '#0f0f1a',
        color: isLight ? '#0f172a' : '#e2e8f0',
        showCancelButton: true,
        confirmButtonColor: '#6c63ff',
        cancelButtonColor: isLight ? '#cbd5e1' : '#374151',
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i>Yes, logout',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= $prefix ?>logout.php';
        }
    });
}
</script>