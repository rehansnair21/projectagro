<?php
session_start();
require_once 'db_connection.php';

// Fetch user's photo if logged in
$user_photo = 'default-avatar.png'; // Default photo
if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $stmt = $conn->prepare("SELECT photo_url FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_photo = $row['photo_url'];
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroFresh - Modern Agricultural Marketplace</title>
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
            --accent: #f59e0b;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
        }

        body {
            background-color: var(--light);
            overflow-x: hidden;
        }
        cart-button {
            background: rgba(255, 255, 255, 0.9);
            padding: 0.9rem 2rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 1.1rem;
            position: relative;
            isolation: isolate;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: var(--secondary) !important;
            border: none;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 
                0 2px 10px rgba(14, 165, 233, 0.1),
                0 10px 30px rgba(14, 165, 233, 0.1),
                inset 0 0 0 2px rgba(255, 255, 255, 0.8);
        }

        .cart-button::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 100px;
            padding: 2px;
            background: linear-gradient(
                45deg,
                var(--secondary),
                var(--primary),
                var(--accent),
                var(--secondary)
            );
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            background-size: 300% 300%;
            animation: moveGradient 8s infinite linear;
            opacity: 0.7;
        }
        cart-button {
                position: fixed;
                top: 1.25rem;
                right: 6rem; /* Adjusted to make space for login button */
                display: block;}
                

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.25rem;
            position: fixed;
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
            gap: 3.5rem;
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

        .login-button {
    background: rgba(255, 255, 255, 0.9);
    padding: 0.9rem 2.2rem;
    border-radius: 100px;
    font-weight: 700;
    font-size: 1.1rem;
    position: relative;
    isolation: isolate;
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    text-decoration: none;
    color: var(--primary) !important;
    border: none;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 
        0 2px 10px rgba(34, 197, 94, 0.1),
        0 10px 30px rgba(14, 165, 233, 0.1),
        inset 0 0 0 2px rgba(255, 255, 255, 0.8);
}

/* Gradient background with animation */
.login-button::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 100px;
    padding: 2px;
    background: linear-gradient(
        45deg,
        var(--primary),
        var(--secondary),
        var(--accent),
        var(--primary)
    );
    -webkit-mask: 
        linear-gradient(#fff 0 0) content-box, 
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    background-size: 300% 300%;
    animation: moveGradient 8s infinite linear;
    opacity: 0.7;
}

/* Inner glow and background effect */
.login-button::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 100px;
    background: radial-gradient(
        circle at 50% -20%,
        rgba(255, 255, 255, 0.9),
        rgba(255, 255, 255, 0.5) 70%
    );
    opacity: 0;
    transition: opacity 0.4s ease;
}

/* Icon styles */
.login-button i {
    font-size: 1.25rem;
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Span wrapper for text */
.login-button span {
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
    transition: all 0.4s ease;
}

/* Hover effects */
.login-button:hover {
    transform: translateY(-3px) scale(1.02);
    background: white;
    box-shadow: 
        0 10px 40px rgba(34, 197, 94, 0.25),
        0 15px 50px rgba(14, 165, 233, 0.2),
        inset 0 0 0 2px rgba(255, 255, 255, 1);
}

.login-button:hover::before {
    opacity: 1;
    animation: moveGradient 4s infinite linear;
}

.login-button:hover::after {
    opacity: 1;
}

.login-button:hover i {
    transform: translateX(3px) rotate(5deg);
}

/* Active state */
.login-button:active {
    transform: translateY(-1px) scale(0.98);
    box-shadow: 
        0 5px 20px rgba(34, 197, 94, 0.2),
        0 8px 25px rgba(14, 165, 233, 0.15),
        inset 0 0 0 2px rgba(255, 255, 255, 0.9);
}
// ... existing code ...

/* Add this CSS after your existing styles */
.profile-button {
    position: fixed;
    top: 1.25rem;
    right: 2rem;
    background: rgba(255, 255, 255, 0.9);
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 
        0 2px 10px rgba(34, 197, 94, 0.1),
        0 10px 30px rgba(14, 165, 233, 0.1),
        inset 0 0 0 2px rgba(255, 255, 255, 0.8);
    z-index: 1000;
    position: relative;
    isolation: isolate;
}

.profile-button::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 50%;
    padding: 2px;
    background: linear-gradient(
        45deg,
        var(--primary),
        var(--secondary),
        var(--accent),
        var(--primary)
    );
    -webkit-mask: 
        linear-gradient(#fff 0 0) content-box, 
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    background-size: 300% 300%;
    animation: moveGradient 8s infinite linear;
    opacity: 0.7;
}

.profile-button i {
    font-size: 1.25rem;
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.profile-button:hover {
    transform: translateY(-3px) scale(1.1);
    background: white;
    box-shadow: 
        0 10px 40px rgba(34, 197, 94, 0.25),
        0 15px 50px rgba(14, 165, 233, 0.2),
        inset 0 0 0 2px rgba(255, 255, 255, 1);
}

.profile-button:hover::before {
    opacity: 1;
    animation: moveGradient 4s infinite linear;
}

.profile-button:hover i {
    transform: scale(1.1);
}

.profile-button:active {
    transform: translateY(-1px) scale(0.95);
}

/* Update media queries for responsive design */
@media (max-width: 768px) {
    .profile-button {
        top: 1rem;
        right: 1.5rem;
        width: 40px;
        height: 40px;
    }
}


/* Gradient movement animation */
@keyframes moveGradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

/* Responsive styles */
@media (max-width: 480px) {
    .login-button {
        padding: 0.75rem 1.8rem;
        font-size: 1rem;
    }
    
    .login-button i {
        font-size: 1.1rem;
    }
}

        .hero {
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(34, 197, 94, 0.1), transparent),
                        radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.1), transparent);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text {
            animation: slideIn 1s ease-out;
        }

        .hero-text h1 {
            font-size: 4.5rem;
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .cta-button {
            background: var(--gradient);
            color: white;
            padding: 1.25rem 2.5rem;
            border-radius: 100px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.2);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.3);
        }

        .features {
            padding: 8rem 2rem;
            background: white;
        }

        .features-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            background: white;
            border-radius: 2rem;
            padding: 3rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover::before {
            opacity: 0.05;
        }

        .feature-card * {
            position: relative;
            z-index: 1;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .feature-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.6;
        }

        #categories {
            padding: 8rem 2rem;
            background: var(--light);
        }

        .section-header {
            max-width: 800px;
            margin: 0 auto 5rem;
            text-align: center;
        }

        .section-header h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-header p {
            color: #64748b;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .category-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 2rem;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            transform: rotate(45deg);
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: rotate(0deg);
        }

        .category-icon i {
            font-size: 2rem;
            color: white;
            transform: rotate(-45deg);
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-icon i {
            transform: rotate(0deg);
        }

        .category-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .category-card p {
            color: #64748b;
            line-height: 1.6;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 3.5rem;
            }

            .hero-image {
                display: none;
            }

            .nav-links {
                display: none;
            }

            .login-button {
                position: fixed;
                top: 1.25rem;
                right: 2rem;
                display: block;
            }
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2.5rem;
            }

            .section-header h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-button {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Updated user profile styles */
        .user-profile {
            position: fixed;
            top: 1.25rem;
            right: 2rem;
            background: rgba(0, 0, 0, 0.9);
            padding: 0.6rem;
            border-radius: 100px;

            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--primary);
            box-shadow: 
                0 2px 10px rgba(34, 197, 94, 0.1),
                0 10px 30px rgba(14, 165, 233, 0.1);
            z-index: 1001;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-name {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: rgb(0, 0, 0);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            overflow: hidden;
        }

        .user-profile:hover .dropdown-content {
            display: block;
        }

        .dropdown-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: rgba(34, 197, 94, 0.1);
            color: var(--primary);
        }

        .dropdown-item i {
            font-size: 1.1rem;
            color: var(--primary);
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(0, 0, 0, 0.1);
            margin: 0.5rem 0;
        }

        /* Add debugging styles */
        .user-profile.visible {
            display: flex !important;
        }
        /* Style for Login Button */

/* Dropdown Container */


/* Dropdown Styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.5rem;
    padding-right: 1rem;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 100px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 
        0 2px 10px rgba(34, 197, 94, 0.1),
        0 10px 30px rgba(14, 165, 233, 0.1),
        inset 0 0 0 2px rgba(255, 255, 255, 0.8);
}

.dropbtn .profile-img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary);
    background: white;
}

.dropbtn span {
    color: var(--dark);
    font-weight: 600;
    font-size: 0.95rem;
}

.dropbtn i {
    color: var(--primary);
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.dropbtn:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 10px 40px rgba(34, 197, 94, 0.25),
        0 15px 50px rgba(14, 165, 233, 0.2),
        inset 0 0 0 2px rgba(255, 255, 255, 1);
}

.dropbtn:hover i {
    transform: rotate(180deg);
}

/* Dropdown Content */
.dropdown-content {
    display: none;
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    min-width: 220px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 0.5rem;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.dropdown-content.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.dropdown-content a {
    color: var(--dark);
    padding: 0.8rem 1.2rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    border-radius: 10px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dropdown-content a i {
    font-size: 1.1rem;
    color: var(--primary);
    transition: transform 0.2s ease;
}

.dropdown-content a:hover {
    background: rgba(34, 197, 94, 0.1);
    color: var(--primary);
}

.dropdown-content a:hover i {
    transform: translateX(3px);
}

/* Logout specific styling */
.dropdown-content a:last-child {
    color: #dc2626;
}

.dropdown-content a:last-child i {
    color: #dc2626;
}

.dropdown-content a:last-child:hover {
    background: rgba(220, 38, 38, 0.1);
}

@media (max-width: 768px) {
    .dropdown {
        top: 1rem;
        right: 1.5rem;
    }

    .dropbtn {
        padding: 0.4rem;
        padding-right: 0.8rem;
    }

    .dropbtn .profile-img {
        width: 32px;
        height: 32px;
    }

    .dropbtn span {
        font-size: 0.9rem;
    }
}
        
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">𝑨𝑮𝑹𝑶 𝑭𝑹𝑬𝑺𝑯</div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#categories">Products</a>
                
                <a href="contact.html">Contact</a>
                <!-- Move these outside the nav for better positioning -->
    <?php 
if (!isset($_SESSION['id'])) {
    echo '<a href="login.php" class="login-button" id="loginButton">';
    echo '  <i class="fas fa-user"></i>';
    echo '  <span>Login</span>';
    echo '</a>';
} else {
    echo '<div class="dropdown">';
    echo '  <button class="dropbtn">';
    echo '    <img src="' . htmlspecialchars($user_photo) . '" alt="Profile" class="profile-img">';
    echo '    <span>' . htmlspecialchars($_SESSION['name']) . '</span>';
    echo '    <i class="fas fa-chevron-down"></i>';
    echo '  </button>';
    echo '  <div class="dropdown-content">';
    echo '      <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>';
    echo '      <a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>';
    echo '      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>';
    echo '  </div>';
    echo '</div>';
}
?>


    <!-- Add a cart button
    <a href="cart.html" class="cart-button" id="cartButton">
        <i class="fas fa-shopping-cart"></i>
        <span>Cart</span>
    </a> -->
            </div>
        </nav>
    </header>

    

    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Transform Your Agriculture Business</h1>
                <p>Connect with global farmers, suppliers, and buyers through our innovative marketplace platform. Experience seamless trading with secure payments and smart logistics.</p>
                <button class="cta-button">Get Started Now</button>
            </div>
            <div class="hero-image">
                <img src="pexels-akilmazumder-1072824.jpg" alt="Agriculture marketplace">
            </div>
        </div>
    </section>

    <section class="features">
        <div class="section-header">
            <h2>Why Choose AgroFresh?</h2>
            <p>Experience the future of agricultural trade with our innovative features</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Direct Trading</h3>
                <p>Connect directly with verified suppliers and buyers worldwide, eliminating intermediaries and maximizing your profits.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Payments</h3>
                <p>Trade with confidence using our secure payment gateway and escrow service, ensuring safe transactions every time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Smart Logistics</h3>
                <p>Optimize your supply chain with our integrated logistics network and real-time tracking system.</p>
            </div>
        </div>
    </section>

    <section id="categories">
        <div class="section-header">
            <h2>Explore Categories</h2>
            <p>Find everything you need for your agricultural business</p>
        </div>
        <div class="category-grid">
            <a href="sale.php" style="text-decoration: none;">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>Fresh Produce</h3>
                    <p>Access high-quality fruits, vegetables, seeds,fresh milk and grains directly from certified farmers.</p>
                </div>
            </a>
        </div>
    </section>

    <!-- Add this before closing body tag -->
    <script>
        // Check if user is logged in on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, checking login status...');
            checkLoginStatus();
        });

        function checkLoginStatus() {
            console.log('Checking login status...');
            // Simulate checking login status (replace with actual API call)
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            const username = localStorage.getItem('username');
            
            if (isLoggedIn && username) {
                console.log('User is logged in, showing profile');
                showUserProfile(username);
            } else {
                console.log('User is not logged in');
                showLoginButton();
            }
        }

        function showUserProfile(username) {
            console.log('Showing profile for:', username);
            const loginButton = document.getElementById('loginButton');
            const userProfile = document.getElementById('userProfile');
            const userName = document.getElementById('userName');
            
            if (loginButton) {
                loginButton.style.display = 'none';
                console.log('Login button hidden');
            }
            
            if (userProfile && userName) {
                userName.textContent = username;
                userProfile.style.display = 'flex';
                userProfile.classList.add('visible');
                console.log('Profile shown with username:', username);
            } else {
                console.error('Missing elements:', {userProfile, userName});
            }
        }

        function showLoginButton() {
            console.log('Showing login button');
            const loginButton = document.getElementById('loginButton');
            const userProfile = document.getElementById('userProfile');
            
            if (loginButton) {
                loginButton.style.display = 'flex';
            }
            if (userProfile) {
                userProfile.style.display = 'none';
                userProfile.classList.remove('visible');
            }
        }

        function handleLogout(event) {
            event.preventDefault();
            // Clear login state
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('username');
            // Redirect to guest page
            window.location.href = 'guest.php';
        }
        document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown buttons
    const dropbtns = document.querySelectorAll('.dropbtn');
    
    // Add click event listener to each button
    dropbtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Get the dropdown content
            const dropdownContent = this.nextElementSibling;
            
            // Toggle active class
            dropdownContent.classList.toggle('active');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(event) {
                if (!event.target.matches('.dropbtn') && !dropdownContent.contains(event.target)) {
                    dropdownContent.classList.remove('active');
                    document.removeEventListener('click', closeDropdown);
                }
            });
            
            // Prevent event from bubbling up
            e.stopPropagation();
        });
    });

            // Handle CTA button click
            const ctaButton = document.querySelector('.cta-button');
            if (ctaButton) {
                ctaButton.addEventListener('click', function() {
                    <?php if (!isset($_SESSION['id'])) { ?>
                        window.location.href = 'login.php';
                    <?php } else { ?>
                        window.location.href = 'sale.php';
                    <?php } ?>
                });
            }
        });
    </script>
</body>
</html>