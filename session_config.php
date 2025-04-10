<?php
// Set session timeout duration (30 minutes in seconds)
define('SESSION_TIMEOUT', 1800);

function isSessionValid() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function requireLogin() {
    initSession();
    if (!isset($_SESSION['id']) || !isSessionValid()) {
        header("Location: guest.php");
        exit();
    }
}
?>
