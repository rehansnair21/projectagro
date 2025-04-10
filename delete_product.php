<?php
session_start();
require_once 'db_connection.php';

// Return JSON response
header('Content-Type: application/json');

// Debug logging
error_log("POST data received: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in and is admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if product ID is provided
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit();
}

$product_id = $_POST['product_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // First check if the product exists
    $check_query = "SELECT id FROM products WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $conn->error);
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Product not found");
    }

    // Delete from cart_items first
    $delete_cart = "DELETE FROM cart_items WHERE product_id = ?";
    $stmt = $conn->prepare($delete_cart);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
    }

    // Delete from order_items
    $delete_order_items = "DELETE FROM order_items WHERE product_id = ?";
    $stmt = $conn->prepare($delete_order_items);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
    }

    // Finally delete the product
    $delete_product = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_product);
    if (!$stmt) {
        throw new Exception("Error preparing delete product query");
    }
    $stmt->bind_param("i", $product_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting product: " . $stmt->error);
    }

    // If we get here, everything worked
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);

} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
