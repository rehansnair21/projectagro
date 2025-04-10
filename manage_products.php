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
$stmt = $conn->prepare("SELECT * FROM sellerdetails WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

// Get seller's products with additional details
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT o.id) as total_orders,
           SUM(oi.quantity) as total_units_sold,
           COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE p.seller_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f0f5f1;
            padding-top: 80px;
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

        /* Container and Grid Layout */
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

        /* Main Content Styles */
        .main-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            color: var(--dark);
        }

        .add-product-btn {
            background: var(--gradient);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .add-product-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .product-category {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .stat:hover {
            background: #f1f5f9;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-weight: 600;
            color: var(--dark);
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .edit-btn {
            background: var(--secondary);
            color: white;
        }

        .edit-btn:hover {
            background: #0284c7;
        }

        .delete-btn {
            background: #fee2e2;
            color: #ef4444;
        }

        .delete-btn:hover {
            background: #fecaca;
        }

        /* Help Section */
        .help-section {
            margin-top: auto;
            padding-top: 1.5rem;
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
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeIn 0.3s ease-out;
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
                        <a href="manage_products.php" class="active">
                            <i class="fas fa-box"></i>
                            Manage Products
                        </a>
                    </li>
                    <li>
                        <a href="seller_orders.php">
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

            <main class="main-content">
                <div class="page-header">
                    <h1>Manage Products</h1>
                    <a href="add_product.php" class="add-product-btn">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>

                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-category">
                                <i class="fas fa-tag"></i> 
                                <?php echo htmlspecialchars($product['category']); ?>
                            </div>
                            
                            <div class="product-stats">
                                <div class="stat">
                                    <div class="stat-label">Price</div>
                                    <div class="stat-value">₹<?php echo number_format($product['price'], 2); ?></div>
                                </div>
                                <div class="stat">
                                    <div class="stat-label">Stock</div>
                                    <div class="stat-value"><?php echo $product['stock']; ?></div>
                                </div>
                                <div class="stat">
                                    <div class="stat-label">Orders</div>
                                    <div class="stat-value"><?php echo $product['total_orders']; ?></div>
                                </div>
                                <div class="stat">
                                    <div class="stat-label">Units Sold</div>
                                    <div class="stat-value"><?php echo $product['total_units_sold'] ?? 0; ?></div>
                                </div>
                            </div>

                            <div class="product-actions">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                   class="action-btn edit-btn">Edit</a>
                                <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                        class="action-btn delete-btn">Delete</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            fetch('delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete product');
            });
        }
    }
    </script>
</body>
</html> 