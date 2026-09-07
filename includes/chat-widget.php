<?php
// includes/chat-widget.php - Drift Live Chat UI (Matching Screenshot media_1788255730546.png)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
$chatUser = getCurrentUser();
$_prefix = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../' : '';
$adminName = 'Kimsreng Song';
$adminRole = 'Admin';
$adminAvatar = 'assets/images/Kimsreng Song.JPG';

if (isset($conn)) {
    $aRes = $conn->query("SELECT name, role, avatar FROM users WHERE role = 'admin' LIMIT 1");
    if ($aRes && $aRow = $aRes->fetch_assoc()) {
        if (!empty($aRow['name'])) $adminName = $aRow['name'];
        if (!empty($aRow['avatar'])) $adminAvatar = $aRow['avatar'];
    }
}
$adminAvatarUrl = $_prefix . $adminAvatar;

// Pre-fetch past conversation history for instant rendering
$historyMessages = [];
if ($chatUser && isset($conn)) {
    $uId = (int)$chatUser['id'];
    $uEmail = $chatUser['email'];
    $hStmt = $conn->prepare("SELECT id, user_id, name, email, subject, message, status, admin_reply, attachment_url, attachment_type, attachment_name, reactions, replied_at, created_at FROM messages WHERE (user_id = ? OR email = ?) ORDER BY created_at ASC");
    if ($hStmt) {
        $hStmt->bind_param('is', $uId, $uEmail);
        $hStmt->execute();
        $hRes = $hStmt->get_result();
        while ($r = $hRes->fetch_assoc()) {
            $historyMessages[] = $r;
        }
        $hStmt->close();
    }
}

if (!function_exists('parseMessageReactions')) {
    function parseMessageReactions($rawJson, $currentUserId) {
        $parsed = ['msg' => [], 'reply' => []];
        if (!empty($rawJson)) {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $msgMap = [];
                $replyMap = [];
                if (isset($decoded['msg']) || isset($decoded['reply'])) {
                    $msgMap = is_array($decoded['msg'] ?? null) ? $decoded['msg'] : [];
                    $replyMap = is_array($decoded['reply'] ?? null) ? $decoded['reply'] : [];
                } else {
                    $msgMap = $decoded;
                }
                foreach ($msgMap as $em => $users) {
                    if (is_array($users) && !empty($users)) {
                        $parsed['msg'][] = [
                            'emoji'        => $em,
                            'count'        => count($users),
                            'user_reacted' => in_array($currentUserId, $users)
                        ];
                    }
                }
                foreach ($replyMap as $em => $users) {
                    if (is_array($users) && !empty($users)) {
                        $parsed['reply'][] = [
                            'emoji'        => $em,
                            'count'        => count($users),
                            'user_reacted' => in_array($currentUserId, $users)
                        ];
                    }
                }
            }
        }
        return $parsed;
    }
}
$quickReactionEmojis = ['❤️', '👍', '🔥', '😂', '😮', '😢', '🎉'];
?>

<?php if (!isAdmin()): ?>
<!-- FLOATING DRIFT CHAT BUTTON (Hidden for Admin) -->
<div class="chat-floating-container" id="chatFloatingContainer" onclick="openChatWidget()" role="button" aria-label="Open Live Chat" title="Chat with <?= htmlspecialchars($adminName) ?>">
    <button type="button" class="drift-floating-btn" id="chatFloatingBtn" aria-label="Open Live Chat">
        <div class="drift-floating-icon">
            <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="<?= htmlspecialchars($adminName) ?>" class="drift-floating-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'bi bi-person-fill fs-2\'></i>';">
        </div>
        <span class="chat-badge d-none" id="chatFloatingBadge">0</span>
        <span class="drift-pulse-ring d-none" id="chatPulseRing"></span>
    </button>
</div>
<?php endif; ?>

<!-- DRIFT LIVE CHAT POPUP WINDOW -->
<div class="drift-chat-widget" id="chatWidgetBox" aria-hidden="true" data-is-admin="<?= isAdmin() ? '1' : '0' ?>">
    <!-- Drift Blue Header with Direct Action Controls -->
    <div class="drift-chat-header d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
            <div class="drift-header-avatar position-relative" id="driftHeaderAvatarWrap">
                <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="<?= htmlspecialchars($adminName) ?>" class="drift-header-avatar-img" id="driftHeaderAvatarImg" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'bi bi-person-fill text-primary\'></i>';">
                <span class="drift-status-online" id="driftHeaderOnlineDot"></span>
            </div>
            <div class="overflow-hidden">
                <div class="drift-header-title fw-bold text-white text-truncate" id="driftHeaderTitle"><?= htmlspecialchars($adminName) ?></div>
                <div class="drift-header-subtitle text-white-50 text-truncate" id="driftHeaderSubtitle" style="font-size:0.72rem;"><?= htmlspecialchars($adminRole) ?> &bull; Online</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button type="button" class="drift-header-action-btn drift-header-video-btn" id="driftHeaderVideoBtn" title="Start Video Call" onclick="startVideoCall()">
                <i class="bi bi-camera-video-fill"></i>
            </button>
            <a href="tel:087859728" class="drift-header-action-btn drift-header-call-btn" id="driftHeaderCallLink" title="Call <?= htmlspecialchars($adminName) ?> (087859728)">
                <i class="bi bi-telephone-fill"></i>
            </a>
            <a href="mailto:songkimsreng001@gmail.com" class="drift-header-action-btn drift-header-mail-btn" id="driftHeaderMailLink" title="Email <?= htmlspecialchars($adminName) ?> (songkimsreng001@gmail.com)">
                <i class="bi bi-envelope-fill"></i>
            </a>
            <button type="button" class="drift-header-btn ms-1" id="chatCloseBtn" title="Close" onclick="closeChatWidget(event)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Drift Chat Body Canvas -->
    <div class="drift-chat-body" id="chatWidgetBody">
        <!-- Initial Bot Welcome Message (Drift Style - Hidden in Admin Reply Mode) -->
        <div class="drift-msg-row bot-row mb-3 <?= isAdmin() ? 'd-none' : '' ?>" id="driftWelcomeRow">
            <div class="drift-msg-sender-name ms-5 ps-1 mb-1"><?= htmlspecialchars($adminName) ?></div>
            <div class="d-flex align-items-end gap-2">
                <div class="drift-msg-avatar bot-avatar" title="<?= htmlspecialchars($adminName) ?>">
                    <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="<?= htmlspecialchars($adminName) ?>" class="drift-msg-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'bi bi-person-fill\'></i>';">
                </div>
                <div class="drift-msg-bubble bot-bubble">
                    Hey there! 👋 Welcome to <strong><?= htmlspecialchars($adminName) ?></strong>'s portfolio. Are you ready to start discussing your project or services?
                </div>
            </div>
        </div>

        <!-- Quick Response Choice Pills (Drift Style - Hidden for Admin) -->
        <div class="drift-options-container mb-3 <?= isAdmin() ? 'd-none' : '' ?>" id="driftQuickChoices">
            <div class="d-flex flex-column gap-2">
                <button type="button" class="drift-choice-pill" onclick="selectChatOption('I am researching web & software development services')">
                    I am researching web & software development
                </button>
                <button type="button" class="drift-choice-pill" onclick="selectChatOption('I want to hire or collaborate with Kimsreng Song')">
                    I want to hire or collaborate with you
                </button>
                <button type="button" class="drift-choice-pill" onclick="selectChatOption('I want to schedule a quick call (087859728)')">
                    I want to schedule a quick call
                </button>
            </div>
        </div>

        <!-- Dynamic Live Conversation History Stream -->
        <div class="chat-messages-stream" id="chatMessagesStream">
            <?php foreach ($historyMessages as $msg): ?>
                <?php
                $userInitial = strtoupper(substr($chatUser['name'] ?? 'U', 0, 1));
                $timeStr = date('g:i A', strtotime($msg['created_at']));
                $replyTimeStr = !empty($msg['replied_at']) ? date('g:i A', strtotime($msg['replied_at'])) : $timeStr;
                $isReplied = ($msg['status'] === 'replied' && !empty($msg['admin_reply']));
                $rxData = parseMessageReactions($msg['reactions'] ?? '', $chatUser ? (int)$chatUser['id'] : 0);
                $msgReactions = $rxData['msg'];
                $replyReactions = $rxData['reply'];
                ?>
                <!-- User Message Bubble (Right - Solid Blue) -->
                <div class="drift-msg-row user-row mb-3" id="chat-msg-<?= $msg['id'] ?>" data-msg-id="<?= $msg['id'] ?>">
                    <div class="d-flex flex-column align-items-end position-relative">
                        <!-- Floating Quick Reaction Bar -->
                        <div class="drift-msg-reaction-bar d-none" id="reaction-bar-msg-<?= $msg['id'] ?>">
                            <?php foreach ($quickReactionEmojis as $qEm): ?>
                                <button type="button" class="drift-reaction-bar-btn" onclick="reactToMessage(<?= $msg['id'] ?>, '<?= $qEm ?>', 'msg', event)" title="<?= $qEm ?>"><?= $qEm ?></button>
                            <?php endforeach; ?>
                        </div>

                        <div class="drift-bubble-wrap position-relative">
                            <div class="drift-msg-bubble user-bubble d-flex flex-column">
                                <?php if (!empty($msg['attachment_url'])): ?>
                                    <?php $fullAttUrl = $_prefix . htmlspecialchars($msg['attachment_url']); ?>
                                    <?php if ($msg['attachment_type'] === 'image'): ?>
                                        <div class="drift-msg-attachment mt-1 mb-1">
                                            <a href="<?= $fullAttUrl ?>" target="_blank">
                                                <img src="<?= $fullAttUrl ?>" class="drift-attachment-img rounded" alt="Image">
                                            </a>
                                        </div>
                                    <?php elseif ($msg['attachment_type'] === 'video'): ?>
                                        <div class="drift-msg-attachment mt-1 mb-1">
                                            <video src="<?= $fullAttUrl ?>" controls class="drift-attachment-video rounded"></video>
                                        </div>
                                    <?php else: ?>
                                        <div class="drift-msg-attachment mt-1 mb-1">
                                            <a href="<?= $fullAttUrl ?>" download="<?= htmlspecialchars($msg['attachment_name'] ?? 'file') ?>" class="drift-attachment-file-btn d-flex align-items-center gap-2 p-2 rounded text-decoration-none">
                                                <i class="bi bi-file-earmark-arrow-down-fill fs-4 text-white"></i>
                                                <span class="text-truncate text-white" style="max-width:180px;font-size:0.8rem;"><?= htmlspecialchars($msg['attachment_name'] ?? 'Download File') ?></span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!empty($msg['message'])): ?>
                                    <div>
                                        <span><?= nl2br(htmlspecialchars($msg['message'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="drift-msg-meta text-end text-white-50 mt-1" style="font-size:0.68rem;">
                                    <?= $timeStr ?> &bull; ✓✓
                                </div>
                            </div>
                            <button type="button" class="drift-msg-action-btn user-action-btn" onclick="toggleReactionMenu(<?= $msg['id'] ?>, 'msg', event)" title="React with emoji">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                        </div>

                        <!-- Reaction Badges for User Message -->
                        <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-end" id="reactions-msg-<?= $msg['id'] ?>">
                            <?php foreach ($msgReactions as $rx): ?>
                                <button type="button" class="drift-reaction-pill <?= $rx['user_reacted'] ? 'active' : '' ?>" onclick="reactToMessage(<?= $msg['id'] ?>, '<?= $rx['emoji'] ?>', 'msg', event)" title="<?= $rx['emoji'] ?> <?= $rx['count'] ?>">
                                    <span><?= $rx['emoji'] ?></span>
                                    <span class="drift-reaction-count"><?= $rx['count'] ?></span>
                                </button>
                            <?php endforeach; ?>
                            <button type="button" class="drift-reaction-pill drift-add-reaction-pill <?= empty($msgReactions) ? 'd-none' : '' ?>" onclick="toggleReactionMenu(<?= $msg['id'] ?>, 'msg', event)" title="Add reaction">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Admin / Bot Reply Bubble (Left - Light Gray) -->
                <?php if ($isReplied): ?>
                    <div class="drift-msg-row bot-row mb-3" id="chat-reply-<?= $msg['id'] ?>" data-msg-id="<?= $msg['id'] ?>">
                        <div class="drift-msg-sender-name ms-5 ps-1 mb-1"><?= htmlspecialchars($adminName) ?></div>
                        <div class="d-flex align-items-end gap-2 position-relative">
                            <div class="drift-msg-avatar bot-avatar" title="<?= htmlspecialchars($adminName) ?>">
                                <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="<?= htmlspecialchars($adminName) ?>" class="drift-msg-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'bi bi-person-fill\'></i>';">
                            </div>
                            <div class="d-flex flex-column gap-1 position-relative">
                                <!-- Floating Quick Reaction Bar -->
                                <div class="drift-msg-reaction-bar d-none" id="reaction-bar-reply-<?= $msg['id'] ?>">
                                    <?php foreach ($quickReactionEmojis as $qEm): ?>
                                        <button type="button" class="drift-reaction-bar-btn" onclick="reactToMessage(<?= $msg['id'] ?>, '<?= $qEm ?>', 'reply', event)" title="<?= $qEm ?>"><?= $qEm ?></button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="drift-bubble-wrap position-relative">
                                    <div class="drift-msg-bubble bot-bubble">
                                        <?= nl2br(htmlspecialchars($msg['admin_reply'])) ?>
                                        <div class="drift-msg-meta text-muted mt-1" style="font-size:0.68rem;">
                                            <?= $replyTimeStr ?>
                                        </div>
                                    </div>
                                    <button type="button" class="drift-msg-action-btn bot-action-btn" onclick="toggleReactionMenu(<?= $msg['id'] ?>, 'reply', event)" title="React with emoji">
                                        <i class="bi bi-emoji-smile"></i>
                                    </button>
                                </div>

                                <!-- Reaction Badges for Reply -->
                                <div class="drift-msg-reactions d-flex flex-wrap gap-1 mt-1 justify-content-start" id="reactions-reply-<?= $msg['id'] ?>">
                                    <?php foreach ($replyReactions as $rx): ?>
                                        <button type="button" class="drift-reaction-pill <?= $rx['user_reacted'] ? 'active' : '' ?>" onclick="reactToMessage(<?= $msg['id'] ?>, '<?= $rx['emoji'] ?>', 'reply', event)" title="<?= $rx['emoji'] ?> <?= $rx['count'] ?>">
                                            <span><?= $rx['emoji'] ?></span>
                                            <span class="drift-reaction-count"><?= $rx['count'] ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                    <button type="button" class="drift-reaction-pill drift-add-reaction-pill <?= empty($replyReactions) ? 'd-none' : '' ?>" onclick="toggleReactionMenu(<?= $msg['id'] ?>, 'reply', event)" title="Add reaction">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>

                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-start text-muted ms-2" style="font-size:0.72rem;" onclick="prepareChatReply(<?= $msg['id'] ?>, '<?= addslashes(htmlspecialchars(substr($msg['admin_reply'], 0, 25))) ?>')">
                                    <i class="bi bi-reply me-1"></i>Reply
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Active Attachment Preview Banner -->
    <div id="chatAttachmentPreview" class="drift-attachment-preview d-none px-3 py-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <div id="chatAttachThumb" class="drift-attach-thumb">
                <i class="bi bi-file-earmark-fill text-primary"></i>
            </div>
            <div class="overflow-hidden">
                <div id="chatAttachFileName" class="text-truncate text-dark fw-semibold" style="font-size:0.8rem;">filename.png</div>
                <div id="chatAttachFileSize" class="text-muted" style="font-size:0.7rem;">0 KB</div>
            </div>
        </div>
        <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" onclick="cancelChatAttachment()" title="Remove file">
            <i class="bi bi-x-circle-fill fs-5"></i>
        </button>
    </div>

    <!-- Active Reply Banner -->
    <div id="chatReplyBanner" class="drift-reply-banner d-none px-3 py-1 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-1 overflow-hidden">
            <i class="bi bi-reply-fill text-primary"></i>
            <span id="chatReplyText" class="text-truncate text-dark" style="font-size:0.78rem;">Replying to message...</span>
        </div>
        <button type="button" class="btn btn-link btn-sm text-muted p-0 text-decoration-none" onclick="cancelChatReply()" title="Cancel">
            <i class="bi bi-x-circle-fill"></i>
        </button>
    </div>

    <!-- Attachment Options Menu -->
    <div id="chatAttachmentMenu" class="drift-attachment-menu d-none px-3 py-2 border-top">
        <div class="d-flex align-items-center justify-content-around gap-2">
            <button type="button" class="drift-attach-opt-btn" onclick="triggerFileInput('image/*')">
                <div class="drift-attach-icon-circle bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-image-fill"></i>
                </div>
                <span>Photo</span>
            </button>
            <button type="button" class="drift-attach-opt-btn" onclick="triggerFileInput('video/*')">
                <div class="drift-attach-icon-circle bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-camera-video-fill"></i>
                </div>
                <span>Video</span>
            </button>
            <button type="button" class="drift-attach-opt-btn" onclick="triggerFileInput('*/*')">
                <div class="drift-attach-icon-circle bg-success bg-opacity-10 text-success">
                    <i class="bi bi-file-earmark-arrow-up-fill"></i>
                </div>
                <span>Document</span>
            </button>
        </div>
    </div>

    <!-- Expanded Emoji Picker Palette with Tabs -->
    <div id="chatEmojiBar" class="drift-emoji-bar d-none border-top">
        <!-- Category Tabs: All, Smileys, Gestures, Hearts, Fun -->
        <div class="drift-emoji-tabs d-flex align-items-center gap-1 px-3 pt-2 pb-1 border-bottom">
            <button type="button" class="drift-emoji-tab active" data-cat="all" onclick="switchEmojiCategory('all', this)">
                ✨ All
            </button>
            <button type="button" class="drift-emoji-tab" data-cat="smileys" onclick="switchEmojiCategory('smileys', this)">
                😊 Smileys
            </button>
            <button type="button" class="drift-emoji-tab" data-cat="gestures" onclick="switchEmojiCategory('gestures', this)">
                👍 Gestures
            </button>
            <button type="button" class="drift-emoji-tab" data-cat="hearts" onclick="switchEmojiCategory('hearts', this)">
                ❤️ Hearts
            </button>
            <button type="button" class="drift-emoji-tab" data-cat="fun" onclick="switchEmojiCategory('fun', this)">
                🎉 Fun
            </button>
        </div>

        <!-- Emoji Lists per category -->
        <div class="drift-emoji-content px-3 py-2">
            <?php
            $emojiCategories = [
                'smileys' => [
                    'title' => 'Smileys',
                    'emojis' => [
                        '😊', '😂', '🤣', '😍', '🥰', '😘', '😎', '🤩', '🥳', '🤔',
                        '🤫', '🤭', '🙄', '🥺', '😭', '😤', '😱', '😴', '😇', '🤠',
                        '😋', '😜', '🤪', '🤤', '😷', '🤒', '🤕', '🤢', '🤮', '🤧',
                        '🥵', '🥶', '🥴', '😵', '🤯', '🧐', '🤓', '🤡', '👻', '💀'
                    ]
                ],
                'gestures' => [
                    'title' => 'Gestures',
                    'emojis' => [
                        '👍', '👎', '👏', '🙌', '🤝', '✌️', '🤞', '🤟', '🤘', '🤙',
                        '👊', '✊', '🤛', '🤜', '🖐️', '✋', '👋', '🙏', '💪', '💅',
                        '👈', '👉', '👆', '👇', '☝️', '👌', '🤏', '✍️', '🙋‍♂️', '🙋‍♀️'
                    ]
                ],
                'hearts' => [
                    'title' => 'Hearts',
                    'emojis' => [
                        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
                        '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '🔥',
                        '💯', '✨', '⭐', '🌟', '💥', '⚡', '💫', '💬', '💭', '💤'
                    ]
                ],
                'fun' => [
                    'title' => 'Fun',
                    'emojis' => [
                        '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🎯', '🚀', '💡', '☕',
                        '🍕', '🍔', '🍻', '🥂', '💻', '📱', '💼', '👑', '💎', '🎨',
                        '🎧', '🎮', '⚽', '🏀', '🚗', '✈️', '🌍', '☀️', '🌙', '🪄'
                    ]
                ]
            ];
            ?>
            <?php foreach ($emojiCategories as $catKey => $catData): ?>
                <div class="drift-emoji-category-block mb-2" id="driftEmojiCat-<?= $catKey ?>" data-cat="<?= $catKey ?>">
                    <div class="drift-emoji-cat-header text-muted fw-semibold mb-1" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">
                        <?= htmlspecialchars($catData['title']) ?>
                    </div>
                    <div class="drift-emoji-grid">
                        <?php foreach ($catData['emojis'] as $em): ?>
                            <button type="button" class="drift-emoji-btn" onclick="insertEmoji('<?= $em ?>')" title="<?= $em ?>"><?= $em ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Drift Footer Input Bar -->
    <div class="drift-chat-footer px-3 py-2">
        <form id="chatSendForm" class="d-flex flex-column m-0 p-0 w-100" enctype="multipart/form-data">
            <input type="hidden" id="chatReplyToId" value="">
            <input type="hidden" id="chatTargetUserId" value="">
            <input type="hidden" id="chatTargetEmail" value="">
            <input type="file" id="chatFileInput" class="d-none" onchange="handleChatFileSelect(this)">
            
            <div class="drift-input-container d-flex align-items-center mb-2">
                <input type="text" class="drift-text-input flex-grow-1" id="chatMessageInput" placeholder="Type your message..." autocomplete="off">
                <button type="submit" class="drift-send-btn ms-2" id="chatSendBtn" title="Send message">
                    <i class="bi bi-arrow-right-circle-fill fs-4 text-primary" id="chatSendIcon"></i>
                </button>
            </div>

            <!-- Footer Icon Controls & Subtle Branding -->
            <div class="d-flex align-items-center justify-content-between pt-1">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="drift-footer-icon-btn" id="chatActionPlusBtn" title="Attach file" onclick="toggleAttachmentMenu()">
                        <i class="bi bi-paperclip"></i>
                    </button>
                    <button type="button" class="drift-footer-icon-btn" id="chatEmojiToggleBtn" title="Emoji" onclick="toggleEmojiMenu()">
                        <i class="bi bi-emoji-smile"></i>
                    </button>
                </div>
                <div class="drift-branding-text">
                    <span>Chat ⚡ by <strong>Kimsreng</strong></span>
                    <span class="mx-1">&bull;</span>
                    <a href="#contact" class="text-muted text-decoration-none">Privacy Policy</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Direct Emoji Category Switching Logic (Guarantees instant execution regardless of browser script caching) -->
<script>
(function() {
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

    // Event delegation for emoji category tabs to handle any click on tab or its children
    document.addEventListener('click', function(e) {
        const tab = e.target.closest('.drift-emoji-tab');
        if (tab) {
            e.preventDefault();
            e.stopPropagation();
            const cat = tab.getAttribute('data-cat') || 'all';
            window.switchEmojiCategory(cat, tab);
        }
    });

    window.currentUserIsAdmin = <?= isAdmin() ? 'true' : 'false' ?>;

    window.updateAdminRowStatus = function(msgId) {
        if (!msgId) return;
        const statusCell = document.getElementById('admin-msg-status-' + msgId);
        if (statusCell) {
            statusCell.innerHTML = '<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="bi bi-check2-circle me-1"></i>Replied</span>';
        }
        const overviewCell = document.getElementById('overview-msg-status-' + msgId);
        if (overviewCell) {
            overviewCell.innerHTML = '<span class="badge bg-success-subtle text-success rounded-pill px-2">Replied</span>';
        }
    };
})();
</script>
