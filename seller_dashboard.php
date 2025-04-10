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

// Get seller's products
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT o.id) as total_orders,
           SUM(oi.quantity) as total_units_sold
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

// Get recent orders for seller's products
$stmt = $conn->prepare("
    SELECT o.*, oi.quantity, oi.price as item_price, p.name as product_name,
           u.full_name as buyer_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE p.seller_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - AgroFresh</title>
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
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

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

        .main-content {
            display: grid;
            gap: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .products-table, .orders-table {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: var(--secondary);
            color: white;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
        }

        .add-product-btn {
            background: var(--gradient);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-delivered { background: #dcfce7; color: #166534; }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Add these new styles to your existing style section */
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

        .category-badge.vegetables {
            background: #dcfce7;
            color: #166534;
        }

        .sidebar-menu {
            flex-grow: 1;
            margin: 0;
            padding: 0;
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

        /* Responsive Adjustments */
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

        /* CSV Upload Section Styles */
        .csv-upload-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .csv-instructions {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .csv-instructions code {
            display: block;
            background: #1e293b;
            color: #e2e8f0;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin: 0.5rem 0;
            font-family: monospace;
        }

        .template-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .template-btn:hover {
            background: var(--primary-dark);
        }

        .csv-form {
            margin-top: 1.5rem;
        }

        .file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
        }

        .file-upload:hover {
            border-color: var(--primary);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            color: #64748b;
        }

        .file-upload i {
            font-size: 2rem;
            color: var(--primary);
        }

        .upload-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.2s ease;
        }

        .upload-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .file-upload.highlight {
            border-color: var(--primary);
            background-color: rgba(34, 197, 94, 0.05);
        }

        .file-upload label span {
            font-size: 0.875rem;
        }

        /* Progress bar styles */
        .upload-progress {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 1rem;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background: var(--gradient);
            border-radius: 2px;
            width: 0;
            transition: width 0.3s ease;
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
        <?php if (isset($_SESSION['csv_upload_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['csv_upload_message']['type']; ?>">
                <?php 
                echo $_SESSION['csv_upload_message']['text'];
                unset($_SESSION['csv_upload_message']);
                ?>
            </div>
        <?php endif; ?>
        <div class="dashboard-grid">
            <aside class="sidebar">
                <div class="seller-info">
                    <div class="seller-name"><?php echo htmlspecialchars($seller['full_name']); ?></div>
                    <div class="seller-email"><?php echo htmlspecialchars($seller['email']); ?></div>
                    <div class="category-badge vegetables">Verified Seller</div>
                </div>

                <ul class="sidebar-menu">
                    <li>
                        <a href="seller_dashboard.php" class="active">
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
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo count($products); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="stat-value">
                            <?php 
                            echo array_sum(array_column($products, 'total_orders')); 
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Units Sold</h3>
                        <div class="stat-value">
                            <?php 
                            echo array_sum(array_column($products, 'total_units_sold')); 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="products-table">
                    <h2 style="margin-bottom: 1.5rem;">Your Products</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Orders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.5rem;">
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div style="color: #64748b; font-size: 0.875rem;">
                                                <?php echo htmlspecialchars($product['category']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['total_orders']; ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="action-btn edit-btn">Edit</a>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                            class="action-btn delete-btn">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="orders-table">
                    <h2 style="margin-bottom: 1.5rem;">Recent Orders</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['product_name']); ?>
                                    <div style="color: #64748b; font-size: 0.875rem;">
                                        Qty: <?php echo $order['quantity']; ?>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($order['item_price'] * $order['quantity'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="csv-upload-section">
                    <h2>Bulk Product Upload</h2>
                    <div class="csv-instructions">
                        <p>Upload a CSV file with the following columns:</p>
                        <code>product_name,price,category,stock</code>
                        <a href="templates/product_template.csv" download="product_template.csv" class="template-btn">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    
                    <form action="process_csv.php" method="post" enctype="multipart/form-data" class="csv-form" id="csvUploadForm">
                        <div class="file-upload">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <label for="csv_file">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose CSV file or drag it here</span>
                            </label>
                            <div class="upload-progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                        <button type="submit" class="upload-btn">
                            <i class="fas fa-upload"></i> Upload and Process
                        </button>
                    </form>
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

    // File Upload Handling
    document.addEventListener('DOMContentLoaded', function() {
        const fileUpload = document.querySelector('.file-upload');
        const fileInput = document.getElementById('csv_file');
        const fileLabel = document.querySelector('.file-upload label span');
        const originalLabelText = fileLabel.textContent;

        // Handle drag and drop events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUpload.classList.add('highlight');
        }

        function unhighlight(e) {
            fileUpload.classList.remove('highlight');
        }

        // Handle file drop
        fileUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileName(files[0]);
        }

        // Handle file input change
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                updateFileName(this.files[0]);
            }
        });

        function updateFileName(file) {
            if (file) {
                if (file.type !== 'text/csv') {
                    alert('Please upload a CSV file');
                    fileInput.value = '';
                    fileLabel.textContent = originalLabelText;
                    return;
                }
                fileLabel.textContent = file.name;
            } else {
                fileLabel.textContent = originalLabelText;
            }
        }
    });

    // Add this to your existing JavaScript
    document.getElementById('csvUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const progressBar = document.querySelector('.progress-bar');
        const progressContainer = document.querySelector('.upload-progress');
        const submitButton = this.querySelector('button[type="submit"]');
        
        if (!formData.get('csv_file').name) {
            alert('Please select a file first');
            return;
        }

        // Show progress bar
        progressContainer.style.display = 'block';
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

        fetch('process_csv.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload
                alert(data.message || 'Products uploaded successfully!');
                location.reload();
            } else {
                // Show error message
                alert(data.error || 'Failed to upload products');
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-upload"></i> Upload and Process';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading the file');
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-upload"></i> Upload and Process';
        })
        .finally(() => {
            progressContainer.style.display = 'none';
            progressBar.style.width = '0%';
        });

        // Simulate upload progress
        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            if (progress > 90) clearInterval(interval);
            progressBar.style.width = progress + '%';
        }, 100);
    });
    </script>
</body>
</html> 