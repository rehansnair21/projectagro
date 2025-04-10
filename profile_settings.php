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
    <title>Profile Settings - AgroFresh</title>
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

        /* Include the sidebar styles from previous pages */

        .profile-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .profile-form {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: bold;
            color: var(--text-color);
        }

        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 20px;
            }
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-online {
            background-color: #28a745;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 12px;
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        .menu-item:hover {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .menu-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: fixed;
            left: 20px;
            top: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--secondary-color);
        }

        /* Input Focus Styles */
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        /* Button Hover Effects */
        .btn:hover {
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar-toggle {
                display: flex;
            }

            .page-header h1 {
                font-size: 1.5rem;
                margin-left: 50px;
            }

            .profile-container {
                margin: 20px;
                padding: 20px;
            }

            .form-group input {
                font-size: 16px; /* Better for mobile input */
            }

            .btn {
                width: 100%;
                padding: 15px;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Form Validation Styles */
        .form-group input:invalid {
            border-color: #dc3545;
        }

        .form-group input:invalid:focus {
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }

        /* Alert Animation */
        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert {
            animation: slideIn 0.3s ease;
        }

        /* Print Styles */
        @media print {
            .sidebar,
            .sidebar-toggle,
            .btn {
                display: none;
            }

            .main-content {
                margin: 0;
                padding: 0;
            }

            .profile-container {
                box-shadow: none;
            }
        }

        /* Add these styles */
        .form-group input[readonly] {
            background-color: #f8f9fa;
            cursor: default;
            border: 1px solid #dee2e6;
        }

        .form-group input[readonly]:focus {
            outline: none;
            box-shadow: none;
            border-color: #dee2e6;
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
            <a href="earnings.php" class="menu-item">
                <i class="fas fa-rupee-sign"></i>
                Earnings
            </a>
            <a href="profile_settings.php" class="menu-item active">
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
                <h1><i class="fas fa-user"></i> Profile Settings</h1>
            </div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-picture-container">
                        <img src="<?php echo htmlspecialchars(getProfileImage($delivery_person['photo_url'])); ?>" 
                             alt="Profile Picture" 
                             class="profile-picture">
                    </div>
                    <h2><?php echo htmlspecialchars($delivery_person['full_name']); ?></h2>
                    <p>Delivery Personnel</p>
                </div>

                <form id="profile-form" class="profile-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               value="<?php echo htmlspecialchars($delivery_person['full_name']); ?>" 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($delivery_person['email']); ?>" 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" 
                               id="mobile" 
                               name="mobile" 
                               value="<?php echo htmlspecialchars($delivery_person['mobile']); ?>" 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="<?php echo htmlspecialchars($delivery_person['address']); ?>" 
                               readonly>
                    </div>
                </form>
            </div>
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