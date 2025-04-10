<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Set a success message
session_start();
$_SESSION['message'] = "You have been successfully logged out.";

// Redirect to login page
header('Location: login.php');
exit();
?> 