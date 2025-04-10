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

// Get earnings statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(total_amount) as total_earnings,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_deliveries,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_earnings
    FROM orders 
    WHERE status = 'delivered'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get monthly earnings
$monthly_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as delivery_count,
        SUM(total_amount) as earnings
    FROM orders 
    WHERE status = 'delivered'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12";
$monthly_result = $conn->query($monthly_query);
$monthly_earnings = $monthly_result->fetch_all(MYSQLI_ASSOC);

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
    <title>Earnings - AgroFresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Stats Grid */
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

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
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

        /* Earnings Chart */
        .earnings-chart {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .earnings-chart h2 {
            color: var(--text-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        /* Earnings Table */
        .earnings-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .earnings-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .earnings-table th,
        .earnings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .earnings-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .earnings-table tr:hover {
            background: #f8f9fa;
        }

        .earnings-table td {
            color: var(--text-color);
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .earnings-table {
                overflow-x: auto;
            }

            .earnings-table table {
                min-width: 600px;
            }
        }

        /* Status Indicator */
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

        /* Chart Customization */
        canvas {
            width: 100% !important;
            height: 300px !important;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            animation: fadeIn 0.5s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Additional Utility Classes */
        .text-primary {
            color: var(--primary-color);
        }

        .text-center {
            text-align: center;
        }

        .mb-2 {
            margin-bottom: 1rem;
        }

        .font-bold {
            font-weight: bold;
        }

        /* Loading States */
        .loading {
            position: relative;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Print Styles */
        @media print {
            .sidebar, .sidebar-toggle {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .earnings-chart {
                page-break-inside: avoid;
            }

            .earnings-table {
                page-break-inside: avoid;
            }
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
            <a href="delivery_history.php" class="menu-item">
                <i class="fas fa-history"></i>
                Delivery History
            </a>
            <a href="earnings.php" class="menu-item active">
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
                <h1><i class="fas fa-rupee-sign"></i> Earnings</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-rupee-sign fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value">₹<?php echo number_format($stats['total_earnings'], 2); ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-truck fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value"><?php echo $stats['total_deliveries']; ?></div>
                    <div class="stat-label">Total Deliveries</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-day fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value">₹<?php echo number_format($stats['today_earnings'], 2); ?></div>
                    <div class="stat-label">Today's Earnings</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box fa-2x" style="color: var(--primary-color)"></i>
                    <div class="stat-value"><?php echo $stats['today_deliveries']; ?></div>
                    <div class="stat-label">Today's Deliveries</div>
                </div>
            </div>

            <div class="earnings-chart">
                <h2>Monthly Earnings</h2>
                <canvas id="monthlyEarningsChart"></canvas>
            </div>

            <div class="earnings-table">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Deliveries</th>
                            <th>Earnings</th>
                            <th>Average per Delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_earnings as $earning): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($earning['month'] . '-01')); ?></td>
                                <td><?php echo $earning['delivery_count']; ?></td>
                                <td>₹<?php echo number_format($earning['earnings'], 2); ?></td>
                                <td>₹<?php echo number_format($earning['earnings'] / $earning['delivery_count'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize monthly earnings chart
        const ctx = document.getElementById('monthlyEarningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($earning) {
                    return date('F Y', strtotime($earning['month'] . '-01'));
                }, array_reverse($monthly_earnings))); ?>,
                datasets: [{
                    label: 'Monthly Earnings (₹)',
                    data: <?php echo json_encode(array_map(function($earning) {
                        return $earning['earnings'];
                    }, array_reverse($monthly_earnings))); ?>,
                    backgroundColor: '#4CAF50',
                    borderColor: '#45a049',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
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