<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get user ID or guest ID
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : session_id();
$is_guest = !isset($_SESSION['id']);

// Get product ID and action
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$product_id || !in_array($action, ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get current quantity and stock
    $stmt = $conn->prepare("
        SELECT c.quantity, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND c.is_guest = ? AND c.product_id = ?
    ");
    $stmt->bind_param("sii", $user_id, $is_guest, $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        throw new Exception('Item not found in cart');
    }

    $current_quantity = $result['quantity'];
    $stock = $result['stock'];

    // Calculate new quantity
    $new_quantity = $action === 'increase' ? $current_quantity + 1 : $current_quantity - 1;

    // Validate new quantity
    if ($new_quantity < 1) {
        throw new Exception('Quantity cannot be less than 1');
    }

    if ($new_quantity > $stock) {
        throw new Exception('Not enough stock available');
    }

    // Update quantity
    $stmt = $conn->prepare("
        UPDATE cart 
        SET quantity = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE user_id = ? AND is_guest = ? AND product_id = ?
    ");
    $stmt->bind_param("isii", $new_quantity, $user_id, $is_guest, $product_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update cart');
    }

    // Commit transaction
    $conn->commit();

    // Get updated cart total
    $stmt = $conn->prepare("
        SELECT SUM(c.quantity) as total_items,
               SUM(c.quantity * p.price) as total_amount
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.is_guest = ?
    ");
    $stmt->bind_param("si", $user_id, $is_guest);
    $stmt->execute();
    $cart_totals = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'new_quantity' => $new_quantity,
        'total_items' => $cart_totals['total_items'],
        'total_amount' => number_format($cart_totals['total_amount'], 2)
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
