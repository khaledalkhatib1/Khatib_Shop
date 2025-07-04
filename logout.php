<?php
session_start();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete token from database
    require_once 'includes/db.php';
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    // Delete cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
