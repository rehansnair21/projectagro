<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid order ID";
    header('Location: admin_dashboard.php');
    exit();
}

$order_id = $_GET['id'];

// Fetch order details with customer information
$order_query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email,
                u.mobile as customer_mobile, u.address as customer_address,
                u.city as customer_city, u.state as customer_state
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";

$stmt = $conn->prepare($order_query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("i", $order_id);
$result = $stmt->execute();
if ($result === false) {
    die("Error executing query: " . $stmt->error);
}

$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found";
    header('Location: admin_dashboard.php');
    exit();
}

// Fetch order items with product and seller details
$items_query = "SELECT oi.*, p.name as product_name, p.image_url,
                p.category, s.full_name as seller_name
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN sellerdetails s ON p.seller_id = s.id
                WHERE oi.order_id = ?";

$stmt = $conn->prepare($items_query);
if ($stmt === false) {
    die("Error preparing items query: " . $conn->error);
}

$stmt->bind_param("i", $order_id);
$result = $stmt->execute();
if ($result === false) {
    die("Error executing items query: " . $stmt->error);
}

$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .order-details {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-processing { background: #dbeafe; color: #2563eb; }
        .status-delivered { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 0.75rem;
        }

        .info-section h3 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .info-section p {
            margin-bottom: 0.5rem;
            color: #64748b;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        .items-table th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            color: var(--dark);
        }

        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .order-summary {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            text-align: right;
        }

        .summary-row {
            display: flex;
            justify-content: flex-end;
            gap: 2rem;
            margin-bottom: 0.5rem;
        }

        .summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-details">
            <div class="header">
                <a href="admin_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <h1>Order #<?php echo $order['id']; ?></h1>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h3>Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_mobile']); ?></p>
                </div>

                <div class="info-section">
                    <h3>Shipping Address</h3>
                    <p><?php echo htmlspecialchars($order['customer_address']); ?></p>
                    <p><?php echo htmlspecialchars($order['customer_city']); ?>, <?php echo htmlspecialchars($order['customer_state']); ?></p>
                </div>

                <div class="info-section">
                    <h3>Order Information</h3>
                    <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                    <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></p>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     class="product-image">
                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo htmlspecialchars($item['seller_name']); ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 