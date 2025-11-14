<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "Database connection successful!<br>";

    // Check if tables exist
    $tables = ['users', 'posts', 'friendships', 'likes'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.<br>";
        } else {
            echo "Table '$table' does not exist.<br>";
        }
    }

    // Check users count
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Number of users: $count<br>";

    if ($count > 0) {
        echo "Users in database:<br>";
        $stmt = $pdo->query("SELECT id, email, first_name, last_name FROM users");
        $users = $stmt->fetchAll();
        foreach ($users as $user) {
            echo "ID: {$user['id']}, Email: {$user['email']}, Name: {$user['first_name']} {$user['last_name']}<br>";
        }
    } else {
        echo "No users found. You may need to register first.<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
