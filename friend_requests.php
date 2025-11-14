<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$pending = $db->getPendingFriendRequests($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Friend Requests</title>
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
    <nav class="topbar__actions" aria-label="Primary">
        <a class="action" href="profile.php">Profile</a>
        <a class="action" href="logout.php">Logout</a>
    </nav>
</header>
<main class="layout">
    <section class="feed card" style="grid-column:2;">
        <h2 style="margin:12px 0;">Friend Requests</h2>
        <?php if (empty($pending)): ?>
            <div class="card" style="padding:16px;">No pending friend requests.</div>
        <?php else: ?>
            <div style="display:grid;gap:12px;">
            <?php foreach ($pending as $req): ?>
                <div class="card" style="display:flex;align-items:center;gap:12px;padding:12px;">
                    <img src="<?php echo $req['avatar'] ? htmlspecialchars($req['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($req['first_name'].' '.$req['last_name']).'&background=0D8ABC&color=fff&size=64'; ?>" alt="avatar" style="width:56px;height:56px;border-radius:50%;object-fit:cover;" />
                    <div style="flex:1;">
                        <div style="font-weight:600"><?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></div>
                        <div style="color:var(--text-dim);font-size:13px;margin-top:4px;">Requested on <?php echo date('M j, Y', strtotime($req['created_at'])); ?></div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn" onclick="respondRequest(<?php echo $req['from_user_id']; ?>, 'accept', this)">Accept</button>
                        <button class="btn btn--ghost" onclick="respondRequest(<?php echo $req['from_user_id']; ?>, 'decline', this)">Decline</button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
function respondRequest(fromId, action, btn) {
    if (!confirm('Proceed?')) return;
    btn.disabled = true;
    btn.textContent = 'Processing...';
    fetch('respond_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: `from_user_id=${encodeURIComponent(fromId)}&action=${encodeURIComponent(action)}`
    }).then(r => r.json()).then(data => {
        if (data.success) {
            // remove the parent card
            const card = btn.closest('.card');
            if (card) card.remove();
        } else {
            alert('Failed to process request');
            btn.disabled = false;
            btn.textContent = action === 'accept' ? 'Accept' : 'Decline';
        }
    }).catch(err => { alert('Request failed'); btn.disabled=false; btn.textContent = action === 'accept' ? 'Accept' : 'Decline'; });
}
</script>
</body>
</html>
