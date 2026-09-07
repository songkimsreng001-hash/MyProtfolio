// assets/js/main.js

// ===== THEME MANAGER (DARK / LIGHT MODE) =====
function initTheme() {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    
    // Function to get current theme
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    // Function to set theme
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('portfolio_theme', theme);
        
        // Update GIS button theme if present
        const gisContainer = document.querySelector('.g_id_signin');
        if (gisContainer) {
            gisContainer.setAttribute('data-theme', theme === 'light' ? 'outline' : 'filled_black');
        }
    }

    // Bind all theme toggle buttons on the page (navbar or sidebar)
    document.querySelectorAll('#themeToggleBtn, .btn-theme-toggle').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const current = getCurrentTheme();
            const nextTheme = current === 'dark' ? 'light' : 'dark';
            setTheme(nextTheme);
        });
    });
}

// Initialize theme on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}

// ===== HELPER FOR THEME-AWARE SWEETALERT2 =====
function getSwalThemeOptions() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    return {
        background: isLight ? '#ffffff' : '#12132b',
        color: isLight ? '#0f172a' : '#f1f5f9',
        confirmButtonColor: '#6c63ff',
        cancelButtonColor: isLight ? '#cbd5e1' : '#374151'
    };
}

// ===== NAVBAR SCROLL EFFECT =====
window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (nav) {
        if (window.scrollY > 50) {
            nav.style.padding = '8px 0';
        } else {
            nav.style.padding = '14px 0';
        }
    }
});

// ===== FADE-UP OBSERVER =====
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

// ===== SKILL BAR ANIMATION =====
const skillObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('.skill-bar-fill').forEach(bar => {
                bar.style.width = bar.dataset.width;
            });
        }
    });
}, { threshold: 0.3 });

const skillSection = document.getElementById('skills');
if (skillSection) skillObserver.observe(skillSection);

// ===== ACTIVE NAV LINK ON SCROLL =====
const sections = document.querySelectorAll('section[id]');
window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;
    sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        const sectionHeight = section.clientHeight;
        const id = section.getAttribute('id');
        const link = document.querySelector(`.nav-link[href*="#${id}"]`);
        if (link) {
            if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        }
    });
});

// ===== CONTACT FORM SUBMIT =====
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('sendMsgBtn') || this.querySelector('button[type="submit"]');
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending to Admin...';

        const formData = new FormData(this);

        fetch('contact-send.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const swalTheme = getSwalThemeOptions();
            btn.disabled = false;
            btn.innerHTML = originalBtnText;

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Message Sent!',
                    text: data.message || 'Your message has been delivered to Admin.',
                    background: swalTheme.background,
                    color: swalTheme.color,
                    confirmButtonColor: swalTheme.confirmButtonColor,
                    confirmButtonText: 'Great!'
                });
                contactForm.reset();
            } else if (data.code === 'auth_required') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Authentication Required',
                    text: data.message || 'Please log in or register to contact the Admin.',
                    showCancelButton: true,
                    confirmButtonText: 'Login Now',
                    cancelButtonText: 'Cancel',
                    background: swalTheme.background,
                    color: swalTheme.color,
                    confirmButtonColor: swalTheme.confirmButtonColor
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: data.message || 'Could not send message. Please try again.',
                    background: swalTheme.background,
                    color: swalTheme.color,
                    confirmButtonColor: swalTheme.confirmButtonColor
                });
            }
        })
        .catch(err => {
            const swalTheme = getSwalThemeOptions();
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not connect to the server. Please try again.',
                background: swalTheme.background,
                color: swalTheme.color,
                confirmButtonColor: swalTheme.confirmButtonColor
            });
        });
    });
}

// ===== NOTIFICATION & CHAT API HELPERS =====
function getApiUrl(endpoint) {
    const isDashboard = window.location.pathname.includes('/dashboard/');
    return (isDashboard ? '../api/' : 'api/') + endpoint;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

let isChatOpen = false;
let pollingInterval = null;

// ===== NOTIFICATIONS MANAGER =====
function fetchNotifications() {
    fetch(getApiUrl('notifications.php'))
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success' || !data.logged_in) {
                return;
            }

            const unreadCount = data.unread_count || 0;
            const notRepliedCount = data.not_replied_count || 0;
            const unreadReplies = data.unread_replies_count || 0;
            const notifications = data.notifications || [];

            // 1. Update Navbar Notification Badge
            const navBadge = document.getElementById('navbarNotificationBadge');
            const navPulse = document.getElementById('navbarNotificationPulse');
            const dropBadge = document.getElementById('notifDropdownBadge');
            const statusSummary = document.getElementById('notifStatusSummary');

            if (navBadge) {
                if (unreadCount > 0) {
                    navBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    navBadge.classList.remove('d-none');
                    if (navPulse) navPulse.classList.remove('d-none');
                } else {
                    navBadge.classList.add('d-none');
                    if (navPulse) navPulse.classList.add('d-none');
                }
            }

            if (dropBadge) {
                if (unreadCount > 0) {
                    dropBadge.textContent = `${unreadCount} unread`;
                    dropBadge.classList.remove('d-none');
                } else {
                    dropBadge.classList.add('d-none');
                }
            }

            if (statusSummary) {
                if (data.user_role === 'admin') {
                    statusSummary.innerHTML = notRepliedCount > 0 
                        ? `<span class="text-warning fw-bold"><i class="bi bi-clock-history"></i> ${notRepliedCount} not replied yet</span>`
                        : `<span class="text-success"><i class="bi bi-check-circle-fill"></i> All inquiries replied</span>`;
                } else {
                    if (unreadReplies > 0) {
                        statusSummary.innerHTML = `<span class="text-success fw-bold"><i class="bi bi-chat-quote-fill"></i> ${unreadReplies} New Reply from Admin</span>`;
                    } else if (notRepliedCount > 0) {
                        statusSummary.innerHTML = `<span class="text-warning"><i class="bi bi-clock-history"></i> ${notRepliedCount} waiting for Admin reply</span>`;
                    } else {
                        statusSummary.innerHTML = `<span class="text-success"><i class="bi bi-check-all"></i> All messages replied</span>`;
                    }
                }
            }

            // 2. Update Floating Chat Badge (Picture 1)
            const chatFloatingBadge = document.getElementById('chatFloatingBadge');
            const chatPulseRing = document.getElementById('chatPulseRing');
            if (chatFloatingBadge) {
                if (unreadCount > 0) {
                    chatFloatingBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    chatFloatingBadge.classList.remove('d-none');
                    if (chatPulseRing) chatPulseRing.classList.remove('d-none');
                } else {
                    chatFloatingBadge.classList.add('d-none');
                    if (chatPulseRing) chatPulseRing.classList.add('d-none');
                }
            }

            // 3. Render Navbar Notification List Stream
            const stream = document.getElementById('notificationListStream');
            if (stream) {
                if (notifications.length === 0) {
                    stream.innerHTML = `
                        <div class="text-center py-4 px-3 text-muted" style="font-size:0.85rem;">
                            <i class="bi bi-bell-slash fs-3 d-block mb-2 text-muted opacity-50"></i>
                            <div>No notifications yet</div>
                            <div style="font-size:0.75rem;">Messages and Admin replies will appear here.</div>
                        </div>
                    `;
                } else {
                    let html = '';
                    notifications.forEach(item => {
                        const isReply = item.type === 'admin_reply';
                        const iconClass = isReply ? 'reply' : 'pending';
                        const icon = isReply ? 'bi-chat-left-quote-fill' : 'bi-clock-history';
                        
                        html += `
                            <div class="notification-item ${item.is_unread ? 'unread' : ''}" onclick="handleNotificationClick(${item.id})">
                                <div class="notification-item-icon ${iconClass}">
                                    <i class="bi ${icon}"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <strong style="font-size:0.82rem;" class="text-truncate">${escapeHtml(item.title)}</strong>
                                        <small class="text-muted ms-1" style="font-size:0.7rem;">${item.time_ago}</small>
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size:0.77rem;">${escapeHtml(item.preview)}</div>
                                </div>
                            </div>
                        `;
                    });
                    stream.innerHTML = html;
                }
            }
        })
        .catch(err => {
            console.error('Error fetching notifications:', err);
        });
}

function markAllNotificationsRead() {
    fetch(getApiUrl('notifications.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_all_read'
    })
    .then(res => res.json())
    .then(data => {
        fetchNotifications();
    });
}

function handleNotificationClick(msgId) {
    fetch(getApiUrl('notifications.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_read&message_id=' + msgId
    }).then(() => {
        fetchNotifications();
        openChatWidget(msgId);
    });
}

// ===== CHAT WIDGET CONTROLLER =====
window.currentUserIsAdmin = document.body.classList.contains('is-admin') || false;
window.adminActiveChatTarget = null;

window.openChatWidget = function(replyMsgId = null, snippet = '') {
    // Hide open bootstrap dropdowns if any
    const notifBtn = document.getElementById('notificationBellBtn');
    if (notifBtn && typeof bootstrap !== 'undefined') {
        const bsDropdown = bootstrap.Dropdown.getInstance(notifBtn);
        if (bsDropdown) {
            bsDropdown.hide();
        }
    }

    const chatBox = document.getElementById('chatWidgetBox');
    if (!chatBox) {
        console.warn('chatWidgetBox not found in DOM');
        return;
    }

    if (chatBox.getAttribute('data-is-admin') === '1' || document.body.classList.contains('is-admin')) {
        window.currentUserIsAdmin = true;
    }

    if (chatBox.classList.contains('active')) {
        return; // Already open, prevent duplicate calls
    }

    chatBox.classList.add('active');
    chatBox.setAttribute('aria-hidden', 'false');
    isChatOpen = true;

    // Hide floating button while chat window is active (admin never shows floating container)
    document.getElementById('chatFloatingContainer')?.classList.add('d-none');

    fetchChatMessages(true);
    fetchNotifications();

    setTimeout(() => {
        if (replyMsgId) {
            prepareChatReply(replyMsgId, snippet);
        } else {
            document.getElementById('chatMessageInput')?.focus();
        }
    }, 200);
};

// Admin opening chat to reply to a specific user inquiry
window.openAdminReplyChat = function(msgId, userId, name, email, avatar, subject) {
    window.currentUserIsAdmin = true;
    window.adminActiveChatTarget = {
        msgId: msgId,
        userId: userId || 0,
        name: name || 'User',
        email: email || '',
        avatar: avatar || null,
        subject: subject || ''
    };

    // Set hidden inputs
    const replyIdInput = document.getElementById('chatReplyToId');
    if (replyIdInput) replyIdInput.value = msgId || '';

    const targetUserIdInput = document.getElementById('chatTargetUserId');
    if (targetUserIdInput) targetUserIdInput.value = userId || '';

    const targetEmailInput = document.getElementById('chatTargetEmail');
    if (targetEmailInput) targetEmailInput.value = email || '';

    // Update Drift Chat Header to show the Client / User details
    const headerTitle = document.getElementById('driftHeaderTitle');
    const headerSubtitle = document.getElementById('driftHeaderSubtitle');
    const headerAvatarWrap = document.getElementById('driftHeaderAvatarWrap');
    const headerMailLink = document.getElementById('driftHeaderMailLink');
    const headerCallLink = document.getElementById('driftHeaderCallLink');
    const headerVideoBtn = document.getElementById('driftHeaderVideoBtn');

    if (headerTitle) headerTitle.textContent = name || 'User Inquiry';
    if (headerSubtitle) headerSubtitle.textContent = (email || '') + ' • Client Inquiry';
    if (headerMailLink) {
        headerMailLink.href = 'mailto:' + encodeURIComponent(email || '');
        headerMailLink.title = 'Email ' + (name || 'User');
    }
    if (headerVideoBtn) headerVideoBtn.classList.add('d-none');
    if (headerCallLink) headerCallLink.classList.add('d-none');

    if (headerAvatarWrap) {
        if (avatar) {
            const prefix = window.location.pathname.includes('/dashboard/') ? '../' : '';
            const fullAvatar = avatar.startsWith('http') || avatar.startsWith('../') ? avatar : prefix + avatar;
            headerAvatarWrap.innerHTML = `<img src="${fullAvatar}" alt="${escapeHtml(name)}" class="drift-header-avatar-img"><span class="drift-status-online"></span>`;
        } else {
            const initial = (name || 'U').charAt(0).toUpperCase();
            headerAvatarWrap.innerHTML = `<div class="d-flex align-items-center justify-content-center bg-white text-primary rounded-circle fw-bold" style="width:38px;height:38px;font-size:1rem;">${initial}</div><span class="drift-status-online"></span>`;
        }
    }

    // Hide welcome bot choices in admin reply mode
    document.getElementById('driftQuickChoices')?.classList.add('d-none');
    document.getElementById('driftWelcomeRow')?.classList.add('d-none');

    // Update reply banner
    const replyBanner = document.getElementById('chatReplyBanner');
    const replyText = document.getElementById('chatReplyText');
    if (replyBanner && replyText) {
        replyText.textContent = `Replying to ${name || 'User'} (${subject || 'Inquiry'})`;
        replyBanner.classList.remove('d-none');
    }

    // Update input placeholder
    const msgInput = document.getElementById('chatMessageInput');
    if (msgInput) {
        msgInput.placeholder = `Reply to ${name || 'User'}...`;
    }

    // Open chat popup
    const chatBox = document.getElementById('chatWidgetBox');
    if (chatBox) {
        chatBox.classList.add('active');
        chatBox.setAttribute('aria-hidden', 'false');
        isChatOpen = true;
    }

    // Fetch conversation with this specific user
    fetchChatMessages(true, window.adminActiveChatTarget);

    setTimeout(() => {
        msgInput?.focus();
    }, 200);
};

window.closeChatWidget = function(e) {
    if (e && typeof e.stopPropagation === 'function') {
        e.stopPropagation();
    }
    const chatBox = document.getElementById('chatWidgetBox');
    if (!chatBox) return;

    chatBox.classList.remove('active');
    chatBox.setAttribute('aria-hidden', 'true');
    isChatOpen = false;

    // Restore floating button ONLY if user is NOT admin
    if (!window.currentUserIsAdmin && !document.body.classList.contains('is-admin')) {
        document.getElementById('chatFloatingContainer')?.classList.remove('d-none');
    }
};

window.toggleChatWidget = function(e) {
    if (e && typeof e.stopPropagation === 'function') {
        e.stopPropagation();
    }
    const chatBox = document.getElementById('chatWidgetBox');
    if (!chatBox) return;

    if (chatBox.classList.contains('active')) {
        closeChatWidget(e);
    } else {
        openChatWidget();
    }
};

window.prepareChatReply = function(msgId, snippet) {
    const replyBanner = document.getElementById('chatReplyBanner');
    const replyText = document.getElementById('chatReplyText');
    const replyIdInput = document.getElementById('chatReplyToId');
    const msgInput = document.getElementById('chatMessageInput');

    if (replyBanner && replyText && replyIdInput) {
        replyIdInput.value = msgId || '';
        const targetName = window.adminActiveChatTarget?.name || 'Admin Kimsreng';
        replyText.textContent = snippet ? `Replying: "${snippet.substring(0, 30)}..."` : `Replying to ${targetName}`;
        replyBanner.classList.remove('d-none');
    }

    if (msgInput) {
        msgInput.focus();
        const targetName = window.adminActiveChatTarget?.name || 'Admin Kimsreng';
        msgInput.placeholder = `Type reply to ${targetName}...`;
    }

    scrollChatToBottom();
};

window.cancelChatReply = function() {
    const replyBanner = document.getElementById('chatReplyBanner');
    const replyIdInput = document.getElementById('chatReplyToId');
    const msgInput = document.getElementById('chatMessageInput');

    if (replyBanner && replyIdInput) {
        replyIdInput.value = '';
        replyBanner.classList.add('d-none');
    }

    if (msgInput) {
        if (window.adminActiveChatTarget) {
            msgInput.placeholder = `Reply to ${window.adminActiveChatTarget.name}...`;
        } else {
            msgInput.placeholder = 'Type your message...';
        }
    }
};

window.selectChatOption = function(optionText) {
    const msgInput = document.getElementById('chatMessageInput');
    const form = document.getElementById('chatSendForm');
    if (msgInput && form) {
        msgInput.value = optionText;
        form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
};

const QUICK_REACTION_EMOJIS = ['❤️', '👍', '🔥', '😂', '😮', '😢', '🎉'];

window.switchEmojiCategory = function(catName, btn) {
    if (!catName) catName = 'all';
    catName = String(catName).toLowerCase().trim();

    if (catName === 'smile' || catName === 'smiley') catName = 'smileys';
    if (catName === 'gesture') catName = 'gestures';
    if (catName === 'heart') catName = 'hearts';

    // 1. Update active tab styling
    const tabs = document.querySelectorAll('.drift-emoji-tab');
    tabs.forEach(function(t) {
        const tCat = (t.getAttribute('data-cat') || '').toLowerCase().trim();
        const isMatch = (t === btn) || (tCat === catName) || (catName === 'smileys' && (tCat === 'smile' || tCat === 'smileys'));
        if (isMatch) {
            t.classList.add('active');
        } else {
            t.classList.remove('active');
        }
    });

    // 2. Filter category blocks
    const blocks = document.querySelectorAll('.drift-emoji-category-block');
    blocks.forEach(function(block) {
        const bCat = (block.getAttribute('data-cat') || '').toLowerCase().trim();
        const shouldShow = (catName === 'all') || (bCat === catName) || (catName === 'smileys' && (bCat === 'smile' || bCat === 'smileys'));

        if (shouldShow) {
            block.classList.remove('d-none');
            block.style.setProperty('display', 'block', 'important');
            const grid = block.querySelector('.drift-emoji-grid');
            if (grid) {
                grid.classList.remove('d-none');
                grid.style.setProperty('display', 'grid', 'important');
            }
            const header = block.querySelector('.drift-emoji-cat-header');
            if (header) {
                header.classList.remove('d-none');
                header.style.setProperty('display', 'block', 'important');
            }
        } else {
            block.classList.add('d-none');
            block.style.setProperty('display', 'none', 'important');
        }
    });

    // 3. Scroll container to top
    const content = document.querySelector('.drift-emoji-content');
    if (content) {
        content.scrollTop = 0;
    }
};

window.insertEmoji = function(emoji) {
    const msgInput = document.getElementById('chatMessageInput');
    if (msgInput) {
        const start = msgInput.selectionStart ?? msgInput.value.length;
        const end = msgInput.selectionEnd ?? msgInput.value.length;
        const val = msgInput.value;
        msgInput.value = val.substring(0, start) + emoji + val.substring(end);
        const newPos = start + emoji.length;
        msgInput.focus();
        try {
            msgInput.setSelectionRange(newPos, newPos);
        } catch (err) {}
    }
};

window.toggleEmojiMenu = function() {
    const bar = document.getElementById('chatEmojiBar');
    if (bar) {
        bar.classList.toggle('d-none');
    }
    document.getElementById('chatAttachmentMenu')?.classList.add('d-none');
};

window.toggleReactionMenu = function(msgId, target = 'msg', event = null) {
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }

    const targetBarId = `reaction-bar-${target}-${msgId}`;
    const targetBar = document.getElementById(targetBarId);

    // Close any other open reaction bars
    document.querySelectorAll('.drift-msg-reaction-bar').forEach(b => {
        if (b.id !== targetBarId) {
            b.classList.add('d-none');
        }
    });

    if (targetBar) {
        targetBar.classList.toggle('d-none');
    }
};

function renderReactionPillsHtml(msgId, target, reactionsList) {
    let pillsHtml = '';
    const reactions = Array.isArray(reactionsList) ? reactionsList : [];
    reactions.forEach(rx => {
        pillsHtml += `
            <button type="button" class="drift-reaction-pill ${rx.user_reacted ? 'active' : ''}" onclick="reactToMessage(${msgId}, '${rx.emoji}', '${target}', event)" title="${rx.emoji} ${rx.count}">
                <span>${rx.emoji}</span>
                <span class="drift-reaction-count">${rx.count}</span>
            </button>
        `;
    });
    const addBtnHiddenClass = reactions.length === 0 ? 'd-none' : '';
    pillsHtml += `
        <button type="button" class="drift-reaction-pill drift-add-reaction-pill ${addBtnHiddenClass}" onclick="toggleReactionMenu(${msgId}, '${target}', event)" title="Add reaction">
            <i class="bi bi-plus-lg"></i>
        </button>
    `;
    return pillsHtml;
}

function renderReactionBarHtml(msgId, target) {
    let btns = '';
    QUICK_REACTION_EMOJIS.forEach(em => {
        btns += `<button type="button" class="drift-reaction-bar-btn" onclick="reactToMessage(${msgId}, '${em}', '${target}', event)" title="${em}">${em}</button>`;
    });
    return `<div class="drift-msg-reaction-bar d-none" id="reaction-bar-${target}-${msgId}">${btns}</div>`;
}

window.reactToMessage = function(msgId, emoji, target = 'msg', event = null) {
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }

    // Close reaction bar immediately
    const bar = document.getElementById(`reaction-bar-${target}-${msgId}`);
    if (bar) {
        bar.classList.add('d-none');
    }

    const container = document.getElementById(`reactions-${target}-${msgId}`);

    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('message_id', msgId);
    formData.append('emoji', emoji);
    formData.append('target', target);

    fetch(getApiUrl('chat.php'), {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && container) {
            const list = data.reactions && data.reactions[target] ? data.reactions[target] : [];
            container.innerHTML = renderReactionPillsHtml(msgId, target, list);
        } else if (data.code === 'auth_required') {
            const swalTheme = getSwalThemeOptions();
            Swal.fire({
                icon: 'warning',
                title: 'Sign in to React',
                text: 'Please log in to react to messages.',
                showCancelButton: true,
                confirmButtonText: 'Login Now',
                cancelButtonText: 'Cancel',
                background: swalTheme.background,
                color: swalTheme.color,
                confirmButtonColor: swalTheme.confirmButtonColor
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = (window.location.pathname.includes('/dashboard/') ? '../' : '') + 'login.php';
                }
            });
        }
    })
    .catch(err => {
        console.error('Error toggling reaction:', err);
    });
};

window.toggleDriftOptions = function() {
    const menu = document.getElementById('driftOptionsMenu');
    if (menu) {
        menu.classList.toggle('d-none');
    }
};

window.toggleAdminProfileMenu = function() {
    toggleDriftOptions();
};

window.startVideoCall = function() {
    Swal.fire({
        title: 'Video Call',
        html: `
            <div class="py-2 text-center">
                <div class="spinner-grow text-primary mb-3" style="width:3rem;height:3rem;"></div>
                <h6 class="text-dark mb-2">Connecting with Admin Kimsreng Song...</h6>
                <p class="text-muted small">You can also call directly via phone</p>
                <a href="tel:087859728" class="btn btn-success btn-sm mt-2"><i class="bi bi-telephone-fill me-1"></i> Call 087859728</a>
            </div>
        `,
        showCancelButton: true,
        cancelButtonText: 'End Call',
        showConfirmButton: false,
        background: '#ffffff',
        color: '#1e293b'
    });
};

window.toggleAttachmentMenu = function() {
    const menu = document.getElementById('chatAttachmentMenu');
    if (menu) {
        menu.classList.toggle('d-none');
    }
    document.getElementById('chatEmojiBar')?.classList.add('d-none');
};

window.triggerFileInput = function(acceptType) {
    const input = document.getElementById('chatFileInput');
    if (input) {
        input.accept = acceptType || '*/*';
        input.click();
    }
    document.getElementById('chatAttachmentMenu')?.classList.add('d-none');
};

window.handleChatFileSelect = function(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    const previewBanner = document.getElementById('chatAttachmentPreview');
    const fileNameEl = document.getElementById('chatAttachFileName');
    const fileSizeEl = document.getElementById('chatAttachFileSize');
    const thumbEl = document.getElementById('chatAttachThumb');

    if (fileNameEl) fileNameEl.textContent = file.name;
    if (fileSizeEl) {
        const sizeKB = (file.size / 1024).toFixed(1);
        fileSizeEl.textContent = sizeKB > 1024 ? (sizeKB / 1024).toFixed(2) + ' MB' : sizeKB + ' KB';
    }

    if (thumbEl) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                thumbEl.innerHTML = `<img src="${e.target.result}" style="width:32px;height:32px;object-fit:cover;border-radius:4px;">`;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            thumbEl.innerHTML = `<i class="bi bi-camera-video-fill text-danger fs-4"></i>`;
        } else {
            thumbEl.innerHTML = `<i class="bi bi-file-earmark-text-fill text-primary fs-4"></i>`;
        }
    }

    if (previewBanner) {
        previewBanner.classList.remove('d-none');
    }
    scrollChatToBottom();
};

window.cancelChatAttachment = function() {
    const input = document.getElementById('chatFileInput');
    if (input) input.value = '';
    document.getElementById('chatAttachmentPreview')?.classList.add('d-none');
};

// Fetch and render full conversation history
function fetchChatMessages(markRead = false, targetParams = null) {
    const stream = document.getElementById('chatMessagesStream');
    if (!stream) return;

    const target = targetParams || window.adminActiveChatTarget;
    let url = 'chat.php?';
    if (markRead) url += 'mark_read=1&';
    if (target) {
        if (target.userId) url += 'target_user_id=' + encodeURIComponent(target.userId) + '&';
        if (target.email) url += 'target_email=' + encodeURIComponent(target.email) + '&';
        if (target.msgId) url += 'target_msg_id=' + encodeURIComponent(target.msgId) + '&';
    }

    fetch(getApiUrl(url))
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') {
                return;
            }

            window.currentUserIsAdmin = !!data.is_admin;
            if (data.is_admin) {
                document.getElementById('chatFloatingContainer')?.classList.add('d-none');
            }

            const messages = data.messages || [];
            if (messages.length === 0) {
                if (data.is_admin && target) {
                    stream.innerHTML = `
                        <div class="text-center py-4 text-muted" style="font-size:0.85rem;">
                            <i class="bi bi-chat-left-text fs-2 d-block mb-2 text-accent"></i>
                            <div>No previous chat messages with <strong>${escapeHtml(target.name || 'this user')}</strong>.</div>
                            <small class="text-muted">Type your reply below to start the conversation.</small>
                        </div>
                    `;
                } else {
                    stream.innerHTML = '';
                }
                return;
            }

            const prefix = window.location.pathname.includes('/dashboard/') ? '../' : '';
            const adminName = data.admin?.name || 'Kimsreng Song';
            const adminAvatarUrl = prefix + (data.admin?.avatar || 'assets/images/Kimsreng Song.JPG');

            let html = '';

            messages.forEach((msg) => {
                const isReplied = (msg.status === 'replied' && msg.admin_reply);

                // Helper for message attachment
                let attachmentHtml = '';
                if (msg.attachment_url) {
                    const fullUrl = prefix + msg.attachment_url;
                    if (msg.attachment_type === 'image') {
                        attachmentHtml = `
                            <div class="drift-msg-attachment mt-1 mb-1">
                                <a href="${fullUrl}" target="_blank" title="Click to view full image">
                                    <img src="${fullUrl}" class="drift-attachment-img rounded" alt="${escapeHtml(msg.attachment_name || 'Image')}">
                                </a>
                            </div>
                        `;
                    } else if (msg.attachment_type === 'video') {
                        attachmentHtml = `
                            <div class="drift-msg-attachment mt-1 mb-1">
                                <video src="${fullUrl}" controls class="drift-attachment-video rounded"></video>
                            </div>
                        `;
                    } else {
                        attachmentHtml = `
                            <div class="drift-msg-attachment mt-1 mb-1">
                                <a href="${fullUrl}" download="${escapeHtml(msg.attachment_name || 'document')}" class="drift-attachment-file-btn d-flex align-items-center gap-2 p-2 rounded text-decoration-none">
                                    <i class="bi bi-file-earmark-arrow-down-fill fs-4 text-white"></i>
                                    <span class="text-truncate text-white" style="max-width:180px;font-size:0.8rem;">${escapeHtml(msg.attachment_name || 'Download File')}</span>
                                </a>
                            </div>
                        `;
                    }
                }

                const msgReactions = (msg.reactions && msg.reactions.msg) || [];
                const replyReactions = (msg.reactions && msg.reactions.reply) || [];

                if (data.is_admin) {
                    // ========================================================
                    // ADMIN VIEW:
                    // User inquiry is on the LEFT (Incoming client message)
                    // Admin reply is on the RIGHT (Outgoing "You" in electric blue)
                    // ========================================================
                    const userInitial = (msg.user_name || 'U').charAt(0).toUpperCase();
                    const fullUserAvatar = msg.user_avatar ? (prefix + msg.user_avatar) : null;
                    const avatarHtml = fullUserAvatar
                        ? `<img src="${fullUserAvatar}" class="drift-msg-avatar-img" alt="${escapeHtml(msg.user_name)}" onerror="this.onerror=null;this.parentElement.innerHTML='${userInitial}';">`
                        : `<span>${userInitial}</span>`;

                    html += `
                        <!-- User Inquiry Row (Left - Client) -->
                        <div class="drift-msg-row bot-row mb-3" id="chat-msg-${msg.id}" data-msg-id="${msg.id}">
                            <div class="drift-msg-sender-name ms-5 ps-1 mb-1 d-flex align-items-center gap-1">
                                <strong>${escapeHtml(msg.user_name)}</strong>
                                <span class="badge bg-secondary-subtle text-muted" style="font-size:0.65rem;">Client</span>
                                ${isReplied ? '<span class="badge bg-success-subtle text-success ms-1" style="font-size:0.62rem;"><i class="bi bi-check2-circle me-1"></i>Replied</span>' : '<span class="badge bg-warning-subtle text-warning ms-1" style="font-size:0.62rem;"><i class="bi bi-clock me-1"></i>Pending</span>'}
                            </div>
                            <div class="d-flex align-items-end gap-2 position-relative">
                                <div class="drift-msg-avatar bot-avatar" title="${escapeHtml(msg.user_name)}">
                                    ${avatarHtml}
                                </div>
                                <div class="d-flex flex-column gap-1 position-relative" style="max-width:82%;">
                                    ${renderReactionBarHtml(msg.id, 'msg')}

                                    <div class="drift-bubble-wrap position-relative">
                                        <div class="drift-msg-bubble bot-bubble">
                                            ${msg.subject ? `<div class="fw-semibold text-accent mb-1" style="font-size:0.75rem;"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(msg.subject)}</div>` : ''}
                                            ${attachmentHtml}
                                            ${msg.message ? `<div><span>${escapeHtml(msg.message).replace(/\n/g, '<br>')}</span></div>` : ''}
                                            <div class="drift-msg-meta text-muted mt-1" style="font-size:0.68rem;">
                                                ${msg.created_at} &bull; ${msg.date_group || ''}
                                            </div>
                                        </div>
                                        <button type="button" class="drift-msg-action-btn bot-action-btn" onclick="toggleReactionMenu(${msg.id}, 'msg', event)" title="React with emoji">
                                            <i class="bi bi-emoji-smile"></i>
                                        </button>
                                    </div>

                                    <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-start" id="reactions-msg-${msg.id}">
                                        ${renderReactionPillsHtml(msg.id, 'msg', msgReactions)}
                                    </div>

                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-start text-accent ms-1" style="font-size:0.72rem;" onclick="prepareChatReply(${msg.id}, '${escapeHtml(msg.message).substring(0, 25)}')">
                                        <i class="bi bi-reply-fill me-1"></i>Reply to this message
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;

                    // Admin Reply (Right - Outgoing for Admin in Solid Blue)
                    if (isReplied) {
                        html += `
                            <div class="drift-msg-row user-row mb-3" id="chat-reply-${msg.id}" data-msg-id="${msg.id}">
                                <div class="d-flex flex-column align-items-end position-relative">
                                    <div class="text-end me-1 mb-1" style="font-size:0.68rem;color:var(--text-muted);">
                                        <span class="badge bg-accent-subtle text-accent"><i class="bi bi-shield-check me-1"></i>You (Admin)</span>
                                    </div>
                                    ${renderReactionBarHtml(msg.id, 'reply')}

                                    <div class="drift-bubble-wrap position-relative">
                                        <div class="drift-msg-bubble user-bubble d-flex flex-column">
                                            <div><span>${escapeHtml(msg.admin_reply).replace(/\n/g, '<br>')}</span></div>
                                            <div class="drift-msg-meta text-end text-white-50 mt-1" style="font-size:0.68rem;">
                                                ${msg.replied_at || msg.created_at} &bull; ✓✓ Sent
                                            </div>
                                        </div>
                                        <button type="button" class="drift-msg-action-btn user-action-btn" onclick="toggleReactionMenu(${msg.id}, 'reply', event)" title="React with emoji">
                                            <i class="bi bi-emoji-smile"></i>
                                        </button>
                                    </div>

                                    <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-end" id="reactions-reply-${msg.id}">
                                        ${renderReactionPillsHtml(msg.id, 'reply', replyReactions)}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    // ========================================================
                    // REGULAR USER VIEW:
                    // User message is on the RIGHT (Solid Blue)
                    // Admin reply is on the LEFT (Light Gray)
                    // ========================================================
                    html += `
                        <div class="drift-msg-row user-row mb-3" id="chat-msg-${msg.id}" data-msg-id="${msg.id}">
                            <div class="d-flex flex-column align-items-end position-relative">
                                ${renderReactionBarHtml(msg.id, 'msg')}

                                <div class="drift-bubble-wrap position-relative">
                                    <div class="drift-msg-bubble user-bubble d-flex flex-column">
                                        ${attachmentHtml}
                                        ${msg.message ? `<div><span>${escapeHtml(msg.message).replace(/\n/g, '<br>')}</span></div>` : ''}
                                        <div class="drift-msg-meta text-end text-white-50 mt-1" style="font-size:0.68rem;">
                                            ${msg.created_at} &bull; ✓✓
                                        </div>
                                    </div>
                                    <button type="button" class="drift-msg-action-btn user-action-btn" onclick="toggleReactionMenu(${msg.id}, 'msg', event)" title="React with emoji">
                                        <i class="bi bi-emoji-smile"></i>
                                    </button>
                                </div>

                                <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-end" id="reactions-msg-${msg.id}">
                                    ${renderReactionPillsHtml(msg.id, 'msg', msgReactions)}
                                </div>
                            </div>
                        </div>
                    `;

                    if (isReplied) {
                        html += `
                            <div class="drift-msg-row bot-row mb-3" id="chat-reply-${msg.id}" data-msg-id="${msg.id}">
                                <div class="drift-msg-sender-name ms-5 ps-1 mb-1">${escapeHtml(adminName)}</div>
                                <div class="d-flex align-items-end gap-2 position-relative">
                                    <div class="drift-msg-avatar bot-avatar" title="${escapeHtml(adminName)}">
                                        <img src="${adminAvatarUrl}" alt="${escapeHtml(adminName)}" class="drift-msg-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\\'bi bi-person-fill\\'></i>';">
                                    </div>
                                    <div class="d-flex flex-column gap-1 position-relative">
                                        ${renderReactionBarHtml(msg.id, 'reply')}

                                        <div class="drift-bubble-wrap position-relative">
                                            <div class="drift-msg-bubble bot-bubble">
                                                ${escapeHtml(msg.admin_reply).replace(/\n/g, '<br>')}
                                                <div class="drift-msg-meta text-muted mt-1" style="font-size:0.68rem;">
                                                    ${msg.replied_at || msg.created_at}
                                                </div>
                                            </div>
                                            <button type="button" class="drift-msg-action-btn bot-action-btn" onclick="toggleReactionMenu(${msg.id}, 'reply', event)" title="React with emoji">
                                                <i class="bi bi-emoji-smile"></i>
                                            </button>
                                        </div>

                                        <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-start" id="reactions-reply-${msg.id}">
                                            ${renderReactionPillsHtml(msg.id, 'reply', replyReactions)}
                                        </div>

                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-start text-muted ms-2" style="font-size:0.72rem;" onclick="prepareChatReply(${msg.id}, '${escapeHtml(msg.admin_reply).substring(0, 25)}')">
                                            <i class="bi bi-reply me-1"></i>Reply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }
            });

            stream.innerHTML = html;
            scrollChatToBottom();
        })
        .catch(err => {
            console.error('Error loading chat:', err);
        });
}

function scrollChatToBottom() {
    const body = document.getElementById('chatWidgetBody');
    if (body) {
        body.scrollTop = body.scrollHeight;
    }
}

// Send chat message with text and/or attachment
function sendChatMessage(e) {
    e.preventDefault();
    const msgInput = document.getElementById('chatMessageInput');
    const replyIdInput = document.getElementById('chatReplyToId');
    const targetUserIdInput = document.getElementById('chatTargetUserId');
    const targetEmailInput = document.getElementById('chatTargetEmail');
    const fileInput = document.getElementById('chatFileInput');
    const sendBtn = document.getElementById('chatSendBtn');

    const message = msgInput?.value.trim() || '';
    const replyToId = replyIdInput?.value || '';
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

    if (!message && !hasFile) return;

    sendBtn.disabled = true;
    const originalBtn = sendBtn.innerHTML;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;"></span>';

    const formData = new FormData();
    formData.append('message', message);
    formData.append('subject', 'Chat Inquiry');
    if (replyToId) {
        formData.append('reply_to_id', replyToId);
    }
    if (targetUserIdInput && targetUserIdInput.value) {
        formData.append('target_user_id', targetUserIdInput.value);
    }
    if (targetEmailInput && targetEmailInput.value) {
        formData.append('target_email', targetEmailInput.value);
    }
    if (hasFile) {
        formData.append('attachment', fileInput.files[0]);
    }

    fetch(getApiUrl('chat.php'), {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = originalBtn;

        if (data.status === 'success') {
            if (msgInput) msgInput.value = '';
            cancelChatReply();
            cancelChatAttachment();

            // If admin replying to specific user
            if (window.adminActiveChatTarget) {
                fetchChatMessages(false, window.adminActiveChatTarget);
                const repliedMsgId = data.data?.id || replyToId || window.adminActiveChatTarget.msgId;
                if (repliedMsgId && typeof window.updateAdminRowStatus === 'function') {
                    window.updateAdminRowStatus(repliedMsgId);
                }
                const swalTheme = getSwalThemeOptions();
                Swal.fire({
                    icon: 'success',
                    title: 'Reply Sent!',
                    text: data.message || 'Your reply has been sent and emailed to the user.',
                    timer: 2000,
                    showConfirmButton: false,
                    background: swalTheme.background,
                    color: swalTheme.color
                });
            } else {
                fetchChatMessages();
            }
            fetchNotifications();
        } else if (data.code === 'auth_required') {
            const swalTheme = getSwalThemeOptions();
            Swal.fire({
                icon: 'warning',
                title: 'Sign in to Chat',
                text: data.message || 'Please log in to chat with the Admin.',
                showCancelButton: true,
                confirmButtonText: 'Login Now',
                cancelButtonText: 'Cancel',
                background: swalTheme.background,
                color: swalTheme.color,
                confirmButtonColor: swalTheme.confirmButtonColor
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = (window.location.pathname.includes('/dashboard/') ? '../' : '') + 'login.php';
                }
            });
        } else {
            const swalTheme = getSwalThemeOptions();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not send message.',
                background: swalTheme.background,
                color: swalTheme.color,
                confirmButtonColor: swalTheme.confirmButtonColor
            });
        }
    })
    .catch(err => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = originalBtn;
        console.error(err);
    });
}

// ===== INITIALIZE NOTIFICATIONS & CHAT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch of notifications and chat history
    fetchNotifications();
    fetchChatMessages();

    // Polling every 10 seconds
    pollingInterval = setInterval(() => {
        fetchNotifications();
        if (isChatOpen) {
            fetchChatMessages();
        }
    }, 10000);

    // Bind floating chat container & button to open chat
    const floatContainer = document.getElementById('chatFloatingContainer');
    if (floatContainer) {
        floatContainer.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openChatWidget();
        });
    }

    // Bind close & minimize buttons
    document.getElementById('chatCloseBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        closeChatWidget(e);
    });
    document.getElementById('chatMinimizeBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        closeChatWidget(e);
    });

    // Press Escape to close chat if open
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isChatOpen) {
            closeChatWidget(e);
        }
    });

    // Bind Mark all read button
    document.getElementById('markAllReadBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        markAllNotificationsRead();
    });

    // Bind Chat Send Form & Enter key
    const chatSendForm = document.getElementById('chatSendForm');
    const chatInput = document.getElementById('chatMessageInput');
    if (chatSendForm) {
        chatSendForm.addEventListener('submit', sendChatMessage);
    }
    if (chatInput && chatSendForm) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatSendForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // Close menus on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.drift-msg-reaction-bar') && !e.target.closest('.drift-msg-action-btn') && !e.target.closest('.drift-reaction-pill')) {
            document.querySelectorAll('.drift-msg-reaction-bar').forEach(b => b.classList.add('d-none'));
        }
        if (!e.target.closest('#chatEmojiBar') && !e.target.closest('#chatEmojiToggleBtn')) {
            document.getElementById('chatEmojiBar')?.classList.add('d-none');
        }
        if (!e.target.closest('#chatAttachmentMenu') && !e.target.closest('#chatActionPlusBtn')) {
            document.getElementById('chatAttachmentMenu')?.classList.add('d-none');
        }
        if (!e.target.closest('#driftOptionsMenu') && !e.target.closest('.drift-header-btn')) {
            document.getElementById('driftOptionsMenu')?.classList.add('d-none');
        }
    });
});
