<?php
session_start();
require_once 'db_connection.php';

// Add this right after session_start()
$message = '';
$messageClass = '';

if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageClass = 'success';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageClass = 'error';
    unset($_SESSION['error']);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: login.php');
    exit();
}

// Fetch counts for dashboard
$counts = [
    'users' => 0,
    'sellers' => 0,
    'products' => 0,
    'orders' => 0
];

// Get users count (excluding admin)
$query = "SELECT COUNT(*) as count FROM users WHERE role != 'admin'";
$result = $conn->query($query);
if ($result) {
    $counts['users'] = $result->fetch_assoc()['count'];
}

// Get sellers count
$query = "SELECT COUNT(*) as count FROM sellerdetails";
$result = $conn->query($query);
if ($result) {
    $counts['sellers'] = $result->fetch_assoc()['count'];
}

// Get products count
$query = "SELECT COUNT(*) as count FROM products";
$result = $conn->query($query);
if ($result) {
    $counts['products'] = $result->fetch_assoc()['count'];
}

// Get orders count
$query = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($query);
if ($result) {
    $counts['orders'] = $result->fetch_assoc()['count'];
}

// Fetch recent users
$users_query = "SELECT id, full_name, email, mobile, role, created_at, city, state, address 
                FROM users 
                WHERE role != 'admin'
                ORDER BY created_at DESC 
                LIMIT 10";
$users_result = $conn->query($users_query);

// Fetch recent sellers
$sellers_query = "SELECT sd.*, u.email, u.mobile, u.city, u.state, u.created_at
                 FROM sellerdetails sd
                 LEFT JOIN users u ON sd.email = u.email
                 ORDER BY sd.created_at DESC 
                 LIMIT 10";
$sellers_result = $conn->query($sellers_query);

// Fetch recent products with seller information
$products_query = "SELECT p.*, u.full_name as seller_name 
                  FROM products p
                  JOIN users u ON p.seller_id = u.id
                  ORDER BY p.created_at DESC 
                  LIMIT 10";
$products_result = $conn->query($products_query);

// Fetch recent orders with user information
$orders_query = "SELECT o.*, u.full_name as customer_name, u.email,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
                 FROM orders o
                 JOIN users u ON o.user_id = u.id
                 ORDER BY o.created_at DESC
                 LIMIT 10";
$orders_result = $conn->query($orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --dark: #1e293b;
            --light: #f8fafc;
            --sidebar-width: 250px;
            --header-height: 60px;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
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
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: white;
            padding: 1rem;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .nav-link.active {
            background: var(--gradient);
            color: white;
        }

        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .data-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--dark);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #0ea5e9;
            color: white;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-processing {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-delivered {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .dashboard-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .summary-title {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }

        .data-table td {
            vertical-align: middle;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .price-column {
            font-weight: 600;
            color: #16a34a;
        }

        .stock-warning {
            color: #dc2626;
            font-weight: 500;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #16a34a;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #dc2626;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">AgroFresh Admin</div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="#dashboard" class="nav-link active">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="#users" class="nav-link">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a href="#sellers" class="nav-link">
                    <i class="fas fa-store"></i>
                    Sellers
                </a>
            </li>
            <li class="nav-item">
                <a href="#products" class="nav-link">
                    <i class="fas fa-box"></i>
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a href="#orders" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0);" onclick="logout()" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageClass; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $counts['users']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Sellers</h3>
                <div class="number"><?php echo $counts['sellers']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $counts['products']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $counts['orders']; ?></div>
            </div>
        </div>

        <div id="dashboard-section">
            <div class="dashboard-summary">
                <h2 class="section-title">Recent Activity Overview</h2>
                <div class="summary-content">
                    <div class="data-section">
                        <div class="section-header">
                            <h3 class="section-title">Latest Orders</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // New separate query for dashboard orders
                                $dashboard_orders_query = "SELECT o.id, o.total_amount, o.status, u.full_name as customer_name 
                                                        FROM orders o
                                                        JOIN users u ON o.user_id = u.id
                                                        ORDER BY o.created_at DESC
                                                        LIMIT 5";
                                $dashboard_orders_result = $conn->query($dashboard_orders_query);
                                
                                if ($dashboard_orders_result && $dashboard_orders_result->num_rows > 0) {
                                    while ($order = $dashboard_orders_result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="price-column">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo $order['status'] ?: 'pending'; ?>">
                                                <?php echo ucfirst($order['status'] ?: 'pending'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php 
                                    endwhile;
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center;'>No orders found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="other-sections" style="display: none;">
            <div class="data-section" id="users">
                <div class="section-header">
                    <h2 class="section-title">Users List</h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Address</th>
                            <th>Location</th>
                            <th>Role</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($users_result && $users_result->num_rows > 0) {
                            while ($user = $users_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($user['address'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $location = [];
                                    if (!empty($user['city'])) $location[] = $user['city'];
                                    if (!empty($user['state'])) $location[] = $user['state'];
                                    echo htmlspecialchars(implode(', ', $location));
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center;'>No users found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="data-section" id="orders">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td><?php echo $order['items_count']; ?> items</td>
                            <td class="price-column">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="order-status status-<?php echo $order['status'] ?: 'pending'; ?>">
                                    <?php echo ucfirst($order['status'] ?: 'pending'); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="viewOrder(<?php echo $order['id']; ?>)">View</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="data-section" id="products">
                <div class="section-header">
                    <h2 class="section-title">Recent Products</h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Seller</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/images/default-product.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td class="price-column">₹<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="stock-warning"><?php echo $product['stock']; ?> left</span>
                                <?php else: ?>
                                    <?php echo $product['stock']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                            <td>
                                <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="data-section" id="sellers">
                <div class="section-header">
                    <h2 class="section-title">Sellers List</h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Address</th>
                            <th>Location</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($sellers_result && $sellers_result->num_rows > 0) {
                            while ($seller = $sellers_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>#<?php echo $seller['id']; ?></td>
                                <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                <td><?php echo htmlspecialchars($seller['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($seller['address']); ?></td>
                                <td>
                                    <?php 
                                    $location = [];
                                    if ($seller['city']) $location[] = $seller['city'];
                                    if ($seller['state']) $location[] = $seller['state'];
                                    echo htmlspecialchars(implode(', ', $location));
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($seller['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn delete-btn" onclick="deleteSeller(<?php echo $seller['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center;'>No sellers found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                if (targetId === 'dashboard') {
                    document.getElementById('dashboard-section').style.display = 'block';
                    document.getElementById('other-sections').style.display = 'none';
                } else {
                    document.getElementById('dashboard-section').style.display = 'none';
                    document.getElementById('other-sections').style.display = 'block';
                    
                    const section = document.getElementById(targetId);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        document.querySelector('a[href="#dashboard"]').click();

        function viewOrder(orderId) {
            window.location.href = `view_order.php?id=${orderId}`;
        }

        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.header').prepend(menuToggle);

        menuToggle.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        function deleteSeller(sellerId) {
            showDeleteConfirmation(
                'Delete Seller',
                'Are you sure you want to delete this seller? This will:',
                [
                    'Remove all their products from the marketplace',
                    'Delete their seller profile',
                    'Convert their account to a regular user account'
                ],
                () => {
                    window.location.href = `delete_seller.php?id=${sellerId}`;
                }
            );
        }

        function deleteUser(userId) {
            showDeleteConfirmation(
                'Delete User',
                'Are you sure you want to delete this user? This will:',
                [
                    'Permanently remove their account',
                    'Delete all their orders and cart items',
                    'Remove their seller profile (if they are a seller)'
                ],
                () => {
                    window.location.href = `delete_user.php?id=${userId}`;
                }
            );
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function showDeleteConfirmation(title, message, consequences, onConfirm) {
            const modal = document.createElement('div');
            modal.className = 'delete-confirmation-modal';
            
            const modalContent = `
                <div class="modal-content">
                    <h3>${title}</h3>
                    <p>${message}</p>
                    <ul>
                        ${consequences.map(item => `<li>${item}</li>`).join('')}
                    </ul>
                    <div class="modal-buttons">
                        <button class="action-btn delete-btn" id="confirmDelete">Yes, Delete</button>
                        <button class="action-btn" id="cancelDelete">Cancel</button>
                    </div>
                </div>
            `;
            
            modal.innerHTML = modalContent;
            document.body.appendChild(modal);
            
            const style = document.createElement('style');
            style.textContent = `
                .delete-confirmation-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                }
                .modal-content {
                    background: white;
                    padding: 2rem;
                    border-radius: 1rem;
                    max-width: 500px;
                    width: 90%;
                }
                .modal-content h3 {
                    margin-bottom: 1rem;
                    color: #dc2626;
                }
                .modal-content ul {
                    margin: 1rem 0;
                    padding-left: 1.5rem;
                }
                .modal-content li {
                    margin-bottom: 0.5rem;
                    color: #4b5563;
                }
                .modal-buttons {
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                    margin-top: 1.5rem;
                }
            `;
            document.head.appendChild(style);
            
            document.getElementById('confirmDelete').addEventListener('click', () => {
                modal.remove();
                onConfirm();
            });
            
            document.getElementById('cancelDelete').addEventListener('click', () => {
                modal.remove();
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        function deleteProduct(productId) {
            showDeleteConfirmation(
                'Delete Product',
                'Are you sure you want to delete this product? This will:',
                [
                    'Remove the product from the marketplace',
                    'Remove all related cart items',
                    'This action cannot be undone'
                ],
                () => {
                    // Send POST request using Fetch API
                    fetch('delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the product row from the table
                            const row = document.querySelector(`button[onclick*="${productId}"]`).closest('tr');
                            if (row) {
                                row.remove();
                            }
                            
                            // Show success message
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success';
                            alertDiv.textContent = 'Product deleted successfully';
                            document.querySelector('.header').insertAdjacentElement('afterend', alertDiv);
                            
                            // Remove alert after 3 seconds
                            setTimeout(() => alertDiv.remove(), 3000);
                        } else {
                            // Show error message
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-error';
                            alertDiv.textContent = data.error || 'Failed to delete product';
                            document.querySelector('.header').insertAdjacentElement('afterend', alertDiv);
                            
                            // Remove alert after 3 seconds
                            setTimeout(() => alertDiv.remove(), 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-error';
                        alertDiv.textContent = 'Error deleting product';
                        document.querySelector('.header').insertAdjacentElement('afterend', alertDiv);
                        
                        // Remove alert after 3 seconds
                        setTimeout(() => alertDiv.remove(), 3000);
                    });
                }
            );
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>