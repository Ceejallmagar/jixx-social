<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user = $_SESSION['user'];
$suggestedFriends = $db->getSuggestedFriends($user['id'], 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Friends - Jixx</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--background);
            color: var(--text);
        }
        .suggest-card {
            padding: 30px;
            max-width: 600px;
            width: 100%;
            animation: fadeInSlideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        .suggest-card h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }
        .suggest-card p {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            text-align: center;
        }
        .friend-suggestion-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }
        .friend-suggestion-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .friend-suggestion-item .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .friend-suggestion-item img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        .friend-suggestion-item .name {
            font-weight: 600;
        }
        .add-friend-btn {
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .add-friend-btn:hover {
            transform: translateY(-1px);
        }
        .add-friend-btn.sent {
            background: var(--surface-strong);
            color: var(--text-dim);
            cursor: not-allowed;
        }
        .actions {
            text-align: center;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }
        .actions .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="suggest-card card">
        <h1>Connect with others</h1>
        <p>Here are some people you might know. Add them as friends to see their posts.</p>
        
        <div class="friend-suggestion-list">
            <?php foreach ($suggestedFriends as $friend): ?>
                <div class="friend-suggestion-item">
                    <div class="user-info">
                        <?php
                        $avatar = $friend['avatar'] ? htmlspecialchars($friend['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($friend['first_name'] . ' ' . $friend['last_name']) . '&background=0D8ABC&color=fff&size=48';
                        ?>
                        <img src="<?php echo $avatar; ?>" alt="Avatar">
                        <div>
                            <div class="name"><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></div>
                        </div>
                    </div>
                    <button class="add-friend-btn" data-user-id="<?php echo $friend['id']; ?>">Add Friend</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="index.php" class="btn" style="background: var(--surface-strong); color: var(--text);">Skip for Now</a>
            <a href="index.php" class="btn">Finish</a>
        </div>
    </div>

    <script>
        document.querySelectorAll('.add-friend-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                this.disabled = true;
                this.textContent = 'Request Sent';
                this.classList.add('sent');

                fetch('send_friend_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'to_user_id=' + encodeURIComponent(userId)
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        this.textContent = 'Error';
                        console.error('Failed to send friend request:', data.error);
                    }
                }).catch(err => {
                    this.textContent = 'Error';
                    console.error('Fetch error:', err);
                });
            });
        });
    </script>
</body>
</html>