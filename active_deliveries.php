<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a delivery person
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'delivery') {
    header('Location: login.php');
    exit();
}

$delivery_id = $_SESSION['id'];

// Get delivery person's details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'delivery'");
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery_person = $stmt->get_result()->fetch_assoc();

// Get active deliveries
$query = "
    SELECT o.*, 
           u.full_name as customer_name,
           u.mobile as customer_mobile,
           u.address as delivery_address,
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name)) as items,
           p.image_url as product_image
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status IN ('processing', 'out_for_delivery')
    GROUP BY o.id
    ORDER BY o.created_at DESC";

$result = $conn->query($query);
$active_deliveries = $result->fetch_all(MYSQLI_ASSOC);

function getProfileImage($photo_url) {
    $default_avatar = 'assets/images/default-avatar.png';
    
    if (empty($photo_url) || $photo_url === 'default-avatar.png') {
        return $default_avatar;
    }
    
    $full_path = __DIR__ . '/' . $photo_url;
    if (file_exists($full_path)) {
        return $photo_url;
    }
    
    return $default_avatar;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Deliveries - AgroFresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background-color: #f4f4f4;
            --text-color: #333;
            --sidebar-width: 250px;
            --header-height: 60px;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .delivery-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .delivery-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.2);
        }

        .card-body {
            padding: 20px;
        }

        .customer-info {
            margin-bottom: 15px;
        }

        .customer-info p {
            margin: 5px 0;
        }

        .order-items {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .map-view {
            height: 200px;
            background: #f0f0f0;
            border-radius: 5px;
            margin: 15px 0;
        }

        .delivery-time {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .delivery-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 20px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: #f8f9fa;
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        .badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: auto;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            background: var(--background-color);
        }

        /* Mobile Responsive */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }
        }

        /* Status Indicators */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-online {
            background: #28a745;
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars(getProfileImage($delivery_person['photo_url'])); ?>" alt="Profile">
            <div>
                <h3><?php echo htmlspecialchars($delivery_person['full_name']); ?></h3>
                <small><span class="status-indicator status-online"></span> Online</small>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="delivery_dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="active_deliveries.php" class="menu-item active">
                <i class="fas fa-truck"></i>
                Active Deliveries
                <span class="badge"><?php echo count($active_deliveries); ?></span>
            </a>
            <a href="delivery_history.php" class="menu-item">
                <i class="fas fa-history"></i>
                Delivery History
            </a>
            <a href="earnings.php" class="menu-item">
                <i class="fas fa-rupee-sign"></i>
                Earnings
            </a>
            <a href="profile_settings.php" class="menu-item">
                <i class="fas fa-user"></i>
                Profile Settings
            </a>
       
            <a href="javascript:void(0)" onclick="logout()" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <!-- Add this right after the sidebar -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-truck"></i> Active Deliveries</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='delivery_dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                </div>
            </div>

            <?php if (empty($active_deliveries)): ?>
                <div class="empty-state">
                    <i class="fas fa-truck-loading"></i>
                    <h2>No Active Deliveries</h2>
                    <p>There are no pending deliveries at the moment.</p>
                </div>
            <?php else: ?>
                <div class="delivery-grid">
                    <?php foreach ($active_deliveries as $delivery): ?>
                        <div class="delivery-card">
                            <div class="card-header">
                                <h3>Order #<?php echo $delivery['id']; ?></h3>
                                <span class="order-status">
                                    <?php echo ucfirst($delivery['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="customer-info">
                                    <p><i class="fas fa-user"></i> <strong>Customer:</strong> 
                                        <?php echo htmlspecialchars($delivery['customer_name']); ?>
                                    </p>
                                    <p><i class="fas fa-phone"></i> <strong>Phone:</strong> 
                                        <?php echo htmlspecialchars($delivery['customer_mobile']); ?>
                                    </p>
                                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> 
                                        <?php echo htmlspecialchars($delivery['delivery_address']); ?>
                                    </p>
                                </div>
                                
                                <div class="order-items">
                                    <strong>Items:</strong>
                                    <p><?php echo htmlspecialchars($delivery['items']); ?></p>
                                </div>

                                <div class="delivery-time">
                                    <i class="far fa-clock"></i> Order Time: 
                                    <?php echo date('d M Y, h:i A', strtotime($delivery['created_at'])); ?>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="updateDeliveryStatus(<?php echo $delivery['id']; ?>, 'delivered')">
                                        <i class="fas fa-check"></i> Mark as Delivered
                                    </button>
                                    <button class="btn btn-secondary" onclick="viewDeliveryDetails(<?php echo $delivery['id']; ?>)">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </button>
                                    <button class="btn btn-secondary" onclick="openMap('<?php echo htmlspecialchars($delivery['delivery_address']); ?>')">
                                        <i class="fas fa-map"></i> Navigate
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateDeliveryStatus(orderId, status) {
            if (confirm('Are you sure you want to mark this order as delivered?')) {
                const button = event.target;
                const originalContent = button.innerHTML;
                button.innerHTML = '<span class="loading-spinner"></span> Updating...';
                button.disabled = true;

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
                        alert('Order status updated successfully');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to update order status');
                        button.innerHTML = originalContent;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update order status');
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            }
        }

        function viewDeliveryDetails(orderId) {
            window.location.href = `order_details.php?id=${orderId}`;
        }

        function openMap(address) {
            // Encode the address for use in the URL
            const encodedAddress = encodeURIComponent(address);
            // Open Google Maps in a new tab
            window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        });
    </script>
</body>
</html> 