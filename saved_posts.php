<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$userId = $_SESSION['user']['id'];

// Fetch saved posts for the user
$stmt = $db->pdo->prepare("
    SELECT p.*, u.first_name, u.last_name, u.avatar
    FROM saved_posts sp
    JOIN posts p ON sp.post_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE sp.user_id = ?
    ORDER BY sp.created_at DESC
");
$stmt->execute([$userId]);
$savedPosts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Saved Posts • Jixx</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        body {
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        h1 {
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        .posts-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
        }
        .post-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 320px;
            box-sizing: border-box;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border);
        }
        .author-name {
            font-weight: 600;
            color: var(--text);
        }
        .post-content {
            padding: 12px;
            flex-grow: 1;
            color: var(--text);
        }
        .post-media img, .post-media video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
            max-height: 360px;
            object-fit: contain;
            background: #000;
        }
        .post-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
        }
        .view-post-btn {
            background: linear-gradient(135deg, #1e90ff, #5cc3ff);
            border: none;
            color: #012;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .view-post-btn:hover {
            background: linear-gradient(135deg, #0b6fb3, #3ca9ff);
        }
        @media (max-width: 640px) {
            .posts-grid {
                flex-direction: column;
                align-items: center;
            }
            .post-card {
                width: 100%;
                max-width: 320px;
            }
        }
    </style>
</head>
<body>
    <h1>Saved Posts</h1>
    <?php if (empty($savedPosts)): ?>
        <p style="text-align:center;">You have no saved posts.</p>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($savedPosts as $post): ?>
                <div class="post-card">
                    <div class="post-header">
                        <?php
                        $avatar = $post['avatar'] ? htmlspecialchars($post['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['first_name'] . ' ' . $post['last_name']) . '&background=0D8ABC&color=fff&size=48';
                        ?>
                        <img src="<?php echo $avatar; ?>" alt="Avatar" class="avatar" />
                        <div class="author-name"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></div>
                    </div>
                    <div class="post-content">
                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if ($post['media_url']): ?>
                            <div class="post-media">
                                <?php
                                $mediaUrl = htmlspecialchars($post['media_url']);
                                $ext = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
                                if (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                    <video controls>
                                        <source src="<?php echo $mediaUrl; ?>" type="video/<?php echo $ext; ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo $mediaUrl; ?>" alt="Post media" />
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="post-footer">
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="view-post-btn" target="_blank" rel="noopener noreferrer">View Post</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
