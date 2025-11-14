<?php
session_start();
require_once 'database.php';
// Prevent aggressive caching so updated like counts appear after actions
header('Cache-Control: no-store, no-cache, must-revalidate');

$isLoggedIn = isset($_SESSION['user']);

if (!$isLoggedIn && !isset($_GET['id'])) {
    // If not logged in and no profile ID is specified, redirect to login
    header('Location: login.php');
    exit;
}

$userId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user']['id'];
$db = new Database();
$user = $db->getUserById($userId);
$isOwnProfile = ($isLoggedIn && $userId === $_SESSION['user']['id']);
$viewerId = $isLoggedIn ? $_SESSION['user']['id'] : null;
$posts = $db->getPostsForProfile($userId, $viewerId);
$friendCount = $db->getFriendCount($userId);

// Default profile picture (Facebook style)
$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=0D8ABC&color=fff&size=128';
$avatar = $user['avatar'] ? htmlspecialchars($user['avatar']) : $defaultAvatar;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?php echo htmlspecialchars($user['first_name'] . "'s Profile"); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />

    <style>
        /* CSS to center tabs and fix mobile post size */
        
        /* This CSS targets the profile tabs to center them on all screen sizes */
        .profile-tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }

        /* This CSS ensures posts take up full width on small screens for better visibility */
        .profile-posts-grid {
           
            gap: 20px;
            grid-template-columns: 1fr; /* Ensures a single column on all screen sizes */
            max-width: 960px; /* Constrains the grid width on larger screens */
            margin: auto; /* Centers the grid */
            padding: 0 10px; /* Adds padding on the sides for mobile */
        }

        .profile-post-item {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        /* This CSS ensures the post card itself expands to fill its container */
        .post.card {
            width: 100%;
        }

        /* A rule for managing posts to ensure they also stack properly */
        @media (max-width: 768px) {
            .manage-posts-list {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">
            <a href="index.php" class="brand-link">
                <div class="logo"></div>
                <span class="brand">Jixx</span>
            </a>
        </div>
        <a href="settings.php" class="action" aria-label="Settings">
            <span class="settings-icon">⚙️</span>
        </a>
        <nav class="topbar__actions" aria-label="Primary">
            <?php
            $currentUser = $db->getUserById($_SESSION['user']['id']);
            $defaultAvatarSmall = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['first_name'] . ' ' . $currentUser['last_name']) . '&background=0D8ABC&color=fff&size=48';
            $topAvatar = $currentUser['avatar'] ? htmlspecialchars($currentUser['avatar']) : $defaultAvatarSmall;
            ?>
            <a class="action" href="profile.php" aria-label="Profile" style="display:flex;align-items:center;gap:8px;">
                <img src="<?php echo $topAvatar; ?>" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid var(--border);" />
                <span style="font-weight:600;">Profile</span>
            </a>
            <a class="action" href="logout.php" aria-label="Logout">Logout</a>
        </nav>
    </header>

    <div class="profile-section-full-width">
        <div class="profile-section card">
            <div class="profile-picture" style="text-align:center;">
                <img src="<?php echo $avatar; ?>" alt="Profile Picture" style="width:128px;height:128px;border-radius:50%;margin:0 auto;box-shadow:0 2px 16px #0003;" />
            </div>
            <div class="profile-details" style="text-align:center;margin-top:16px;">
                <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                <div class="profile-meta">
                    Friends: <?php echo $friendCount; ?>
                </div>
                <div class="profile-bio" style="margin:12px 0;">
                    <?php echo htmlspecialchars($user['bio'] ?? 'No bio yet.'); ?>
                </div>
                <?php if ($isOwnProfile): ?>
                    <a href="edit_profile.php" class="edit-profile-btn">✏️ Edit Profile</a>
                <?php else: ?>
                    <?php if ($isLoggedIn): ?>
                        <?php
                        $friendshipStatus = $db->getFriendshipStatus($viewerId, $userId);
                        if ($friendshipStatus === 'accepted') {
                            echo '<button class="friend-request-btn" disabled>✔️ Friends</button>';
                        } elseif ($friendshipStatus === 'pending') {
                            // Check if current user sent the request or received it
                            $sentRequestStmt = $db->pdo->prepare("SELECT 1 FROM friendships WHERE user1_id = ? AND user2_id = ? AND status = 'pending'");
                            $sentRequestStmt->execute([$viewerId, $userId]);
                            $sentRequest = $sentRequestStmt->fetch() !== false;
                            if ($sentRequest) {
                                ?>
                                <form method="POST" action="cancel_friend_request.php" style="display:inline;">
                                    <input type="hidden" name="to_user_id" value="<?php echo $userId; ?>" />
                                    <button type="submit" class="friend-request-btn cancel-request-btn">❌ Cancel Request</button>
                                </form>
                                <?php
                            } else {
                                echo '<a href="friend_requests.php" class="friend-request-btn">Respond to Request</a>';
                            }
                        } else {
                            ?>
                            <form method="POST" action="send_friend_request.php">
                                <input type="hidden" name="to_user_id" value="<?php echo $userId; ?>" />
                                <button type="submit" class="friend-request-btn">➕ Add Friend</button>
                            </form>
                            <?php
                        }
                        ?>
                    <?php else: ?>
                        <a href="login.php" class="friend-request-btn">Login to Add Friend</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($isOwnProfile && ($user['hobbies'] || $user['social_links'])): ?>
                <div class="profile-additional-info" style="margin-top:20px;text-align:left;max-width:300px;margin-left:auto;margin-right:auto;">
                    <?php if ($user['hobbies']): ?>
                    <div style="margin-bottom:12px;">
                        <h4 class="additional-info-title">Hobbies</h4>
                        <p style="margin:0;font-size:14px;">
                            <?php
                            $hobbies = json_decode($user['hobbies'], true);
                            if ($hobbies && is_array($hobbies)) {
                                $hobbyStrings = [];
                                foreach ($hobbies as $hobby) {
                                    if (isset($hobby['emoji']) && isset($hobby['word'])) {
                                        $hobbyStrings[] = htmlspecialchars($hobby['emoji']) . ' ' . htmlspecialchars($hobby['word']);
                                    }
                                }
                                echo implode(', ', $hobbyStrings);
                            } else {
                                echo htmlspecialchars($user['hobbies']);
                            }
                            ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($user['social_links']): ?>
                    <div>
                        <h4 class="additional-info-title">Social Links</h4>
                        <?php
                        $socialLinks = json_decode($user['social_links'], true);
                        if ($socialLinks && is_array($socialLinks)):
                        ?>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <?php foreach ($socialLinks as $link): ?>
                            <?php
                            $url = $link['url'] ?? '';
                            if (!preg_match('/^https?:\/\//', $url)) {
                                $url = 'https://' . $url;
                            }
                            ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px;padding:4px 8px;background:#f0f0f0;border-radius:4px;text-decoration:none;color:#333;">
                                <?php echo htmlspecialchars($link['label'] ?? 'Link'); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <main class="layout">
        <section class="feed" aria-label="Profile Feed">
            <?php if ($isOwnProfile): ?>
           <div class="profile-tabs">
                <a href="profile.php?id=<?php echo $userId; ?>" class="profile-tab <?php echo !isset($_GET['view']) ? 'active' : ''; ?>">Posts</a>
                <a href="profile.php?id=<?php echo $userId; ?>&view=manage" class="profile-tab <?php echo (isset($_GET['view']) && $_GET['view'] === 'manage') ? 'active' : ''; ?>">Manage Posts</a>
            </div>

            <style>
                .profile-tabs {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin-top: 10px;
                }

                .profile-tab {
                    background-color: #007bff;
                    color: #fff;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 0 5px;
                    font-family: sans-serif;
                    transition: background-color 0.3s ease;

                }

                .profile-tab:hover {
                    background-color: #25bdfaff;
                }

                .profile-tab.active {
                    background-color: #05a7e7ff;
                }
            </style>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $_GET['view'] === 'manage' && $isOwnProfile): ?>
                <div class="manage-posts-section card">
                    <h3 class="card__title">Manage Your Posts</h3>
                    <?php if (empty($posts)): ?>
                        <p style="padding: 16px; text-align: center; color: var(--text-dim);">You haven't posted anything yet.</p>
                    <?php else: ?>
                        <ul class="manage-posts-list">
                            <?php foreach ($posts as $post): ?>
                                <li class="manage-post-item <?php echo $post['media_url'] ? 'has-media' : ''; ?>">
                                    <?php if ($post['media_url']): ?>
                                        <div class="manage-post-item__thumbnail">
                                            <?php
                                            $mediaUrl = htmlspecialchars($post['media_url']);
                                            $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                            $videoExts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
                                            if (in_array($ext, $videoExts)) {
                                                // Append #t=0.1 to show the first frame as a thumbnail for videos
                                                echo '<video src="' . $mediaUrl . '#t=0.1" preload="metadata"></video>';
                                            } else {
                                                echo '<img src="' . $mediaUrl . '" alt="Post media" loading="lazy" />';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="manage-post-item__content">
                                        <p class="manage-post-item__text">
                                            <?php echo !empty($post['content']) ? htmlspecialchars($post['content']) : '<em class="no-caption">No caption</em>'; ?>
                                        </p>
                                        <small class="manage-post-item__meta">
                                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?> &middot; <?php echo ucfirst($post['privacy']); ?>
                                        </small>
                                    </div>
                                    <div class="manage-post-item__actions">
                                        <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn--ghost">Edit</a>
                                        <a href="delete_post.php?id=<?php echo $post['id']; ?>&from=manage" class="btn btn--ghost delete-post-btn">Delete</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (empty($posts)): ?>
                <div class="card" style="padding: 20px; text-align: center; color: var(--text-dim);">
                    This user hasn't posted anything yet.
                </div>
                <?php else: ?>
                <div class="profile-posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="profile-post-item">
                            <article class="post card" data-post-id="<?php echo $post['id']; ?>" data-author-id="<?php echo $post['user_id']; ?>">
                                <header class="post__header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <a href="profile.php?id=<?php echo $post['user_id']; ?>" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;">
                                            <?php
                                            $postAvatar = $post['avatar'] ? htmlspecialchars($post['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($post['first_name'] . ' ' . $post['last_name']) . '&background=0D8ABC&color=fff&size=40';
                                            ?>
                                            <img src="<?php echo $postAvatar; ?>" alt="Avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />
                                            <div>
                                                <div class="post__author"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></div>
                                                <div class="post__meta"><?php echo date('M j', strtotime($post['created_at'])); ?> · <?php echo ucfirst($post['privacy']); ?></div>
                                            </div>
                                        </a>
                                    </div>
                                </header>
                                <div class="post-clickable-area" data-href="view_post.php?id=<?php echo $post['id']; ?>" style="cursor:pointer;">
                                    <?php if ($post['media_url']): ?>
                                    <figure class="post__media">
                                        <?php
                                        $mediaUrl = htmlspecialchars($post['media_url']);
                                        $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        $videoExts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
                                        if (in_array($ext, $videoExts)) {
                                            echo '<video src="' . $mediaUrl . '" controls playsinline style="width:100%;max-height:100%;" preload="metadata"></video>';
                                        } else {
                                            echo '<img src="' . $mediaUrl . '" alt="Post media" style="width:100%;max-height:100%;" loading="lazy" class="post-image" />';
                                        }
                                        ?>
                                    </figure>
                                    <?php endif; ?>
                                    <div class="post__content">
                                        <?php echo htmlspecialchars($post['content']); ?>
                                    </div>
                                </div>
                                <footer class="post__footer">
                                    <?php
                                    $liked = isset($_SESSION['user']) ? $db->isLiked($viewerId, $post['id']) : false;
                                    ?>
                                    <button class="like-btn" data-post-id="<?php echo $post['id']; ?>" aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>">
                                        👍 <?php echo $liked ? 'Liked' : 'Like'; ?> (<?php echo $post['like_count']; ?>)
                                    </button>
                                    <button class="share-btn">↗ Share</button>
                                </footer>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
<script src="script.js"></script>
</html>