<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

$userId = $_SESSION['user']['id'];
$postId = (int) $_POST['post_id'];

$db = new Database();

try {
    $result = $db->toggleSavePost($userId, $postId);
    $isSaved = $db->isPostSaved($userId, $postId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'saved' => $isSaved,
            'message' => $isSaved ? 'Post saved!' : 'Post unsaved!'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to toggle save status']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
