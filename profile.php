<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);

    // Validate inputs
    $errors = [];
    if (empty($full_name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match("/^[6-9]\d{9}$/", $mobile)) $errors[] = "Invalid mobile number";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (!preg_match("/^[1-9][0-9]{5}$/", $pincode)) $errors[] = "Invalid pincode";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, mobile=?, address=?, city=?, state=?, pincode=? WHERE id=?");
        $stmt->bind_param("sssssssi", $full_name, $email, $mobile, $address, $city, $state, $pincode, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $_SESSION['name'] = $full_name; // Update session name
        } else {
            $error_message = "Error updating profile: " . $conn->error;
                        }
                    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch user details including role
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

function getProfileImage($photo_url) {
    $default_avatar = 'assets/images/default-avatar.png';
    if (empty($photo_url) || $photo_url === 'default-avatar.png') {
        return $default_avatar;
    }
    $full_path = __DIR__ . '/' . $photo_url;
    return file_exists($full_path) ? $photo_url : $default_avatar;
}

// Get role-specific information
$role_info = [];
if ($user['role'] === 'seller') {
    $seller_stmt = $conn->prepare("SELECT * FROM sellerdetails WHERE id = ?");
    $seller_stmt->bind_param("i", $user_id);
    $seller_stmt->execute();
    $role_info = $seller_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - AgroFresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --background-color: #f4f4f4;
            --text-color: #333;
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .profile-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .profile-image {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            margin: 0 auto 20px;
            overflow: hidden;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 16px;
            opacity: 0.9;
        }

        .profile-details {
            padding: 30px;
        }

        .detail-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .detail-group:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #333;
            font-size: 16px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .back-button:hover {
            opacity: 0.9;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }

        .role-user {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-seller {
            background: #fce4ec;
            color: #c2185b;
        }

        .role-delivery {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 10px;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-image {
                width: 120px;
                height: 120px;
            }

            .profile-name {
                font-size: 20px;
            }
        }

        .edit-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .edit-button:hover {
            background: var(--secondary-color);
            color: white;
        }

        .detail-value input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            display: none;
        }

        .detail-value.editing input {
            display: block;
        }

        .detail-value.editing .text {
            display: none;
        }

        .save-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
            width: 100%;
            display: none;
        }

        .save-button.show {
            display: block;
        }

        .save-button:hover {
            background: var(--secondary-color);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Add these new styles for validation */
        .detail-value input.invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .validation-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            font-style: italic;
        }

        .detail-value {
            position: relative;
        }

        /* Style for valid input state */
        .detail-value input:valid:not(.invalid) {
            border-color: #28a745;
            background-color: #f8fff8;
        }

        /* Improve input focus state */
        .detail-value input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25);
        }

        /* Style for input hover */
        .detail-value input:hover {
            border-color: var(--primary-color);
        }

        /* Transition for smooth validation feedback */
        .detail-value input {
            transition: all 0.3s ease;
        }

        .photo-upload-label {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .photo-upload-label:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <form method="POST" action="">
                <div class="profile-header">
                    <a href="<?php echo $user['role'] === 'delivery' ? 'delivery_dashboard.php' : 'template.php'; ?>" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="button" class="edit-button" onclick="toggleEdit()">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <div class="profile-image">
                        <img src="<?php echo getProfileImage($user['photo_url']); ?>" alt="Profile Picture" id="profile-preview">
                        <label for="photo-upload" class="photo-upload-label">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="photo-upload" accept="image/*" style="display: none;">
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <div class="role-badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-group">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['email']); ?></span>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                </div>

                    <div class="detail-group">
                        <div class="detail-label">Mobile Number</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['mobile']); ?></span>
                            <input type="tel" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                        </div>
                </div>

                    <div class="detail-group">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['address']); ?></span>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>
                </div>

                    <div class="detail-group">
                        <div class="detail-label">City</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['city'] ?? 'Not specified'); ?></span>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                        </div>
                </div>

                    <div class="detail-group">
                        <div class="detail-label">State</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['state']); ?></span>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required>
                        </div>
                </div>

                    <div class="detail-group">
                        <div class="detail-label">PIN Code</div>
                        <div class="detail-value">
                            <span class="text"><?php echo htmlspecialchars($user['pincode']); ?></span>
                            <input type="text" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" required>
                        </div>
                </div>

                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        function toggleEdit() {
            const detailValues = document.querySelectorAll('.detail-value');
            const saveButton = document.querySelector('.save-button');
            const editButton = document.querySelector('.edit-button');
            
            detailValues.forEach(value => {
                value.classList.toggle('editing');
            });
            
            saveButton.classList.toggle('show');
            editButton.textContent = editButton.textContent.includes('Edit') ? 'Cancel' : 'Edit';
        }

        // Add these new validation functions
        const validations = {
            full_name: {
                regex: /^[a-zA-Z\s]{2,50}$/,
                message: 'Name should contain only letters and spaces (2-50 characters)'
            },
            email: {
                regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: 'Please enter a valid email address'
            },
            mobile: {
                regex: /^[6-9]\d{9}$/,
                message: 'Mobile number should start with 6-9 and have 10 digits'
            },
            address: {
                regex: /^.{5,100}$/,
                message: 'Address should be between 5 and 100 characters'
            },
            city: {
                regex: /^[a-zA-Z\s]{2,50}$/,
                message: 'City should contain only letters and spaces'
            },
            state: {
                regex: /^[a-zA-Z\s]{2,50}$/,
                message: 'State should contain only letters and spaces'
            },
            pincode: {
                regex: /^[1-9][0-9]{5}$/,
                message: 'PIN code should be 6 digits and not start with 0'
            }
        };

        function validateField(input) {
            const field = input.name;
            const value = input.value.trim();
            const validation = validations[field];
            const errorElement = input.parentElement.querySelector('.validation-error');
            
            // Remove existing error element if it exists
            if (errorElement) {
                errorElement.remove();
            }
            
            // Check if field is empty
            if (!value) {
                showError(input, `${field.replace('_', ' ')} is required`);
                return false;
            }

            // Check against regex pattern
            if (!validation.regex.test(value)) {
                showError(input, validation.message);
                return false;
            }
            
                return true;
        }

        function showError(input, message) {
            // Remove any existing error message
            const existingError = input.parentElement.querySelector('.validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Create and add new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'validation-error';
            errorDiv.textContent = message;
            input.parentElement.appendChild(errorDiv);
            input.classList.add('invalid');
        }

        function removeError(input) {
            const errorElement = input.parentElement.querySelector('.validation-error');
            if (errorElement) {
                errorElement.remove();
            }
            input.classList.remove('invalid');
        }

        // Add validation listeners to all inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Add validation to all form inputs
            const inputs = document.querySelectorAll('input[name]');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateField(this);
                });
                
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
            
            // Form submission validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate all fields
                inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Auto-hide messages after 3 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            });
        });

        document.getElementById('photo-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert('File size should not exceed 5MB');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only JPG, JPEG or PNG files');
                    return;
                }

                const formData = new FormData();
                formData.append('photo', file);

                // Show loading overlay
                document.querySelector('.loading-overlay').style.display = 'flex';

                fetch('update_profile_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update profile photo
                        document.getElementById('profile-preview').src = data.photo_url + '?v=' + new Date().getTime();
                        showNotification('Profile photo updated successfully');
                    } else {
                        showNotification(data.error || 'Failed to update profile photo', true);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to update profile photo', true);
                })
                .finally(() => {
                    // Hide loading overlay
                    document.querySelector('.loading-overlay').style.display = 'none';
                });
            }
        });

        function showNotification(message, isError = false) {
            const notification = document.createElement('div');
            notification.className = `message ${isError ? 'error' : 'success'}`;
            notification.textContent = message;
            
            // Remove existing messages
            document.querySelectorAll('.message').forEach(msg => msg.remove());
            
            // Add new message at the top of the container
            const container = document.querySelector('.container');
            container.insertBefore(notification, container.firstChild);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>