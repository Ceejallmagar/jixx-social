<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Handle both old and new Google sign-in formats
    $id_token = '';
    if (isset($_POST['credential'])) {
        // New Google Identity Services format
        $id_token = $_POST['credential'];
    } else {
        // Old manual format
        $input = json_decode(file_get_contents('php://input'), true);
        $id_token = $input['id_token'] ?? '';
    }

    if (!$id_token) {
        throw new Exception('ID token missing');
    }

    // Verify the ID token with Google
    $client_id = '18539381861-kpg6am590kaakqj22fd57g4j8tdd5f28.apps.googleusercontent.com';
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    curl_close($ch);

    if (isset($error_msg)) {
        throw new Exception('Curl error: ' . $error_msg);
    }

    if ($http_code !== 200) {
        throw new Exception('Invalid ID token');
    }

    $payload = json_decode($response, true);
    if (!$payload || $payload['aud'] !== $client_id) {
        throw new Exception('Token verification failed');
    }

    // Extract user info
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $first_name = $payload['given_name'] ?? '';
    $last_name = $payload['family_name'] ?? '';
    $name = $payload['name'] ?? $first_name . ' ' . $last_name;

    $db = new Database();

    // Check if user exists by google_id or email
    $user = $db->getUserByGoogleId($google_id);
    if (!$user) {
        $user = $db->getUserByEmail($email);
        if ($user) {
            // Update user with google_id
            if (!$db->updateUserGoogleId($user['id'], $google_id)) {
                throw new Exception('Failed to update user Google ID');
            }
        } else {
            // Create new user
            $user_id = $db->createUserFromGoogle($google_id, $email, $first_name, $last_name);
            if (!$user_id) {
                throw new Exception('Failed to create user');
            }
            $user = $db->getUserById($user_id);
        }
    }

    if (!$user) {
        throw new Exception('User not found');
    }

    // Set session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name']
    ];

    // Check if this is from the Google button (POST with credential) or manual JS call
    if (isset($_POST['credential'])) {
        // From Google button - redirect to index
        header('Location: index.php');
        exit;
    } else {
        // From manual JS call - return JSON
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    // Check if this is from the Google button or manual JS call
    if (isset($_POST['credential'])) {
        // From Google button - redirect back to login with error
        header('Location: login.php?error=' . urlencode($e->getMessage()));
        exit;
    } else {
        // From manual JS call - return JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
