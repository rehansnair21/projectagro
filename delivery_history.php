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

// Get delivery history with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'";
$total_result = $conn->query($count_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $items_per_page);

// Get delivery history with proper joins and fields
$query = "
    SELECT 
        o.id,
        o.total_amount,
        o.shipping_address,
        o.payment_method,
        o.status,
        o.created_at,
        o.payment_id,
        u.full_name as customer_name,
        u.mobile as customer_mobile,
        GROUP_CONCAT(
            CONCAT(oi.quantity, 'x ', p.name)
            SEPARATOR ', '
        ) as items
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.status = 'delivered'
    GROUP BY 
        o.id,
        o.total_amount,
        o.shipping_address,
        o.payment_method,
        o.status,
        o.created_at,
        o.payment_id,
        u.full_name,
        u.mobile
    ORDER BY o.created_at DESC
    LIMIT ?, ?";

// Prepare and execute the query with error handling
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("ii", $offset, $items_per_page);
$success = $stmt->execute();
if (!$success) {
    die("Error executing query: " . $stmt->error);
}

$delivery_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Separately get items for each order if needed
foreach ($delivery_history as &$order) {
    $items_query = "
        SELECT oi.quantity, p.name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?";
    
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order['id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item['quantity'] . 'x ' . $item['name'];
    }
    
    $order['items'] = implode(', ', $items);
}

function getProfileImage($photo_url) {
    $default_avatar = 'assets/images/default-avatar.png';
    if (empty($photo_url) || $photo_url === 'default-avatar.png') {
        return $default_avatar;
    }
    $full_path = __DIR__ . '/' . $photo_url;
    return file_exists($full_path) ? $photo_url : $default_avatar;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History - AgroFresh</title>
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

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .page-header h1 {
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* History Filters */
        .history-filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        /* History Cards */
        .history-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .history-card:hover {
            transform: translateY(-2px);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .delivery-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .delivery-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            background: #e8f5e9;
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Buttons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
        }

        /* Empty State */
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px 0;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid var(--primary-color);
            border-radius: 5px;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #e8f5e9;
        }

        .page-link.active {
            background: var(--primary-color);
            color: white;
        }

        /* Status Indicators */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-online {
            background: #28a745;
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

            .delivery-details {
                grid-template-columns: 1fr;
            }

            .history-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .date-input {
                width: 100%;
            }
        }

        /* Additional Utility Classes */
        .text-primary {
            color: var(--primary-color);
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-small {
            font-size: 0.9rem;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .d-flex {
            display: flex;
        }

        .align-center {
            align-items: center;
        }

        .gap-2 {
            gap: 10px;
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
            <a href="active_deliveries.php" class="menu-item">
                <i class="fas fa-truck"></i>
                Active Deliveries
            </a>
            <a href="delivery_history.php" class="menu-item active">
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
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Delivery History</h1>
            </div>

            <div class="history-filters">
                <div class="filter-group">
                    <label for="start-date">From:</label>
                    <input type="date" id="start-date" class="date-input">
                </div>
                <div class="filter-group">
                    <label for="end-date">To:</label>
                    <input type="date" id="end-date" class="date-input">
                </div>
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>

            <?php if (empty($delivery_history)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h2>No Delivery History</h2>
                    <p>You haven't completed any deliveries yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($delivery_history as $delivery): ?>
                    <div class="history-card">
                        <div class="history-header">
                            <h3>Order #<?php echo $delivery['id']; ?></h3>
                            <span class="delivery-status">Delivered</span>
                        </div>
                        <div class="delivery-details">
                            <div class="customer-info">
                                <p><i class="fas fa-user"></i> <strong>Customer:</strong> 
                                    <?php echo htmlspecialchars($delivery['customer_name']); ?>
                                </p>
                                <p><i class="fas fa-phone"></i> <strong>Phone:</strong> 
                                    <?php echo htmlspecialchars($delivery['customer_mobile']); ?>
                                </p>
                                <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> 
                                    <?php echo htmlspecialchars($delivery['shipping_address']); ?>
                                </p>
                            </div>
                            <div class="order-info">
                                <p><i class="fas fa-box"></i> <strong>Items:</strong></p>
                                <p><?php echo htmlspecialchars($delivery['items']); ?></p>
                            </div>
                            <div class="delivery-info">
                                <p><i class="fas fa-calendar"></i> <strong>Delivered on:</strong></p>
                                <p><?php echo date('d M Y, h:i A', strtotime($delivery['created_at'])); ?></p>
                                <p><i class="fas fa-rupee-sign"></i> <strong>Amount:</strong> 
                                    ₹<?php echo number_format($delivery['total_amount'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function applyFilters() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (startDate && endDate) {
                window.location.href = `delivery_history.php?start=${startDate}&end=${endDate}`;
            } else {
                alert('Please select both start and end dates');
            }
        }

        // Mobile sidebar handling
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        });
    </script>
</body>
</html> 