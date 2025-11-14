<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$userId = $_SESSION['user']['id'];

// Fetch notifications from the API
$notifications = [];
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/app_sijal/fetch_notifications.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-ID: ' . $userId
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success'] && isset($data['notifications'])) {
        $notifications = $data['notifications'];
    }
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notifications - Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
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
        <nav class="topbar__actions" aria-label="Primary">
            <a href="settings.php" class="action" aria-label="Settings">
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
        <section class="feed" aria-label="Notifications">
            <div class="card">
                <div class="card__title">Notifications</div>
                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="notification-item">
                            <p>No notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $notificationData = json_decode($notification['data'], true);
                            $message = isset($notificationData['message']) ? htmlspecialchars($notificationData['message']) : 'New notification';
                            $createdAt = date('M j, Y g:i A', strtotime($notification['created_at']));
                            ?>
                            <div class="notification-item">
                                <div class="notification-content">
                                    <p><?php echo $message; ?></p>
                                    <small style="color: var(--text-dim);"><?php echo $createdAt; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="script.js"></script>
</body>
<noscript>Enable JavaScript for full Jixx experience.</noscript>
</html>
