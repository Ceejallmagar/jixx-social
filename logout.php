<?php
session_start();

// Include the database class to handle token deletion
require_once 'database.php';

// If a "remember me" cookie exists, invalidate it
if (isset($_COOKIE['remember_me'])) {
    $db = new Database();
    $token = $_COOKIE['remember_me'];
    
    // Extract selector from the token
    list($selector, ) = explode(':', $token, 2);
    $db->deleteRememberMeToken($selector);
    
    // Expire the cookie
    setcookie('remember_me', '', time() - 3600, '/');
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
