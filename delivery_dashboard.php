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

if (!$delivery_person) {
    header('Location: login.php');
    exit();
}

// Improved function to get profile image
function getProfileImage($photo_url) {
    $default_avatar = 'assets/images/default-avatar.png';
    
    // If no photo URL is set or it's the default
    if (empty($photo_url) || $photo_url === 'default-avatar.png') {
        return $default_avatar;
    }
    
    // If photo exists in uploads directory
    $full_path = __DIR__ . '/' . $photo_url;
    if (file_exists($full_path)) {
        return $photo_url;
    }
    
    // Fallback to default avatar
    return $default_avatar;
}

// Get delivery person's statistics
$stats_query = "
    SELECT 
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_count,
        COUNT(CASE WHEN status IN ('processing', 'out_for_delivery') THEN 1 END) as pending_count,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count,
        SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as total_earnings
    FROM orders 
    WHERE status != 'pending'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get today's completed deliveries
$today_deliveries_query = "
    SELECT o.*, 
                u.full_name as customer_name,
           u.mobile as customer_mobile,
           u.address as delivery_address,
           GROUP_CONCAT(oi.quantity, 'x ', p.name) as items
                FROM orders o 
                JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) = CURDATE() 
    AND o.status = 'delivered'
                GROUP BY o.id
                ORDER BY o.created_at DESC";
$today_result = $conn->query($today_deliveries_query);
$today_deliveries = $today_result->fetch_all(MYSQLI_ASSOC);

// Get assigned orders
$query = "
    SELECT o.*, 
                u.full_name as customer_name,
           u.mobile as customer_mobile,
           u.address as delivery_address
                FROM orders o 
                JOIN users u ON o.user_id = u.id
    WHERE o.status IN ('processing', 'out_for_delivery')
    ORDER BY o.created_at DESC
";

$result = $conn->query($query);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get profile image URL
$profile_image_url = getProfileImage($delivery_person['photo_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - AgroFresh</title>
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

        .dashboard-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .delivery-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .orders-list {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-details h4 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .customer-info {
            font-size: 0.9rem;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            text-align: center;
            background: #e8f5e9;
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
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

        @media (max-width: 768px) {
            .order-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .action-buttons {
                justify-content: flex-start;
            }
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .profile-image-container {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .profile-info {
            flex: 1;
        }
        
        .profile-info h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .profile-info p {
            color: var(--text-color);
            opacity: 0.8;
        }

        /* Add loading animation */
        @keyframes imageLoading {
            0% { opacity: 0.6; }
            50% { opacity: 0.8; }
            100% { opacity: 0.6; }
        }

        .image-loading {
            animation: imageLoading 1.5s infinite;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .tab-container {
            margin-bottom: 20px;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: #f0f0f0;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: var(--primary-color);
            color: white;
        }

        .delivery-timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
            border-left: 2px solid #e0e0e0;
            padding-left: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary-color);
        }

        .logout-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #ff4747;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-button:hover {
            background: #ff3333;
        }

        .earnings-chart {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
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

        /* User Profile Dropdown */
        .user-profile {
            position: relative;
            cursor: pointer;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }

        .profile-dropdown.active {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: var(--primary-color);
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

        .status-busy {
            background: #dc3545;
        }

        .content-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
        }

        .date-filter, .period-filter {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .history-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-date {
            color: #666;
            font-size: 0.9rem;
        }

        .earnings-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .earnings-chart {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .profile-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-picture-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        #profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .support-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .support-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .support-contacts {
            list-style: none;
            padding: 0;
            margin-top: 15px;
        }

        .support-contacts li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            margin-bottom: 20px;
            color: #ddd;
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
            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile">
            <div>
                <h3><?php echo htmlspecialchars($delivery_person['full_name']); ?></h3>
                <small><span class="status-indicator status-online"></span> Online</small>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="#dashboard" class="menu-item active">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="active_deliveries.php" class="menu-item">
                <i class="fas fa-truck"></i>
                Active Deliveries
                <span class="badge"><?php echo $stats['pending_count']; ?></span>
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
                    
    <!-- Main Content -->
    <div class="main-content">
        <button onclick="logout()" class="logout-button">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>

        <div class="container">
            <div class="dashboard-header">
                <div class="profile-section">
                    <div class="profile-image-container">
                        <img src="<?php echo htmlspecialchars($profile_image_url); ?>" 
                             alt="Profile" 
                             class="profile-image"
                             onerror="this.onerror=null; this.src='assets/images/default-avatar.png'; this.classList.add('profile-image-error');"
                             onload="this.classList.remove('image-loading')">
                    </div>
                    <div class="profile-info">
                        <h1><i class="fas fa-truck"></i> Delivery Dashboard</h1>
                        <p>Welcome, <?php echo htmlspecialchars($delivery_person['full_name']); ?></p>
                                    </div>
                                </div>
                                    </div>
                                    
            <div class="delivery-info">
                <div class="info-card">
                    <h3>Today's Deliveries</h3>
                    <p><?php echo count($orders); ?> orders pending</p>
                                                </div>
                <div class="info-card">
                    <h3>Contact Info</h3>
                    <p>Phone: <?php echo htmlspecialchars($delivery_person['mobile']); ?></p>
                                        </div>
                                    </div>
                                    
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-truck-loading fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
                    <div class="stat-label">Pending Deliveries</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value"><?php echo $stats['delivered_count']; ?></div>
                    <div class="stat-label">Completed Deliveries</div>
                                        </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-day fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value"><?php echo $stats['today_count']; ?></div>
                    <div class="stat-label">Today's Deliveries</div>
                                        </div>
                <div class="stat-card">
                    <i class="fas fa-rupee-sign fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value">₹<?php echo number_format($stats['total_earnings'], 2); ?></div>
                    <div class="stat-label">Total Earnings</div>
                                    </div>
                                </div>
                                
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="showTab('pending')">Pending Deliveries</button>
                    <button class="tab-button" onclick="showTab('completed')">Today's Completed</button>
                </div>

                <div id="pending-tab" class="tab-content">
                    <div class="orders-list">
                        <h2>Pending Deliveries</h2>
                        <?php if (empty($orders)): ?>
                            <p>No pending deliveries at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-id">
                                        <h4>Order #<?php echo $order['id']; ?></h4>
                                        <span class="order-status"><?php echo ucfirst($order['status']); ?></span>
                                    </div>
                                    <div class="customer-info">
                                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_mobile']); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                    </div>
                                    <div class="order-amount">
                                        <p><strong>Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                            Mark Delivered
                                        </button>
                                        <button class="btn btn-secondary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            View Details
                                        </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                </div>

                <div id="completed-tab" class="tab-content" style="display: none;">
                    <div class="delivery-timeline">
                        <?php foreach ($today_deliveries as $delivery): ?>
                            <div class="timeline-item">
                                <h4>Order #<?php echo $delivery['id']; ?> - <?php echo date('h:i A', strtotime($delivery['created_at'])); ?></h4>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                                <p><strong>Items:</strong> <?php echo htmlspecialchars($delivery['items']); ?></p>
                                <p><strong>Amount:</strong> ₹<?php echo number_format($delivery['total_amount'], 2); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Add loading class to profile image
        const profileImage = document.querySelector('.profile-image');
        profileImage.classList.add('image-loading');
    });

    function updateOrderStatus(orderId, status) {
        if (confirm('Are you sure you want to mark this order as delivered?')) {
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
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update order status');
            });
        }
    }

    function viewOrderDetails(orderId) {
        window.location.href = `order_details.php?id=${orderId}`;
    }

    function showTab(tabName) {
        const tabs = document.getElementsByClassName('tab-content');
        const buttons = document.getElementsByClassName('tab-button');
        
        for (let tab of tabs) {
            tab.style.display = 'none';
        }
        
        for (let button of buttons) {
            button.classList.remove('active');
        }
        
        document.getElementById(`${tabName}-tab`).style.display = 'block';
        document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }

    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
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

    // Active menu item handling
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.querySelector('.sidebar').classList.remove('active');
        }
    });

    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Show the selected section
        document.getElementById(sectionId).style.display = 'block';
        
        // Update active menu item
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[href="#${sectionId.replace('-section', '')}"]`).classList.add('active');
    }

    // Add click handlers to menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').replace('#', '') + '-section';
            showSection(sectionId);
        });
    });

    // Initialize earnings chart
    if (document.getElementById('earningsChart')) {
        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($earnings_data, 'date')); ?>,
                datasets: [{
                    label: 'Daily Earnings',
                    data: <?php echo json_encode(array_column($earnings_data, 'total')); ?>,
                    borderColor: '#4CAF50',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Handle profile picture preview
    document.getElementById('profile-picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle profile form submission
    document.getElementById('profile-update-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Profile updated successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to update profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update profile');
        });
    });

    // Initialize the dashboard view
    showSection('dashboard-section');
    </script>
</body>
</html>

