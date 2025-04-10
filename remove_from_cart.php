<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

// Get user ID or guest ID
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : session_id();
$is_guest = !isset($_SESSION['id']);
$product_id = intval($_POST['product_id']);

try {
    // Prepare and execute delete statement
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
    $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Item not found in cart']);
        }
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
