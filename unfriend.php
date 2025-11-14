<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$friendId = intval($_POST['friend_id'] ?? 0);
$userId = $_SESSION['user']['id'];

if (!$friendId) {
    echo json_encode(['success' => false, 'error' => 'Invalid friend ID']);
    exit;
}

$db = new Database();

// Delete friendship regardless of direction
$stmt = $db->pdo->prepare("DELETE FROM friendships WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
$success = $stmt->execute([$userId, $friendId, $friendId, $userId]);

echo json_encode(['success' => $success]);
?>
