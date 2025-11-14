<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// 1. Check for user authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

// 2. Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$originalPostId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$newCaption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
$sharerId = $_SESSION['user']['id'];

if ($originalPostId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid post ID.']);
    exit;
}

$db = new Database();

// 3. Fetch the original post
$originalPost = $db->getPostById($originalPostId);

if (!$originalPost) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Original post not found.']);
    exit;
}

// 4. Prepare the new shared post content
$originalAuthor = $originalPost['first_name'] . ' ' . $originalPost['last_name'];
$finalContent = $newCaption;

// Append a clear reference to the original post
$finalContent .= "\n\n" . '--- Shared from ' . $originalAuthor . " ---\n" . $originalPost['content'];

// 5. Create the new post in the database
$mediaUrl = $originalPost['media_url']; // Copy the media from the original post
$privacy = 'public'; // Shared posts are typically public

$success = $db->createPost($sharerId, $finalContent, $mediaUrl, $privacy);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Post shared successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to share the post.']);
}
?>