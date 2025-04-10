<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id']) || !isset($_GET['order_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['id'];
$order_id = $_GET['order_id'];

// Fetch order details
$order_query = "SELECT o.*, u.full_name, u.email, u.mobile 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: cart.php');
    exit();
}

// Check if payment already exists
$check_payment = "SELECT id FROM payments WHERE order_id = ?";
$stmt = $conn->prepare($check_payment);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$existing_payment = $stmt->get_result()->fetch_assoc();

// Only insert payment if it doesn't exist
if (!$existing_payment) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert into payments table
        $payment_query = "INSERT INTO payments (order_id, amount, payment_method, payment_status, payment_date) 
                         VALUES (?, ?, 'cod', 'pending', NOW())";
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("id", $order_id, $order['total_amount']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting payment: " . $stmt->error);
        }

        // Update order status
        $update_order = "UPDATE orders SET payment_method = 'cod', status = 'pending' WHERE id = ?";
        $stmt = $conn->prepare($update_order);
        $stmt->bind_param("i", $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating order: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Payment Error: " . $e->getMessage());
    }
}

// Fetch order items
$items_query = "SELECT oi.*, p.name, p.image_url 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Summary - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --text-color: #333;
            --background-color: #f9f9f9;
            --border-color: #ddd;
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
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            color: var(--primary-color);
            font-size: 48px;
            margin-bottom: 15px;
        }

        .order-details {
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .items-list {
            margin: 20px 0;
        }

        .item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .item-details {
            flex-grow: 1;
        }

        .shipping-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            text-align: right;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin: 20px auto;
            }

            .summary-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="summary-card">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <div class="success-header">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Order Placed Successfully!</h1>
                    <p>Your order has been confirmed with Cash on Delivery payment method.</p>
                </div>

                <div class="order-details">
                    <h2>Order Details</h2>
                    <div class="detail-row">
                        <span>Order ID:</span>
                        <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Order Date:</span>
                        <span><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Payment Method:</span>
                        <span>Cash on Delivery</span>
                    </div>
                    <div class="detail-row">
                        <span>Payment Status:</span>
                        <span>Pending</span>
                    </div>
                </div>

                <div class="items-list">
                    <h2>Order Items</h2>
                    <?php foreach ($order_items as $item): ?>
                        <div class="item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                                <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="shipping-info">
                    <h2>Shipping Information</h2>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['mobile']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                </div>

                <div class="total-amount">
                    Total Amount: ₹<?php echo number_format($order['total_amount'], 2); ?>
                </div>

                <div class="action-buttons">
                    <a href="template.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="#" onclick="printSummary()" class="btn btn-secondary">Print Summary</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add this script to show a loading indicator when printing
        function printSummary() {
            document.querySelector('.action-buttons').style.display = 'none';
            window.print();
            setTimeout(() => {
                document.querySelector('.action-buttons').style.display = 'flex';
            }, 500);
        }
    </script>
</body>
</html> 