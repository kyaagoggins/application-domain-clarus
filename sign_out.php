<?php
/**
 * Sign Out Script
 * This script logs out the user by destroying the session and redirecting to login page
 */

// Start the session
session_start();

// Store username for goodbye message (optional)
$username = $_SESSION['username'] ?? 'User';

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: Clear any additional cookies you might have set
// setcookie('remember_me', '', time() - 3600, '/');
// setcookie('user_preferences', '', time() - 3600, '/');

// Redirect to login page with optional success message
header('Location: home.html?message=logged_out&user=' . urlencode($username));
exit;
?>