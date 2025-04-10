<?php
session_start();
require_once 'db_connection.php';

// Get user identifier based on login status
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : session_id();
$is_guest = !isset($_SESSION['id']);

// Validate request
if (!isset($_POST['action']) || !isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];
$product_id = intval($_POST['product_id']);

// Verify product exists and get stock information
$product_query = "SELECT stock, price FROM products WHERE id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();
$product = $product_result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

switch ($action) {
    case 'add':
        // Check if item already exists in cart
        $check_query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_item = $result->fetch_assoc();
        $stmt->close();

        if ($existing_item) {
            // Update quantity if not exceeding stock
            $new_quantity = $existing_item['quantity'] + 1;
            if ($new_quantity > $product['stock']) {
                echo json_encode(['success' => false, 'error' => 'Not enough stock available']);
                exit;
            }

            $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isii", $new_quantity, $user_id, $product_id, $is_guest);
        } else {
            // Insert new item
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity, is_guest) VALUES (?, ?, 1, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        }
        break;

    case 'remove':
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        break;

    case 'decrease':
        // Update quantity if greater than 1
        $update_query = "UPDATE cart SET quantity = quantity - 1 
                        WHERE user_id = ? AND product_id = ? AND is_guest = ? AND quantity > 1";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        break;

    case 'increase':
        // Check stock before increasing
        $check_query = "SELECT c.quantity, p.stock 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ? AND c.product_id = ? AND c.is_guest = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();

        if ($item && $item['quantity'] < $item['stock']) {
            $update_query = "UPDATE cart SET quantity = quantity + 1 
                            WHERE user_id = ? AND product_id = ? AND is_guest = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        } else {
            echo json_encode(['success' => false, 'error' => 'Maximum stock reached']);
            exit;
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}

// Execute the prepared statement
if ($stmt->execute()) {
    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND is_guest = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("si", $user_id, $is_guest);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['total'] ?? 0;

    // Get updated product stock
    $stock_query = "SELECT stock FROM products WHERE id = ?";
    $stmt = $conn->prepare($stock_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $new_stock = $row['stock'];

    echo json_encode([
        'success' => true,
        'cartCount' => $cart_count,
        'newStock' => $new_stock
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update cart'
    ]);
}

$stmt->close();
$conn->close();
?>
