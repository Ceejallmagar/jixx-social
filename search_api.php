<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$db = new Database();
$pdo = $db->pdo;
$results = [];
$searchQuery = '%' . $query . '%';
$currentUserId = $_SESSION['user']['id'];

try {
    // Corrected SQL query to search for users
    // This handles both single word queries (e.g., 'John') and two-word queries (e.g., 'John Doe')
    $userStmt = $pdo->prepare("
        SELECT id, first_name, last_name, avatar, bio
        FROM users
        WHERE (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?) AND id != ?
        LIMIT 5
    ");
    $userStmt->execute([$searchQuery, $searchQuery, $searchQuery, $currentUserId]);
    $users = $userStmt->fetchAll();

    foreach ($users as $user) {
        $results[] = [
            'type' => 'user',
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'avatar' => $user['avatar'],
            'bio' => $user['bio']
        ];
    }

    // Search for public posts by content
    $postStmt = $pdo->prepare("
        SELECT p.id, p.content, p.created_at, u.first_name, u.last_name, u.avatar
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.content LIKE ? AND p.privacy = 'public'
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $postStmt->execute([$searchQuery]);
    $posts = $postStmt->fetchAll();

    foreach ($posts as $post) {
        $results[] = [
            'type' => 'post',
            'id' => $post['id'],
            'content' => $post['content'],
            'author' => $post['first_name'] . ' ' . $post['last_name'],
            'avatar' => $post['avatar'],
            'date' => date('M j, Y', strtotime($post['created_at']))
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database search failed.']);
    // For debugging, you can add this line to see the specific error:
    // error_log($e->getMessage());
}
?>