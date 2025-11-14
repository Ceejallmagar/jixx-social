<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];
$db = new Database();

$stmt = $db->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
