<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Invalid post id']);
    exit;
}

$db = new Database();
$userId = $_SESSION['user']['id'];

// Explicit toggle: check existence then insert/delete
$stmt = $db->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->execute([$userId, $postId]);
$exists = (bool)$stmt->fetch();
if ($exists) {
    $del = $db->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $ok = $del->execute([$userId, $postId]);
    $liked = false;
} else {
    $ins = $db->pdo->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)");
    $ok = $ins->execute([$userId, $postId]);
    $liked = true;
}

// Return fresh like count
$cnt = $db->pdo->prepare("SELECT COUNT(*) as c FROM likes WHERE post_id = ?");
$cnt->execute([$postId]);
$row = $cnt->fetch();
$like_count = $row ? intval($row['c']) : 0;

echo json_encode(['success' => (bool)$ok, 'like_count' => $like_count, 'liked' => (bool)$liked]);
exit;
