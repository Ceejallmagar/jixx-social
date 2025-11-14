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
$from = intval($_POST['from_user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$to = $_SESSION['user']['id'];
if (!$from || !in_array($action, ['accept','decline'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}
$db = new Database();
if ($action === 'accept') {
    $ok = $db->acceptFriendRequest($from, $to);
    if ($ok) {
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'DB failed']);
    exit;
} else {
    $ok = $db->declineFriendRequest($from, $to);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}
