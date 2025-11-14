<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($postId <= 0) {
    header('Location: profile.php');
    exit;
}
$post = $db->getPostById($postId);
if (!$post || $post['user_id'] != $_SESSION['user']['id']) {
    // Unauthorized or not found — redirect back to profile for safety
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $privacy = $_POST['privacy'];
    $stmt = $db->pdo->prepare("UPDATE posts SET content=?, privacy=? WHERE id=?");
    $stmt->execute([$content, $privacy, $postId]);
    header('Location: profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Post • Jixx</title>
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
    </header>

    <div class="layout">
        <div class="feed">
            <div class="edit-post card">
                <h2 class="card__title">Edit Post</h2>
                <form method="POST" style="padding: 16px; display: grid; gap: 16px;">
                    <div>
                        <label for="content" style="display: block; margin-bottom: 8px; color: var(--text-dim);">Caption</label>
                        <textarea name="content" id="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    <div>
                        <label for="privacy" style="display: block; margin-bottom: 8px; color: var(--text-dim);">Privacy</label>
                        <select name="privacy" id="privacy">
                            <option value="public" <?php echo ($post['privacy'] === 'public') ? 'selected' : ''; ?>>Public</option>
                            <option value="friends" <?php echo ($post['privacy'] === 'friends') ? 'selected' : ''; ?>>Friends</option>
                            <option value="private" <?php echo ($post['privacy'] === 'private') ? 'selected' : ''; ?>>Only Me</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                        <a href="profile.php" class="btn btn--ghost">Cancel</a>
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
