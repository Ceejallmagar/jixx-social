<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromUserId = $_SESSION['user']['id'];
    $toUserId = isset($_POST['to_user_id']) ? intval($_POST['to_user_id']) : 0;

    if ($toUserId > 0) {
        $db = new Database();
        
        // Delete the pending friend request sent by the current user
        $stmt = $db->pdo->prepare("DELETE FROM friendships WHERE user1_id = ? AND user2_id = ? AND status = 'pending'");
        $stmt->execute([$fromUserId, $toUserId]);
    }

    // Redirect back to the profile page
    if ($toUserId > 0) {
        header('Location: profile.php?id=' . $toUserId);
    } else {
        header('Location: index.php');
    }
    exit;
}

header('Location: index.php');
exit;