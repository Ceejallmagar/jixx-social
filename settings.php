<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user = $db->getUserById($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-id="<?php echo $_SESSION['user']['id']; ?>">
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
                        <?php
                        $friends = $db->getFriends($_SESSION['user']['id']);
                        foreach ($friends as $friend):
                        ?>
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
            <button class="action chat-btn" aria-label="Chat" id="chatBtn">
                <span class="chat-icon">💬</span>
            </button>
            <a href="settings.php" class="action settings-btn" aria-label="Settings">
                <span class="settings-icon">⚙️</span>
            </a>

            <?php
            $currentUser = $db->getUserById($_SESSION['user']['id']);
            $defaultAvatarSmall = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['first_name'] . ' ' . $currentUser['last_name']) . '&background=0D8ABC&color=fff&size=48';
            $topAvatar = $currentUser['avatar'] ? htmlspecialchars($currentUser['avatar']) : $defaultAvatarSmall;
            ?>
            <a class="action profile" href="profile.php" aria-label="Profile" style="display:flex;align-items:center;gap:8px;">
                <img src="<?php echo $topAvatar; ?>" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid var(--border);" />
                <span style="font-weight:600;">Profile</span>
            </a>
            <?php if (isset($_SESSION['user'])): ?>
            <a class="action" href="logout.php" aria-label="Logout">Logout</a>
            <?php else: ?>
            <a class="action" href="login.php" aria-label="Login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="layout">
        <section class="feed" aria-label="Settings">
            <div class="settings-page">
                <div class="settings-header">
                    <h1>Settings</h1>
                    <p>Manage your account preferences and application settings</p>
                </div>

                <div class="settings-content">
                    <!-- General Settings -->
                    <div class="settings-section">
                        <h2>General Settings</h2>
                        <div class="setting-item">
                            <label for="themeSelect">Theme</label>
                            <select id="themeSelect">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                                <option value="system">System Default</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label for="languageSelect">Language</label>
                            <select id="languageSelect">
                                <option value="en">English</option>
                                <option value="es">Español</option>
                                <option value="fr">Français</option>
                                <option value="de">Deutsch</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label for="notificationsToggle">Notifications</label>
                            <input type="checkbox" id="notificationsToggle" checked>
                        </div>
                        <div class="setting-item">
                            <label>Account</label>
                            <div class="account-actions">
                                <button id="editProfileBtn">Edit Profile</button>
                                <button id="savedPostsBtn">Saved Posts</button>
                                <button id="logoutBtn">Logout</button>
                                <button id="deleteAccountBtn" class="danger">Delete Account</button>
                            </div>
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="settings-section">
                        <h2>Display</h2>
                        <div class="setting-item">
                            <label for="fontSizeSlider">Font Size</label>
                            <input type="range" id="fontSizeSlider" min="12" max="24" value="16">
                            <span id="fontSizeValue">16px</span>
                        </div>
                        <div class="setting-item">
                            <label for="brightnessSlider">Screen Brightness</label>
                            <input type="range" id="brightnessSlider" min="0" max="100" value="100">
                            <span id="brightnessValue">100%</span>
                        </div>
                        <div class="setting-item">
                            <label for="resolutionSelect">Resolution</label>
                            <select id="resolutionSelect">
                                <option value="auto">Auto</option>
                                <option value="1920x1080">1920x1080</option>
                                <option value="1366x768">1366x768</option>
                                <option value="1280x720">1280x720</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label for="fullscreenToggle">Full-screen Mode</label>
                            <input type="checkbox" id="fullscreenToggle">
                        </div>
                    </div>

                    <!-- Privacy and Security -->
                    <div class="settings-section">
                        <h2>Privacy and Security</h2>
                        <div class="setting-item">
                            <label for="twoFactorToggle">Two-Factor Authentication</label>
                            <input type="checkbox" id="twoFactorToggle">
                        </div>
                        <div class="setting-item">
                            <label>Password</label>
                            <button id="changePasswordBtn">Change Password</button>
                        </div>
                        <div class="setting-item">
                            <label>Permissions</label>
                            <button id="managePermissionsBtn">Manage Permissions</button>
                        </div>
                        <div class="setting-item">
                            <label>Privacy Policy</label>
                            <a href="#" id="privacyPolicyLink">View Privacy Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="script.js"></script>
    <script>
        // Settings page specific functionality
        document.addEventListener('DOMContentLoaded', () => {
            // Theme switching
            const themeSelect = document.getElementById('themeSelect');
            themeSelect.addEventListener('change', (e) => {
                const theme = e.target.value;
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
            });

            // Language selection
            const languageSelect = document.getElementById('languageSelect');
            languageSelect.addEventListener('change', (e) => {
                const language = e.target.value;
                localStorage.setItem('language', language);
                alert('Language changed to ' + language + '. Please refresh the page to apply changes.');
            });

            // Notifications toggle
            const notificationsToggle = document.getElementById('notificationsToggle');
            notificationsToggle.addEventListener('change', (e) => {
                const enabled = e.target.checked;
                localStorage.setItem('notifications', enabled);
                if (enabled) {
                    if ('Notification' in window) {
                        Notification.requestPermission();
                    }
                }
            });

            // Font size adjustment
            const fontSizeSlider = document.getElementById('fontSizeSlider');
            const fontSizeValue = document.getElementById('fontSizeValue');
            fontSizeSlider.addEventListener('input', (e) => {
                const size = e.target.value;
                fontSizeValue.textContent = size + 'px';
                document.documentElement.style.fontSize = size + 'px';
                localStorage.setItem('fontSize', size);
            });

            // Brightness adjustment
            const brightnessSlider = document.getElementById('brightnessSlider');
            const brightnessValue = document.getElementById('brightnessValue');
            brightnessSlider.addEventListener('input', (e) => {
                const brightness = e.target.value;
                brightnessValue.textContent = brightness + '%';
                document.documentElement.style.filter = `brightness(${brightness}%)`;
                localStorage.setItem('brightness', brightness);
            });

            // Fullscreen toggle
            const fullscreenToggle = document.getElementById('fullscreenToggle');
            fullscreenToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                }
            });

            // Account actions
            document.getElementById('editProfileBtn').addEventListener('click', () => {
                window.location.href = 'edit_profile.php';
            });

            document.getElementById('savedPostsBtn').addEventListener('click', () => {
                window.location.href = 'saved_posts.php';
            });

            document.getElementById('logoutBtn').addEventListener('click', () => {
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            });

            document.getElementById('deleteAccountBtn').addEventListener('click', () => {
                if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                    alert('Account deletion functionality would be implemented here.');
                }
            });

            document.getElementById('changePasswordBtn').addEventListener('click', () => {
                window.location.href = 'password_change.php';
            });

            document.getElementById('managePermissionsBtn').addEventListener('click', () => {
                alert('Permissions management functionality would be implemented here.');
            });

            document.getElementById('privacyPolicyLink').addEventListener('click', (e) => {
                e.preventDefault();
                alert('Privacy policy would be displayed here.');
            });

            // Load saved settings
            loadSettings();
        });

        function loadSettings() {
            // Theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.getElementById('themeSelect').value = savedTheme;
            document.documentElement.setAttribute('data-theme', savedTheme);

            // Language
            const savedLanguage = localStorage.getItem('language') || 'en';
            document.getElementById('languageSelect').value = savedLanguage;

            // Notifications
            const savedNotifications = localStorage.getItem('notifications') === 'true';
            document.getElementById('notificationsToggle').checked = savedNotifications;

            // Font size
            const savedFontSize = localStorage.getItem('fontSize') || '16';
            document.getElementById('fontSizeSlider').value = savedFontSize;
            document.getElementById('fontSizeValue').textContent = savedFontSize + 'px';
            document.documentElement.style.fontSize = savedFontSize + 'px';

            // Brightness
            const savedBrightness = localStorage.getItem('brightness') || '100';
            document.getElementById('brightnessSlider').value = savedBrightness;
            document.getElementById('brightnessValue').textContent = savedBrightness + '%';
            document.documentElement.style.filter = `brightness(${savedBrightness}%)`;
        }
    </script>
</body>
</html>
