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
    $_SESSION['error'] = "No seller ID provided";
    header('Location: admin_dashboard.php');
    exit();
}

$seller_id = (int)$_GET['id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete products first (this will cascade to cart and order_items)
    $conn->query("DELETE FROM products WHERE seller_id = $seller_id");
    
    // Delete from sellerdetails
    $result = $conn->query("DELETE FROM sellerdetails WHERE id = $seller_id");

    if ($result) {
        $conn->commit();
        $_SESSION['success'] = "Seller deleted successfully";
    } else {
        throw new Exception("Failed to delete seller");
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error deleting seller: " . $e->getMessage();
}

header('Location: admin_dashboard.php');
exit();
?>
