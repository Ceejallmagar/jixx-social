<?php
session_start();
require_once 'database.php';
// Prevent aggressive caching so updated like counts appear after actions
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$posts = $db->getPosts();
$friends = $db->getFriends($_SESSION['user']['id']);
$pendingCount = count($db->getPendingFriendRequests($_SESSION['user']['id']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="fix_nav_icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        /* Force FontAwesome icons to display as inline-block and visible */
        .fa, .fas, .fa-solid, .fa-home, .fa-user, .fa-message, .fa-bell, .fa-gear, .fa-comment {
            display: inline-block !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            -moz-osx-font-smoothing: grayscale !important;
            visibility: visible !important;
        }
    </style>
</head>
<body data-user-id="<?php echo $_SESSION['user']['id']; ?>">
        <!-- Loader -->
        <div id="globalLoader" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:var(--surface);padding:32px 40px;border-radius:16px;box-shadow:0 2px 16px #0003;text-align:center;">
                <div class="loader-spinner" style="margin-bottom:16px;"></div>
                <div style="color:var(--text);font-size:18px;font-weight:500;">Loading...</div>
            </div>
        </div>
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
                            <button type="button" id="chatEmojiBtn" class="emoji-btn">😊</button>
                            <input type="text" id="chatMessageInput" placeholder="Type a message..." required />
                            <button type="submit">Send</button>
                            <emoji-picker id="chatEmojiPicker"></emoji-picker>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <nav class="topbar__actions" aria-label="Primary">
            <a href="chat.php" class="action chat-btn" aria-label="Chat" id="chatBtn">
                <i class="fa-solid fa-comment"></i>
                        </a>
            <a href="settings.php" class="action" aria-label="Settings">
                <i class="fa-solid fa-gear"></i>
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

    <main class="layout">
        <section class="feed" aria-label="Feed">
            <nav class="topbar__nav" aria-label="Main Navigation" style="display: flex; gap: 16px;">
                <?php
                $user = $db->getUserById($_SESSION['user']['id']);
                ?>
                <a href="index.php" class="nav-link" title="Home" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="profile.php?id=<?php echo $user['id']; ?>" class="nav-link" title="Profile" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </a>
                <a href="friend_requests.php" class="nav-link" title="Friends" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-user"></i>
                    <span>Friends</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge" style="margin-left: 4px;">+<?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="notifications.php" class="nav-link" title="Notifications" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notifications-icon">🔔</span>
                </a>
            </nav>

            <div class="composer card">
                <form method="POST" action="create_post.php" enctype="multipart/form-data" id="postForm">
                    <input type="text" name="content" placeholder="What's on your mind, <?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>?" id="postContent" />
                    <input type="file" name="media" id="mediaInput" accept="image/*,video/*" style="display: none;" />

                    <div class="media-preview" id="mediaPreview" style="display: none;">
                        <div class="media-preview__content">
                            <img id="previewImage" style="max-width: 100%; max-height: 200px; border-radius: 8px;" />
                            <video id="previewVideo" style="max-width: 100%; max-height: 200px; border-radius: 8px;" controls playsinline></video>
                            <button type="button" class="remove-media" id="removeMedia">✕</button>
                        </div>
                    </div>
                    
                    <div class="privacy-wrapper">
                        <div class="privacy-pill" title="Privacy">
                            <span class="icon">🔒</span>
                            <select name="privacy" id="privacySelect">
                                <option value="public">Public</option>
                                <option value="friends">Friends</option>
                                <option value="private">Only Me</option>
                            </select>
                        </div>
                    </div>
                    <div class="composer__actions">
                        <button type="submit" id="postBtn">Post</button>
                        <button type="button" id="photoVideoBtn">📷 Photo/Video</button>
                    </div>
                </form>
            </div>

            <?php foreach ($posts as $post): ?>
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
                    
                    </div>
                </header>
                <div class="post-clickable-area" data-href="view_post.php?id=<?php echo $post['id']; ?>" style="cursor:pointer;">
                    <?php if ($post['media_url']): ?>
                    <figure class="post__media">
                        <?php
                        $mediaUrl = htmlspecialchars($post['media_url']);
                        // Get extension robustly (handle query params, uppercase)
                        $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                        $videoExts = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
                        if (in_array($ext, $videoExts)) {
                        ?>
                        <video src="<?php echo $mediaUrl; ?>" controls playsinline style="width:100%;max-height:100%;" preload="metadata"></video>
                        <?php
                        } else {
                        ?>
                            <img src="<?php echo $mediaUrl; ?>" alt="Post media" style="width:100%;max-height:100%;" loading="lazy" class="post-image" />
                        <?php } ?>
                    </figure>
                    <?php endif; ?>
                    <div class="post__content">
                        <?php
                        $content = $post['content'];
                        if (filter_var($content, FILTER_VALIDATE_URL)) {
                            echo '<a href="' . htmlspecialchars($content) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($content) . '</a>';
                        } else {
                            echo htmlspecialchars($content);
                        }
                        ?>
                    </div>
                </div> <!-- closing post-clickable-area -->
                <footer class="post__footer">
                    <?php $liked = isset($_SESSION['user']) ? $db->isLiked($_SESSION['user']['id'], $post['id']) : false; ?>
                    <button class="like-btn" data-post-id="<?php echo $post['id']; ?>" aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>">
                        <?php if ($liked): ?>
                            👍 Liked (<?php echo $post['like_count']; ?>)
                        <?php else: ?>
                            👍 Like (<?php echo $post['like_count']; ?>)
                        <?php endif; ?>
                    </button>
                    <button class="share-btn">↗ Share</button>
                </footer>
            </article>
            <?php endforeach; ?>
        </section>

        <aside class="sidebar right" aria-label="Friends">
            <div class="card friends">
                <div class="card__title">Friends</div>
                <ul>
                    <?php foreach ($friends as $friend): ?>
                    <li style="display:flex;align-items:center;gap:8px;padding:8px 0;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:8px;">
                        <?php
                        $friendAvatar = $friend['avatar'] ? htmlspecialchars($friend['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($friend['first_name'] . ' ' . $friend['last_name']) . '&background=0D8ABC&color=fff&size=32';
                        ?>
                        <img src="<?php echo $friendAvatar; ?>" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" />
                        <span><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></span>
                        </div>
                        <button class="btn btn--ghost unfriend-btn" data-friend-id="<?php echo $friend['id']; ?>">Unfriend</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </main>

    <script src="script.js"></script>
    <script>
    // Override the default share button functionality
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Find the parent post article to get the post ID
            const postArticle = e.target.closest('article.post');
            if (!postArticle) return;

            const postId = postArticle.dataset.postId;
            const newCaption = prompt("Add your caption (optional):");

            // Proceed if the user didn't click "Cancel"
            if (newCaption !== null) {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('caption', newCaption);

                fetch('share_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Post shared successfully!');
                        window.location.reload(); // Reload to see the new post
                    } else {
                        alert('Error sharing post: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => console.error('Share post error:', error));
            }
        });
    });

    document.querySelectorAll('.unfriend-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Are you sure you want to unfriend this user?')) return;
            btn.disabled = true;
            btn.textContent = 'Removing...';
            fetch('unfriend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'friend_id=' + encodeURIComponent(btn.dataset.friendId)
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    btn.closest('li').remove();
                } else {
                    alert('Failed to unfriend user');
                    btn.disabled = false;
                    btn.textContent = 'Unfriend';
                }
            }).catch(() => {
                alert('Request failed');
                btn.disabled = false;
                btn.textContent = 'Unfriend';
            });
        });
    });

    // Composer media upload functionality
    document.addEventListener('DOMContentLoaded', () => {
        const photoVideoBtn = document.getElementById('photoVideoBtn');
        const mediaInput = document.getElementById('mediaInput');
        const mediaPreview = document.getElementById('mediaPreview');
        const previewImage = document.getElementById('previewImage');
        const previewVideo = document.getElementById('previewVideo');
        const removeMedia = document.getElementById('removeMedia');

        if (photoVideoBtn) {
            photoVideoBtn.addEventListener('click', () => {
                mediaInput.click();
            });
        }

        if (mediaInput) {
            mediaInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const fileUrl = URL.createObjectURL(file);
                    mediaPreview.style.display = 'block';

                    if (file.type.startsWith('image/')) {
                        previewImage.src = fileUrl;
                        previewImage.style.display = 'block';
                        previewVideo.style.display = 'none';
                    } else if (file.type.startsWith('video/')) {
                        previewVideo.src = fileUrl;
                        previewVideo.style.display = 'block';
                        previewImage.style.display = 'none';
                    }
                }
            });
        }

        if (removeMedia) {
            removeMedia.addEventListener('click', () => {
                mediaInput.value = ''; // Clear the selected file
                previewImage.src = '';
                previewVideo.src = '';
                mediaPreview.style.display = 'none';
            });
        }
    });
    </script>
</body>
<noscript>Enable JavaScript for full Jixx experience.</noscript>
</html>
