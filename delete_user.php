<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No user ID provided";
    header('Location: admin_dashboard.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Don't allow admin to delete themselves
if ($user_id === $_SESSION['id']) {
    $_SESSION['error'] = "You cannot delete your own admin account";
    header('Location: admin_dashboard.php');
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete related records first
    // Delete from cart
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    
    // Delete from orders (this will cascade to order_items and payments due to foreign key constraints)
    $conn->query("DELETE FROM orders WHERE user_id = $user_id");
    
    // Delete from sellerdetails if the user is a seller
    $conn->query("DELETE FROM sellerdetails WHERE id = $user_id");
    
    // Finally delete the user
    $result = $conn->query("DELETE FROM users WHERE id = $user_id");

    if ($result) {
        $conn->commit();
        $_SESSION['success'] = "User deleted successfully";
    } else {
        throw new Exception("Failed to delete user");
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

header('Location: admin_dashboard.php');
exit();
?>
