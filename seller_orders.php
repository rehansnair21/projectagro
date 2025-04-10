<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Get seller details
$seller_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Error preparing seller query: " . $conn->error);
}
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

// Modified query to match your actual database structure
$orders_query = "
    SELECT 
        o.id as order_id,
        o.created_at,
        o.status,
        o.total_amount,
        o.shipping_address,
        o.payment_method,
        oi.quantity,
        oi.price as item_price,
        p.name as product_name,
        p.image_url,
        u.full_name as buyer_name,
        u.email as buyer_email,
        u.mobile as buyer_phone
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE p.seller_id = ?
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($orders_query);
if (!$stmt) {
    die("Error preparing orders query: " . $conn->error);
}
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Root Variables */
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f0f5f1;
            padding-top: 80px;
            color: var(--dark);
            line-height: 1.5;
        }

        /* Header Styles */
        .header {
            background: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Layout Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 100px;
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }

        .seller-info {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .seller-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .seller-email {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dcfce7;
            color: #166534;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .sidebar-menu {
            list-style: none;
            margin: 0;
            padding: 0;
            flex-grow: 1;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .sidebar-menu a:hover {
            background: #f8fafc;
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background: var(--gradient);
            color: white;
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Orders Container Styles */
        .orders-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .order-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .order-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-content {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .order-details {
            display: grid;
            gap: 0.75rem;
        }

        .customer-details {
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-top: 1rem;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-out_for_delivery { background: #ddd6fe; color: #5b21b6; }
        .status-delivered { background: #dcfce7; color: #166534; }

        /* Form Controls */
        .status-select {
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            margin-right: 0.5rem;
            font-size: 0.875rem;
            color: var(--dark);
            background-color: white;
            cursor: pointer;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .update-status-btn {
            background: var(--gradient);
            color: white;
        }

        .update-status-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Help Section */
        .help-section {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .help-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #64748b;
            text-decoration: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .help-link:hover {
            background: #f8fafc;
            color: var(--primary);
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                height: auto;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .order-content {
                grid-template-columns: 1fr;
            }

            .product-image {
                width: 100%;
                height: 200px;
            }

            .order-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .status-select {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .update-status-btn {
                width: 100%;
            }
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">AgroFresh</div>
            <div>
                <a href="template.php" style="margin-right: 1rem; color: var(--dark); text-decoration: none;">Home</a>
                <a href="logout.php" style="color: #ef4444; text-decoration: none;">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="dashboard-grid">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="seller-info">
                    <div class="seller-name"><?php echo htmlspecialchars($seller['full_name']); ?></div>
                    <div class="seller-email"><?php echo htmlspecialchars($seller['email']); ?></div>
                    <div class="category-badge vegetables">Verified Seller</div>
                </div>

                <ul class="sidebar-menu">
                    <li>
                        <a href="seller_dashboard.php">
                            <i class="fas fa-chart-line"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="add_product.php">
                            <i class="fas fa-plus-circle"></i>
                            Add Product
                        </a>
                    </li>
                    <li>
                        <a href="manage_products.php">
                            <i class="fas fa-box"></i>
                            Manage Products
                        </a>
                    </li>
                    <li>
                        <a href="seller_orders.php" class="active">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <li>
                        <a href="seller_profile.php">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                    </li>
                    <li>
                        <a href="seller_analytics.php">
                            <i class="fas fa-chart-bar"></i>
                            Analytics
                        </a>
                    </li>
                    <li>
                        <a href="seller_settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                </ul>

                <div class="help-section">
                    <a href="seller_help.php" class="help-link">
                        <i class="fas fa-question-circle"></i>
                        Need Help?
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="orders-container">
                <h1 style="margin-bottom: 2rem;">Manage Orders</h1>
                
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span style="font-weight: 600;">Order #<?php echo $order['order_id']; ?></span>
                            <span style="color: #64748b; margin-left: 1rem;">
                                <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>

                    <div class="order-content">
                        <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                             class="product-image">

                        <div class="order-details">
                            <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                            <div style="color: #64748b;">
                                Quantity: <?php echo $order['quantity']; ?> units
                            </div>
                            <div style="font-weight: 600; color: var(--primary);">
                                ₹<?php echo number_format($order['item_price'] * $order['quantity'], 2); ?>
                            </div>
                            
                            <div class="customer-details">
                                <h4 style="margin-bottom: 0.5rem;">Customer Details</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['buyer_email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['buyer_phone']); ?></p>
                                <p><strong>Address:</strong><br>
                                    <?php echo htmlspecialchars($order['shipping_address']); ?>
                                </p>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <select class="status-select" id="status-<?php echo $order['order_id']; ?>">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>
                            <button onclick="updateOrderStatus(<?php echo $order['order_id']; ?>)" 
                                    class="action-btn update-status-btn">
                                Update Status
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </main>
        </div>
    </div>

    <script>
    function updateOrderStatus(orderId) {
        const status = document.getElementById(`status-${orderId}`).value;
        
        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update order status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update order status');
        });
    }
    </script>
</body>
</html> 