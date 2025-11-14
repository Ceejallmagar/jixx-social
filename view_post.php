<?php
session_start();
require_once 'database.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$db = new Database();
if ($postId <= 0) {
    // Bad request — show a friendly message instead of a PHP notice
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Post not found</title><link rel="stylesheet" href="styles.css" /></head><body><main class="card" style="max-width:600px;margin:40px auto;padding:20px;text-align:center;"><h2>Post not found</h2><p>The requested post is missing or the link is invalid.</p><p><a href="index.php">Return to feed</a></p></main></body></html>';
    exit;
}

$post = $db->getPostById($postId);
if (!$post) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Post not found</title><link rel="stylesheet" href="styles.css" /></head><body><main class="card" style="max-width:600px;margin:40px auto;padding:20px;text-align:center;"><h2>Post not found</h2><p>The post may have been removed or is not accessible.</p><p><a href="index.php">Return to feed</a></p></main></body></html>';
    exit;
}
$user = $db->getUserById($post['user_id']);
// enforce privacy: check if current viewer can see the post
$viewerId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
$allowed = false;
if ($post['privacy'] === 'public') $allowed = true;
elseif ($post['privacy'] === 'private') {
    if ($viewerId && $viewerId === $post['user_id']) $allowed = true;
} elseif ($post['privacy'] === 'friends') {
    if ($viewerId && ($viewerId === $post['user_id'] || $db->isFriend($viewerId, $post['user_id']))) $allowed = true;
}
if (!$allowed) die('This post is not available.');
$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=0D8ABC&color=fff&size=128';
$avatar = $user['avatar'] ? htmlspecialchars($user['avatar']) : $defaultAvatar;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Post</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <main class="view-post card" style="max-width:600px;margin:40px auto;">
        <header style="display:flex;align-items:center;gap:16px;">
            <a href="profile.php?id=<?php echo $user['id']; ?>" style="display:flex;align-items:center;gap:16px;text-decoration:none;color:inherit;">
                <img src="<?php echo $avatar; ?>" alt="Profile Picture" style="width:60px;height:60px;border-radius:50%;box-shadow:0 2px 8px #0002; position:relative; left:5px; top:5px;"  />
                <div>
                    <div style="font-weight:600;font-size:18px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div style="color:#888;font-size:14px;">Posted on <?php echo date('M j, Y', strtotime($post['created_at'])); ?></div>
                </div>
            </a>
        </header>
        <div style="margin:24px 0;font-size:18px; padding:5px;">
            <?php echo htmlspecialchars($post['content']); ?>
        </div>
        <?php if ($post['media_url']): ?>
            <?php
            $mediaUrl = htmlspecialchars($post['media_url']);
            $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $videoExts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
            if (in_array($ext, $videoExts)) {
            ?>
                <video src="<?php echo $mediaUrl; ?>" controls muted playsinline style="max-width:100%;max-height:320px;border-radius:12px;background:#000;"></video>
            <?php } else { ?>
                <img src="<?php echo $mediaUrl; ?>" alt="Post media" style="max-width:100%;max-height:320px;border-radius:12px; position:relative; left:5px;" />
            <?php } ?>
        <?php endif; ?>
        <div style="margin-top:16px;color:#888;">Privacy: <?php echo ucfirst($post['privacy']); ?></div>

        <a href="profile.php?id=<?php echo $user['id']; ?>" style="display:block;margin-top:24px;text-align:center; background-color: #2d8fc8; border radious:5px; display:inline-block; position:relative; left:220px;">Back to Profile</a>
    </main>
    <script src="script.js"></script>
</body>
</html>
