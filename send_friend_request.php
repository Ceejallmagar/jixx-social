<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromUserId = $_SESSION['user']['id'];
    $toUserId = intval($_POST['to_user_id']);
    $success = false;
    $message = '';
    if ($fromUserId !== $toUserId) {
        $db = new Database();
        // Check if a friendship record already exists
        $checkStmt = $db->pdo->prepare("SELECT status FROM friendships WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
        $checkStmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
        $existing = $checkStmt->fetch();
        if ($existing) {
            if ($existing['status'] === 'accepted') {
                $message = 'You are already friends.';
            } elseif ($existing['status'] === 'pending') {
                $message = 'Friend request already sent.';
            } else {
                $message = 'Unable to send friend request.';
            }
        } else {
            $stmt = $db->pdo->prepare("INSERT INTO friendships (user1_id, user2_id, status) VALUES (?, ?, 'pending')");
            $success = (bool)$stmt->execute([$fromUserId, $toUserId]);
            if ($success) {
                $message = 'Friend request sent.';
            } else {
                $message = 'Failed to send friend request.';
            }
        }
    } else {
        $message = 'You cannot send a friend request to yourself.';
    }
    // If request expects JSON (AJAX), return JSON; otherwise redirect back to profile
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'to_user_id' => $toUserId]);
        exit;
    }
    // For non-AJAX, redirect with message (could use session flash message)
    header('Location: profile.php?id=' . $toUserId);
    exit;
}
