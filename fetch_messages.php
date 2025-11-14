<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$otherUserId = intval($_GET['user_id'] ?? 0);
$userId = $_SESSION['user']['id'];

if (!$otherUserId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

$db = new Database();

// Check if users are friends
if (!$db->isFriend($userId, $otherUserId)) {
    echo json_encode(['success' => false, 'error' => 'You can only view messages with friends']);
    exit;
}

// Fetch encrypted messages between the two users ordered by created_at ascending
$stmt = $db->pdo->prepare("
    SELECT m.*, u.first_name, u.last_name
    FROM encrypted_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
$encryptedMessages = $stmt->fetchAll();

// Decrypt messages
$encryptionKey = 'your-secret-key-here'; // Replace with your secure key management
$messages = [];
foreach ($encryptedMessages as $msg) {
    $decryptedMessage = openssl_decrypt($msg['message'], 'AES-256-CBC', $encryptionKey, 0, $msg['iv']);
    $messages[] = [
        'id' => $msg['id'],
        'sender_id' => $msg['sender_id'],
        'receiver_id' => $msg['receiver_id'],
        'message' => $decryptedMessage,
        'created_at' => $msg['created_at'],
        'first_name' => $msg['first_name'],
        'last_name' => $msg['last_name']
    ];
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>
