<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
// Support POST (AJAX) or GET fallback
$postId = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id'] ?? 0);
$post = $db->getPostById($postId);
if ($post && $post['user_id'] == $_SESSION['user']['id']) {
    $stmt = $db->pdo->prepare("DELETE FROM posts WHERE id=?");
    $stmt->execute([$postId]);
}
// If request expects JSON, return JSON for AJAX
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($accept, 'application/json') !== false || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Check if we came from the manage view to redirect back correctly.
if (isset($_GET['from']) && $_GET['from'] === 'manage') {
    // Redirect back to the manage posts view.
    header('Location: profile.php?view=manage');
} else {
    // Default redirect for other cases.
    header('Location: profile.php');
}
exit;
