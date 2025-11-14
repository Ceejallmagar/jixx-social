<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $feeling = isset($_POST['feeling']) ? trim($_POST['feeling']) : '';
    $mediaUrl = null;
    
    // Handle media upload
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
                $mediaUrl = $filePath;
            }
        }
    }
    
    // Add feeling to content if provided
    if ($feeling && $content) {
        $content = $content . ' ' . $feeling;
    } elseif ($feeling && !$content) {
        $content = $feeling;
    }
    
    $privacy = isset($_POST['privacy']) ? $_POST['privacy'] : 'public';
    if (!in_array($privacy, ['public','friends','private'])) $privacy = 'public';
    if ($content !== '' || $mediaUrl !== null) {
        $db = new Database();
        $db->createPost($_SESSION['user']['id'], $content, $mediaUrl, $privacy);
    }
}

header('Location: index.php');
exit;
?>
