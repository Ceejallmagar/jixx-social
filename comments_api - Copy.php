<?php
/**
 * comments_api.php
 *
 * A simple API endpoint to handle adding and retrieving comments.
 * This script expects POST requests with an 'action' parameter.
 *
 * Actions:
 * 1. 'add_comment': Requires 'user_id', 'page_id', and 'comment_text'.
 * 2. 'get_comments': Requires 'page_id'.
 */

// We need the session to identify the logged-in user for security checks.
session_start();

// Set the response content type to JSON to ensure clients parse it correctly.
header('Content-Type: application/json');

// --- 1. DATABASE CONNECTION ---

// Assume a configuration file exists with your database credentials.
// Example db_config.php:
/*
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jixx_app');
define('DB_USER', 'root');
define('DB_PASS', '');
*/
require_once 'config.php'; // Using existing config.php from your project

$pdo = null;
try {
    // Establish a persistent and secure connection to the database.
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // If the database connection fails, return a server error and stop execution.
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// --- 2. API LOGIC ---

// Ensure the request is a POST request to prevent access via URL.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is accepted.']);
    exit;
}

// Determine the requested action from the POST data.
$action = $_POST['action'] ?? '';

// Route the request to the appropriate function based on the action.
switch ($action) {
    default:
        // If the action is missing or invalid, return a bad request error.
        http_response_code(400); // 400 Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid or missing action specified.']);
        break;
}