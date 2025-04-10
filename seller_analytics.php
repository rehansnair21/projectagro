<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$seller_id = $_SESSION['id'];

// Get seller details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

// Get analytics data
// Total revenue
$stmt = $conn->prepare("
    SELECT 
        SUM(oi.quantity * oi.price) as total_revenue,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.quantity) as total_units_sold,
        COUNT(DISTINCT p.id) as total_products
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$analytics = $stmt->get_result()->fetch_assoc();

// Get monthly revenue data
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(oi.quantity * oi.price) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$monthly_revenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get top products
$stmt = $conn->prepare("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.seller_id = ?
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 5
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Analytics - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Include the same CSS as seller_orders.php */
        /* Add these additional styles */
        .analytics-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .chart-container {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
        }

        .top-products {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .product-list {
            margin-top: 1rem;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: 500;
        }

        .product-stats {
            display: flex;
            gap: 2rem;
            color: #64748b;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Include the same header and sidebar as seller_orders.php -->
    <div class="container">
        <div class="dashboard-grid">
            <!-- Sidebar (same as seller_orders.php) -->
            
            <main class="analytics-container">
                <h1 style="margin-bottom: 2rem;">Analytics Dashboard</h1>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Revenue</h3>
                        <div class="stat-value">₹<?php echo number_format($analytics['total_revenue'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="stat-value"><?php echo $analytics['total_orders']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Units Sold</h3>
                        <div class="stat-value"><?php echo $analytics['total_units_sold']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo $analytics['total_products']; ?></div>
                    </div>
                </div>

                <div class="chart-container">
                    <h2>Monthly Revenue</h2>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="top-products">
                    <h2>Top Products</h2>
                    <div class="product-list">
                        <?php foreach ($top_products as $product): ?>
                        <div class="product-item">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-stats">
                                <span><?php echo $product['total_sold']; ?> units sold</span>
                                <span>₹<?php echo number_format($product['revenue'], 2); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column(array_reverse($monthly_revenue), 'month')); ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?php echo json_encode(array_column(array_reverse($monthly_revenue), 'revenue')); ?>,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
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
    </script>
</body>
</html> 