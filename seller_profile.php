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
$stmt = $conn->prepare("
    SELECT u.*, s.*
    FROM users u
    JOIN sellerdetails s ON u.id = s.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    
    // Update user table
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name = ?, email = ?, mobile = ?, address = ?, state = ?, pincode = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $full_name, $email, $mobile, $address, $state, $pincode, $seller_id);
    
    if ($stmt->execute()) {
        // Also update sellerdetails table
        $stmt = $conn->prepare("
            UPDATE sellerdetails 
            SET full_name = ?, email = ?, mobile = ?, address = ?, state = ?, pincode = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $full_name, $email, $mobile, $address, $state, $pincode, $seller_id);
        $stmt->execute();
        
        $success_message = "Profile updated successfully!";
        
        // Update session data
        $_SESSION['name'] = $full_name;
        $_SESSION['email'] = $email;
    } else {
        $error_message = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Include the same CSS as seller_orders.php */
        /* Add these additional styles */
        .profile-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e2e8f0;
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .save-btn {
            grid-column: span 2;
            background: var(--gradient);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .save-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }

            .save-btn {
                grid-column: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Include the same header and sidebar as seller_orders.php -->
    <div class="container">
        <div class="dashboard-grid">
            <!-- Sidebar (same as seller_orders.php) -->
            
            <main class="profile-container">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($seller['photo_url'] ?? 'uploads/default-avatar.png'); ?>" 
                         alt="Profile" class="profile-avatar">
                    <div>
                        <h1><?php echo htmlspecialchars($seller['full_name']); ?></h1>
                        <p style="color: #64748b;">Seller Account</p>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($seller['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($seller['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" 
                               value="<?php echo htmlspecialchars($seller['mobile']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($seller['address']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" 
                               value="<?php echo htmlspecialchars($seller['state']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" 
                               value="<?php echo htmlspecialchars($seller['pincode']); ?>" required>
                    </div>

                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
            </main>
        </div>
    </div>
</body>
</html> 