<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$friends = $db->getFriends($_SESSION['user']['id']);
$pendingCount = count($db->getPendingFriendRequests($_SESSION['user']['id']));
?>
<header class="topbar">
    <div class="topbar__brand">
        <a href="index.php" class="brand-link">
            <div class="logo"></div>
            <span class="brand">Jixx</span>
        </a>
    </div>
    <div class="topbar__search">
        <a href="search.php" class="search-btn" aria-label="Search">
            <span class="search-icon">🔍</span>
        </a>
    </div>

    <!-- Chat Overlay -->
    <div class="chat-overlay" id="chatOverlay" style="display: none;">
        <div class="chat-modal">
            <div class="chat-header">
                <h3>Chat</h3>
                <button class="close-chat" id="closeChat">✕</button>
            </div>
            <div class="chat-content">
                <div class="friends-list" id="chatFriendsList">
                    <div class="friends-list-header">Friends</div>
                    <?php foreach ($friends as $friend): ?>
                    <div class="friend-item" data-friend-id="<?php echo $friend['id']; ?>">
                        <?php
                        $friendAvatar = $friend['avatar'] ? htmlspecialchars($friend['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($friend['first_name'] . ' ' . $friend['last_name']) . '&background=0D8ABC&color=fff&size=32';
                        ?>
                        <img src="<?php echo $friendAvatar; ?>" alt="Avatar" class="friend-avatar" />
                        <span><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-area">
                    <div class="chat-messages-header" id="chatMessagesHeader">Select a friend to start chatting</div>
                    <div class="chat-messages" id="chatMessages"></div>
                    <form class="chat-input-form" id="chatInputForm" style="display: none;">
                        <input type="text" id="chatMessageInput" placeholder="Type a message..." required />
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <nav class="topbar__actions" aria-label="Primary">
        <a href="chat.php" class="action chat-btn" aria-label="Chat" id="chatBtn">
            <i class="fa-solid fa-comment">💬</i>
        </a>
        <a href="settings.php" class="action" aria-label="Settings">
            <i class="fa-solid fa-gear">⚙️</i>
        </a>

        <?php
        $currentUser = $db->getUserById($_SESSION['user']['id']);
        $defaultAvatarSmall = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['first_name'] . ' ' . $currentUser['last_name']) . '&background=0D8ABC&color=fff&size=48';
        $topAvatar = $currentUser['avatar'] ? htmlspecialchars($currentUser['avatar']) : $defaultAvatarSmall;
        ?>
        <a class="action profile" href="profile.php" aria-label="Profile" style="display:flex;align-items:center;gap:8px;">
            <img src="<?php echo $topAvatar; ?>" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid var(--border);" />
        </a>
        <?php if (isset($_SESSION['user'])): ?>
        <a class="action" href="logout.php" aria-label="Logout">Logout</a>
        <?php else: ?>
        <a class="action" href="login.php" aria-label="Login">Login</a>
        <?php endif; ?>
    </nav>
</header>
