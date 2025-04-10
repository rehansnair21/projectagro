<?php
require_once 'db_connection.php';
session_start();

// Get user identifier based on login status
$user_id = null;
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = session_id(); // Use session_id for guest users
}

// Get cart count
$cartCount = 0;
$cartQuery = "SELECT COALESCE(SUM(quantity), 0) as total 
              FROM cart c 
              WHERE c.user_id = ? 
              AND c.is_guest = ? 
              AND EXISTS (SELECT 1 FROM products p WHERE p.id = c.product_id)"; // Only count items that exist in products

$stmt = $conn->prepare($cartQuery);
if ($stmt) {
    $is_guest = !$is_logged_in;
    $stmt->bind_param("si", $user_id, $is_guest);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cartCount = (int)$row['total']; // Explicitly cast to integer
    }
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// Add this cleanup query to remove any orphaned cart items
$cleanup_query = "DELETE FROM cart 
                 WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.id = cart.product_id)
                 OR quantity <= 0";
$conn->query($cleanup_query);

// Get current user's seller ID if they are logged in
$current_seller_id = null;
if ($is_logged_in) {
    $sellerQuery = "SELECT id FROM sellerdetails WHERE user_id = ?";
    $stmt = $conn->prepare($sellerQuery);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $sellerResult = $stmt->get_result();
        if ($sellerRow = $sellerResult->fetch_assoc()) {
            $current_seller_id = $sellerRow['id'];
        }
        $stmt->close();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
}

// Get unique categories
$categoriesQuery = "SELECT DISTINCT category FROM products ORDER BY category";
$categoryResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Modify the products query to include category filter
$sql = "SELECT * FROM products WHERE seller_id != ?";
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $sql .= " AND category = ?";
}
$stmt = $conn->prepare($sql);

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $stmt->bind_param("is", $user_id, $_GET['category']);
} else {
    $stmt->bind_param("i", $user_id);
}

// Check if prepare was successful
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

// Filter out products and add seller info in PHP instead of SQL
$filteredResult = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Skip products where seller_id matches current user's seller_id
        if ($current_seller_id !== null && $row['seller_id'] == $current_seller_id) {
            continue;
        }
        
        // Get seller info
        $sellerInfoSql = "SELECT full_name as seller_name 
                          FROM sellerdetails 
                          WHERE id = ?";
        $stmt2 = $conn->prepare($sellerInfoSql);

        // Check if prepare was successful before binding
        if ($stmt2 === false) {
            // Just skip this seller's info if prepare fails
            $row['seller_name'] = 'Unknown Seller';
        } else {
            $stmt2->bind_param("i", $row['seller_id']);
            $stmt2->execute();
            $sellerResult = $stmt2->get_result();
            if ($sellerRow = $sellerResult->fetch_assoc()) {
                $row['seller_name'] = $sellerRow['seller_name'];
            } else {
                $row['seller_name'] = 'Unknown Seller';
            }
            $stmt2->close();
        }
        
        $filteredResult[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroFresh - Fresh Fruits & Vegetables</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
        }

        body {
            background-color: var(--light);
            overflow-x: hidden;
            padding-top: 80px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.25rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .become-seller-btn {
            background: var(--accent);
            color: white !important;
            padding: 0.8rem 1.5rem;
            border-radius: 100px;
            transition: all 0.3s ease;
        }

        .become-seller-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .cart-button {
            background: var(--gradient);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 100px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .cart-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2);
        }

        .section-header {
            text-align: center;
            padding: 2rem 0;
            max-width: 800px;
            margin: 0 auto;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: #64748b;
            font-size: 1.2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .seller-info {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .seller-info i {
            color: var(--primary);
        }

        .add-to-cart {
            width: 100%;
            padding: 1rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2);
        }

        .product-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .nav {
                padding: 0 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .products-grid {
                padding: 1rem;
            }
        }

        .no-products {
            text-align: center;
            padding: 4rem;
            color: #64748b;
            font-size: 1.2rem;
            grid-column: 1 / -1;
        }

        .category-filter {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 2rem;
        }

        .category-wrapper {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            padding: 1rem 0;
        }

        .category-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border-radius: 100px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .category-item.active {
            background: var(--gradient);
            color: white;
            border-color: transparent;
        }

        .category-item i {
            font-size: 1.1rem;
        }

        /* Add responsive styles for category filter */
        @media (max-width: 768px) {
            .category-filter {
                padding: 0 1rem;
                margin-bottom: 1rem;
            }

            .category-wrapper {
                gap: 0.5rem;
            }

            .category-item {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Add a subtle animation for category items */
        .category-item {
            animation: fadeIn 0.3s ease;
        }

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
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">AgroFresh</div>
            <div class="nav-links">
                <a href="template.php">Home</a>
                <a href="seller_register.php" class="become-seller-btn" style="text-decoration: none;">Become a Seller</a>
                <button class="cart-button" id="cartButton">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count">Cart (<?php echo $cartCount; ?>)</span>
                    
                </button>
            </div>
        </nav>
    </header>

    <main>
        <div class="section-header">
            <h2>Fresh Fruits & Vegetables</h2>
            <p>Farm-fresh produce delivered to your doorstep</p>
        </div>

        <div class="category-filter">
            <div class="category-wrapper">
                <a href="sale.php" class="category-item <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">
                    <i class="fas fa-border-all"></i>
                    All Categories
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="sale.php?category=<?php echo urlencode($category); ?>" 
                       class="category-item <?php echo (isset($_GET['category']) && $_GET['category'] === $category) ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            echo match($category) {
                                'vegetables' => 'carrot',
                                'fruits' => 'apple-alt',
                                'milk' => 'milk',
                                default => 'shopping-basket'
                            };
                        ?>"></i>
                        <?php echo ucfirst(htmlspecialchars($category)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="products-grid">
            <?php
            if (!empty($filteredResult)) {
                foreach ($filteredResult as $row) {
            ?>
                    <div class="product-card" data-product-id="<?php echo $row['id']; ?>">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                        <div class="product-category"><?php echo ucfirst(htmlspecialchars($row['category'])); ?></div>
                        <div class="product-details">
                            <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="product-price">₹<?php echo htmlspecialchars($row['price']); ?>/<?php echo $row['category'] === 'milk' ? 'liter' : 'kg'; ?></p>
                            <p class="seller-info">
                                <i class="fas fa-store"></i>
                                Seller Name: <?php echo htmlspecialchars($row['seller_name']); ?>
                            </p>
                            <p class="product-description"><?php echo htmlspecialchars($row['description']); ?></p>
                            <p class="seller-info">
                                <i class="fas fa-box"></i>
                                Stock Available: <?php echo htmlspecialchars($row['stock']); ?> <?php echo $row['category'] === 'milk' ? 'liters' : 'kg'; ?>
                            </p>
                            <button class="add-to-cart" data-seller-id="<?php echo $row['seller_id']; ?>" onclick="addToCart({
                                id: <?php echo $row['id']; ?>,
                                name: '<?php echo addslashes($row['name']); ?>',
                                price: <?php echo $row['price']; ?>,
                                seller_id: <?php echo $row['seller_id']; ?>
                            })">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-products">No products found. Check back later for fresh products!</div>';
            }
            ?>
        </div>
    </main>

    <script>
        // Add a variable to store current seller ID
        const currentSellerId = <?php echo $current_seller_id ? $current_seller_id : 'null'; ?>;
        
        function showNotification(message, isError = false) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 1000;
                transition: all 0.3s ease;
                background: ${isError ? '#ef4444' : 'var(--primary)'};
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }
        
        function addToCart(product) {
            // Check if user is logged in as a seller
            if (currentSellerId !== null) {
                // Check if current user is the seller of this product
                if (product.seller_id === currentSellerId) {
                    showNotification("You cannot purchase your own products", true);
                    return;
                }
            }
            
            const data = new FormData();
            data.append('product_id', product.id);
            data.append('action', 'add');

            // Get the button before the fetch call
            const productCard = document.querySelector(`[data-product-id="${product.id}"]`);
            const button = productCard.querySelector('.add-to-cart');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            fetch('cart_actions.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                
                // Update cart count if it exists in the response
                if (typeof data.cartCount !== 'undefined') {
                    document.getElementById('cart-count').textContent = `Cart (${data.cartCount})`;
                    
                    // Show success message and update stock display
                    const stockInfo = productCard.querySelector('.seller-info:last-child');
                    
                    // Update the stock display
                    if (data.newStock !== undefined) {
                        const unit = stockInfo.textContent.includes('liters') ? 'liters' : 'kg';
                        stockInfo.innerHTML = `<i class="fas fa-box"></i> Stock Available: ${data.newStock} ${unit}`;
                    }

                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    showNotification("Item added to cart successfully");
                    
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    }, 2000);
                } else if (data.error) {
                    button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    showNotification(data.error, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
            });
        }

        document.getElementById('cartButton').addEventListener('click', () => {
            window.location.href = 'cart.php';
        });
        
        // Disable add to cart buttons for seller's own products
        if (currentSellerId !== null) {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                const sellerId = parseInt(button.getAttribute('data-seller-id'));
                if (sellerId === currentSellerId) {
                    button.disabled = true;
                    button.innerHTML = "Cannot purchase your own product";
                    button.style.opacity = "0.7";
                    button.style.cursor = "not-allowed";
                    button.style.background = "#64748b";
                }
            });
        }

        // Add smooth scrolling when changing categories
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                // Smooth scroll to products
                document.querySelector('.products-grid').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });

                // Change URL after small delay
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>