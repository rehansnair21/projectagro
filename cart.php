<?php
session_start();
require_once 'db_connection.php';

// Get user ID or guest ID
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : session_id();
$is_guest = !isset($_SESSION['id']);

// Get cart items
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.image_url, p.stock, p.category,
           (p.price * c.quantity) as total_price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND c.is_guest = ?
");
$stmt->bind_param("si", $user_id, $is_guest);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate cart totals
$cart_total = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['total_price'];
    $total_items += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        .cart-items {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .cart-summary {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .cart-item {
            background: white;
            border-radius: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .item-category {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .quantity-btn:disabled {
            background: #e2e8f0;
            cursor: not-allowed;
            transform: none;
        }

        .quantity {
            background: #f8fafc;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            min-width: 40px;
            text-align: center;
        }

        .remove-btn {
            color: #ef4444;
            background: #fee2e2;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .remove-btn:hover {
            background: #fecaca;
            color: #dc2626;
            transform: scale(1.1);
        }

        .summary-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #64748b;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
            font-weight: 600;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .checkout-btn {
            background: var(--gradient);
            color: white;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .checkout-btn:active {
            transform: translateY(0);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .empty-cart i {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-cart h2 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .continue-shopping {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .continue-shopping:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading {
            animation: spin 1s linear infinite;
        }

        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .cart-summary {
                position: static;
                margin-top: 1rem;
            }

            .cart-item {
                padding: 1rem;
            }

            .item-image {
                width: 80px;
                height: 80px;
            }

            .header {
                padding: 0.75rem;
            }

            .nav {
                padding: 0 0.75rem;
            }
        }

        html {
            scroll-behavior: smooth;
        }

        .item-price {
            background: #ecfdf5;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            display: inline-block;
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
            <div>
                <a href="sale.php" style="margin-right: 1rem; color: var(--dark); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Shop
                </a>
                <?php if (isset($_SESSION['id'])): ?>
                    <a href="logout.php" style="color: #ef4444; text-decoration: none;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: var(--primary); text-decoration: none;">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="cart-container">
            <div class="cart-items">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Your cart is empty</h2>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <a href="sale.php" class="continue-shopping">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="item-image">
                            
                            <div class="item-details">
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 'decrease')"
                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 'increase')"
                                            <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <button class="remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cart-summary">
                <h2 class="summary-title">Cart Summary</h2>
                <div class="summary-item">
                    <span>Total Items</span>
                    <span><?php echo $total_items; ?></span>
                </div>
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <?php if (!empty($cart_items)): ?>
                    <button onclick="window.location.href='place_order.php'" class="checkout-btn">
                        Proceed to Checkout
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function updateQuantity(productId, action) {
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update cart');
        });
    }

    function removeItem(productId) {
        if (confirm('Are you sure you want to remove this item?')) {
            fetch('remove_from_cart.php', {
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
                    alert(data.error || 'Failed to remove item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove item');
            });
        }
    }
    </script>
</body>
</html> 