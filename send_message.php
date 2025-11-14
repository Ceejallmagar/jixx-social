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

$receiverId = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$senderId = $_SESSION['user']['id'];

if (!$receiverId || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$db = new Database();

// Check if sender and receiver are friends
if (!$db->isFriend($senderId, $receiverId)) {
    echo json_encode(['success' => false, 'error' => 'You can only message friends']);
    exit;
}

// Encrypt the message
$encryptionKey = 'your-secret-key-here'; // Replace with your secure key management
$iv = openssl_random_pseudo_bytes(16);
$encryptedMessage = openssl_encrypt($message, 'AES-256-CBC', $encryptionKey, 0, $iv);

// Insert encrypted message
$stmt = $db->pdo->prepare("INSERT INTO encrypted_messages (sender_id, receiver_id, message, iv) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$senderId, $receiverId, $encryptedMessage, $iv]);

echo json_encode(['success' => $success]);
?>
