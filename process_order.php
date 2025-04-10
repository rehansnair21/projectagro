<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Razorpay credentials
define('RAZORPAY_KEY_ID', 'rzp_test_tviD0nX9tPfUxN');
define('RAZORPAY_KEY_SECRET', 'tRl7osLfQLpDmAsUyrt3YGfz');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

try {
    $conn->begin_transaction();

    $user_id = $_SESSION['id'];
    $payment_method = $_POST['payment_method'];
    $shipping_address = sprintf("%s, %s, %s - %s",
        $_POST['address'],
        $_POST['city'],
        $_POST['state'],
        $_POST['pincode']
    );

    // Get cart items and total
    $stmt = $conn->prepare("SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Verify Razorpay payment if online payment
    if ($payment_method === 'online') {
        // Verify required parameters
        $required_params = ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature'];
        foreach ($required_params as $param) {
            if (!isset($_POST[$param]) || empty($_POST[$param])) {
                throw new Exception("Missing required parameter: $param");
            }
        }

        $razorpay_payment_id = $_POST['razorpay_payment_id'];
        $razorpay_order_id = $_POST['razorpay_order_id'];
        $razorpay_signature = $_POST['razorpay_signature'];

        // Debug logging
        error_log("=== Razorpay Verification Start ===");
        error_log("Payment ID: $razorpay_payment_id");
        error_log("Order ID: $razorpay_order_id");
        error_log("Signature: $razorpay_signature");

        // First verify the payment with Razorpay API
        $auth = base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/payments/$razorpay_payment_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ));
        
        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Curl Error: " . curl_error($ch));
            throw new Exception("Failed to verify payment with Razorpay");
        }
        
        curl_close($ch);

        error_log("API Response: " . $result);
        
        if ($http_status !== 200) {
            error_log("Razorpay API Error. Status: $http_status");
            throw new Exception("Payment verification failed");
        }

        $payment_details = json_decode($result, true);
        
        if (!$payment_details) {
            error_log("Failed to decode payment details");
            throw new Exception("Invalid payment response");
        }

        error_log("Payment Status: " . ($payment_details['status'] ?? 'unknown'));

        // Verify payment is captured
        if (!isset($payment_details['status']) || $payment_details['status'] !== 'captured') {
            error_log("Payment not captured. Current status: " . ($payment_details['status'] ?? 'unknown'));
            throw new Exception("Payment not captured");
        }

        // Build signature data
        $secret = RAZORPAY_KEY_SECRET;
        
        // Get the order details to verify amount
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.razorpay.com/v1/orders/$razorpay_order_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ));
        
        $order_result = curl_exec($ch);
        curl_close($ch);
        
        $order_details = json_decode($order_result, true);
        
        error_log("Order Details: " . json_encode($order_details));

        // Verify amount matches
        if ($order_details && isset($order_details['amount'])) {
            $razorpay_amount = $order_details['amount'] / 100; // Convert from paise to rupees
            if ($razorpay_amount != $total_amount) {
                error_log("Amount mismatch. Expected: $total_amount, Got: $razorpay_amount");
                throw new Exception("Payment amount verification failed");
            }
        }

        // Verify signature
        $data = $razorpay_order_id . "|" . $razorpay_payment_id;
        $generated_signature = hash_hmac('sha256', $data, $secret);
        
        error_log("Data to hash: " . $data);
        error_log("Generated signature: " . $generated_signature);
        error_log("Received signature: " . $razorpay_signature);

        if (!hash_equals($generated_signature, $razorpay_signature)) {
            error_log("Signature verification failed");
            throw new Exception("Invalid payment signature");
        }

        error_log("=== Payment verification successful ===");
    }

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, 
            total_amount, 
            shipping_address, 
            payment_method, 
            status,
            payment_id,
            razorpay_order_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $status = $payment_method === 'online' ? 'processing' : 'pending';
    $payment_id = $payment_method === 'online' ? $razorpay_payment_id : null;
    
    $stmt->bind_param(
        "idssss",
        $user_id,
        $total_amount,
        $shipping_address,
        $payment_method,
        $status,
        $payment_id,
        $razorpay_order_id
    );
    $stmt->execute();
    $order_id = $conn->insert_id;

    // Add order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (
            order_id, 
            product_id, 
            quantity, 
            price
        ) VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cart_items as $item) {
        $stmt->bind_param(
            "iiid",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        );
        $stmt->execute();
    }

    // Record payment for online payments
    if ($payment_method === 'online') {
        $stmt = $conn->prepare("
            INSERT INTO payments (
                order_id,
                amount,
                payment_method,
                payment_status,
                transaction_id,
                razorpay_payment_id,
                razorpay_order_id,
                razorpay_signature
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $payment_status = 'completed';
        
        $stmt->bind_param(
            "idssssss",
            $order_id,
            $total_amount,
            $payment_method,
            $payment_status,
            $razorpay_payment_id,
            $razorpay_payment_id,
            $razorpay_order_id,
            $razorpay_signature
        );
        $stmt->execute();
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'payment_id' => $payment_method === 'online' ? $razorpay_payment_id : null
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('Payment Processing Error: ' . $e->getMessage());
    error_log('POST Data: ' . print_r($_POST, true));
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 