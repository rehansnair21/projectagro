<?php
session_start();
require_once 'db_connection.php';
require_once 'razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Razorpay credentials
define('RAZORPAY_KEY_ID', 'rzp_test_tviD0nX9tPfUxN');
define('RAZORPAY_KEY_SECRET', 'tRl7osLfQLpDmAsUyrt3YGfz');

// Initialize Razorpay API
$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

// Create Razorpay Order function
function createRazorpayOrder($amount, $receipt) {
    global $api;
    
    try {
        // Convert amount to paise (multiply by 100)
        $orderAmount = $amount * 100;
        
        $order = $api->order->create([
            'amount' => $orderAmount,
            'currency' => 'INR',
            'receipt' => $receipt,
            'payment_capture' => 1
        ]);
        
        return $order;
    } catch (\Exception $e) {
        error_log('Razorpay Order Creation Error: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// Debug info - log all POST data for troubleshooting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data received: ' . print_r($_POST, true));
}

// Process Razorpay payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    try {
        // Log all received parameters
        error_log('Payment verification attempt with parameters: ' . json_encode($_POST));
        
        // Verify required parameters
        if (empty($_POST['razorpay_payment_id'])) {
            throw new Exception("Missing required parameter: razorpay_payment_id");
        }
        
        if (empty($_POST['razorpay_order_id'])) {
            throw new Exception("Missing required parameter: razorpay_order_id");
        }
        
        if (empty($_POST['razorpay_signature'])) {
            throw new Exception("Missing required parameter: razorpay_signature");
        }

        // Capture the payment details
        $attributes = [
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];

        // Verify the payment signature
        $api->utility->verifyPaymentSignature($attributes);

        // If we get here, signature verification was successful
        error_log('Payment signature verified successfully');
        
        // Get order details from the form submission
        $order_id = $_POST['order_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        
        if (!$order_id) {
            // If order_id not in POST, try to get from session
            $order_id = $_SESSION['order_id'] ?? null;
            
            if (!$order_id) {
                throw new Exception("Order ID is missing");
            }
        }

        // Insert payment details into the payments table
        $payment_method = 'razorpay';
        $payment_status = 'completed';
        $transaction_id = $_POST['razorpay_payment_id'];

        $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $order_id, $amount, $payment_method, $payment_status, $transaction_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert payment details: " . $stmt->error);
        }

        // Update order status to 'paid' if needed
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Return success response
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit;
    } catch (Exception $e) {
        error_log('Razorpay Verification Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get cart items and total
$user_id = $_SESSION['id'];
$cart_query = "SELECT c.*, p.name, p.price, p.image_url, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ? AND c.is_guest = 0";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Generate receipt ID
$receipt = 'receipt_' . time() . '_' . $user_id;

// Create Razorpay order
$razorpay_order = createRazorpayOrder($total_amount, $receipt);

// Store order ID in session for verification
if (isset($razorpay_order['id'])) {
    $_SESSION['razorpay_order_id'] = $razorpay_order['id'];
} else {
    error_log('Razorpay Order Error: ' . json_encode($razorpay_order));
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - AgroFresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* CSS styles remain unchanged */
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --text-color: #333;
            --background-color: #f9f9f9;
            --border-color: #ddd;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid var(--primary-color);
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .checkout-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .order-summary {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        h2, h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .payment-methods {
            margin: 25px 0;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .payment-method {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-method input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: relative;
                top: 0;
            }

            .container {
                padding: 10px;
            }
        }

        /* Loading animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading::after {
            content: "";
            width: 50px;
            height: 50px;
            border: 5px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: loading 0.8s ease infinite;
        }

        @keyframes loading {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading"></div>
    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-shopping-bag"></i> Place Your Order</h2>
        </div>
        <div class="checkout-grid">
            <div class="checkout-form">
                <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                <form id="checkout-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" required>
                    </div>

                    <div class="payment-methods">
                        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                        <div class="payment-method">
                            <input type="radio" id="cod" name="payment_method" value="cod">
                            <label for="cod"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="online" name="payment_method" value="online" checked>
                            <label for="online"><i class="fas fa-credit-card"></i> Online Payment (Razorpay)</label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="order-summary">
                <h3><i class="fas fa-clipboard-list"></i> Order Summary</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                        <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-item" style="font-weight: bold;">
                    <span>Total Amount</span>
                    <span class="total-amount">₹<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <button type="button" class="btn" onclick="handlePayment()">Place Order</button>
            </div>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    // Global variable to store order details
    let globalOrderData = null;
    
    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loading').style.display = 'none';
    }

    // First create the order in your system
    async function createOrder(formData) {
        showLoading();
        try {
            const response = await fetch('process_order.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to create order');
            }
            
            console.log("Order created successfully:", data);
            return data;
        } catch (error) {
            hideLoading();
            console.error('Error creating order:', error);
            alert(error.message || 'Failed to create order');
            throw error;
        }
    }

    function handlePayment() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const form = document.getElementById('checkout-form');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        formData.append('payment_method', paymentMethod);
        formData.append('create_order', '1');
        
        // First create the order
        createOrder(formData)
            .then(orderData => {
                globalOrderData = orderData; // Store order data globally
                
                if (paymentMethod === 'cod') {
                    // For COD, the order is already processed
                    hideLoading();
                    window.location.href = 'order_success.php?order_id=' + orderData.order_id;
                } else {
                    // For Razorpay, initiate the payment
                    initiateRazorpayPayment(orderData);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error in order creation:', error);
            });
    }

    function initiateRazorpayPayment(orderData) {
        hideLoading(); // Hide loading before showing Razorpay modal
        
        const options = {
            key: "<?php echo RAZORPAY_KEY_ID; ?>",
            amount: orderData.amount,
            currency: "INR",
            name: "AgroFresh",
            description: "Order #" + orderData.order_id,
            order_id: orderData.razorpay_order_id,
            handler: function (response) {
                // This function gets called after successful payment
                console.log("Payment succeeded, response:", response);
                handlePaymentSuccess(response, orderData);
            },
            prefill: {
                name: "<?php echo addslashes($user['full_name']); ?>",
                email: "<?php echo addslashes($user['email']); ?>",
                contact: "<?php echo addslashes($user['mobile']); ?>"
            },
            theme: {
                color: "#4CAF50"
            },
            modal: {
                ondismiss: function() {
                    hideLoading();
                    console.log("Payment modal dismissed");
                }
            }
        };

        console.log("Initializing Razorpay with options:", options);
        
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            console.error('Payment failed:', response.error);
            alert('Payment failed: ' + response.error.description);
        });
        
        console.log("Opening Razorpay payment modal");
        rzp.open();
    }

    function handlePaymentSuccess(paymentResponse, orderData) {
        console.log("Payment success handler called with:", paymentResponse, orderData);
        showLoading();
        
        // Create form data for verification
        const form = new FormData();
        form.append('razorpay_payment_id', paymentResponse.razorpay_payment_id);
        form.append('razorpay_order_id', paymentResponse.razorpay_order_id);
        form.append('razorpay_signature', paymentResponse.razorpay_signature);
        form.append('order_id', orderData.order_id);
        form.append('amount', <?php echo $total_amount; ?>);
        
        // Log what we're sending
        console.log("Sending verification data:", {
            razorpay_payment_id: paymentResponse.razorpay_payment_id,
            razorpay_order_id: paymentResponse.razorpay_order_id,
            razorpay_signature: paymentResponse.razorpay_signature,
            order_id: orderData.order_id,
            amount: <?php echo $total_amount; ?>
        });
        
        // Send verification request
        fetch('checkout.php', {
            method: 'POST',
            body: form
        })
        .then(response => {
            console.log("Verification response status:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("Verification response data:", data);
            hideLoading();
            
            if (data.success) {
                window.location.href = 'order_success.php?order_id=' + data.order_id;
            } else {
                throw new Error(data.error || 'Payment verification failed');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Verification error:', error);
            alert(error.message || 'Payment verification failed');
        });
    }
    </script>
</body>
</html>