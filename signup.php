<?php
    ob_start();
    session_start();
    require_once 'db_connection.php';  
    $errors = [];
    $data = [];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        function validate($field, $value, $pattern, $errorMessage, $sanitize = true) {
            global $errors, $data;
            $value = trim($value);
            if (!preg_match($pattern, $value)) {
                $errors[$field] = $errorMessage;
            } else {
                $data[$field] = $sanitize ? htmlspecialchars($value) : $value;
            }
        }

        // Enhanced validation patterns
        validate(
            "name", 
            $_POST["name"], 
            "/^[a-zA-Z ]{3,}$/", 
            "Name must be at least 3 characters and contain only letters and spaces."
        );
        
        validate(
            "email", 
            $_POST["email"], 
            "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", 
            "Please enter a valid email address."
        );
        
        validate(
            "mobile", 
            $_POST["mobile"], 
            "/^[6-9]\d{9}$/", 
            "Please enter a valid 10-digit mobile number starting with 6-9."
        );
        
        if (strlen($_POST["address"]) < 5) {
            $errors["address"] = "Address must be at least 5 characters long.";
        } else {
            validate(
                "address", 
                $_POST["address"], 
                "/^[a-zA-Z0-9 .,-]+$/", 
                "Address can only contain letters, numbers, spaces, commas, periods, or hyphens."
            );
        }
        
        validate(
            "state", 
            $_POST["state"], 
            "/^[a-zA-Z ]+$/", 
            "State must only contain letters and spaces."
        );
        
        validate(
            "pincode", 
            $_POST["pincode"], 
            "/^[1-9][0-9]{5}$/", 
            "Please enter a valid 6-digit PIN code starting with 1-9."
        );

        $password = trim($_POST["password"]);
        $confirmPassword = trim($_POST["confirmPassword"]);

        // Enhanced password validation
        if (strlen($password) < 8) {
            $errors["password"] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors["password"] = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors["password"] = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors["password"] = "Password must contain at least one number.";
        } elseif (!preg_match('/[@$!%*?&]/', $password)) {
            $errors["password"] = "Password must contain at least one special character (@$!%*?&).";
        } elseif ($password !== $confirmPassword) {
            $errors["confirmPassword"] = "Passwords do not match.";
        }

        if (!in_array($_POST["role"], ["user", "seller", "delivery"])) {
            $errors["role"] = "Please select a valid role.";
        } else {
            $data["role"] = $_POST["role"];
        }

        if (empty($errors)) {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $data["email"]);
            $check_email->execute();
            $result = $check_email->get_result();
            
            if ($result->num_rows > 0) {
                $errors["email"] = "This email is already registered.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into users table
                    $sql = "INSERT INTO users (full_name, email, mobile, address, state, pincode, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "ssssssss",
                        $data["name"],
                        $data["email"],
                        $data["mobile"],
                        $data["address"],
                        $data["state"],
                        $data["pincode"],
                        $hashedPassword,
                        $data["role"]
                    );

                    if ($stmt->execute()) {
                        $user_id = $conn->insert_id;
                        
                        // If role is seller, insert into sellerdetails table
                        if ($data["role"] === "seller") {
                            $seller_sql = "INSERT INTO sellerdetails (id, full_name, email, mobile, address, state, pincode, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $seller_stmt = $conn->prepare($seller_sql);
                            $seller_stmt->bind_param(
                                "isssssss",
                                $user_id,
                                $data["name"],
                                $data["email"],
                                $data["mobile"],
                                $data["address"],
                                $data["state"],
                                $data["pincode"],
                                $hashedPassword
                            );
                            $seller_stmt->execute();
                            $seller_stmt->close();
                        }

                        $conn->commit();
                        $_SESSION['registration_success'] = "Account created successfully! Please login.";
                        header('Location: login.php');
                        exit();
                    } else {
                        throw new Exception("Error creating account");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors["general"] = "Error creating account: " . $e->getMessage();
                }
                
                $stmt->close();
            }
            $check_email->close();
        }
    } 
    ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agro Fresh - Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(67, 206, 162, 0.1), rgba(24, 90, 157, 0.1)),
                        url('https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?crop=entropy&cs=tinysrgb&fm=jpg&ixid=MnwzMjM4NDZ8MHwxfHJhbmRvbXx8fHx8fHx8fDE2MjY4NjQ0NTE&ixlib=rb-1.2.1&q=80') center/cover no-repeat fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.98);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.1),
                0 1px 8px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.15),
                0 1px 10px rgba(0, 0, 0, 0.1);
        }

        .title {
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #43cea2 20%, #185a9d 80%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 1rem;
            appearance: none;
            background-color: white;
            transition: all 0.3s ease;
            color: #333;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus + i,
        .form-group select:focus + i {
            color: #43cea2;
            transform: translateY(-50%) scale(1.1);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #43cea2;
            box-shadow: 0 0 0 4px rgba(67, 206, 162, 0.1);
            transform: translateY(-1px);
        }

        button {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 206, 162, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                transparent,
                rgba(255, 255, 255, 0.1),
                transparent
            );
            transform: rotate(45deg);
            transition: 0.5s;
            opacity: 0;
        }

        button:hover::after {
            opacity: 1;
            left: 100%;
        }

        .login-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 1rem;
            color: #666;
        }

        .login-link a {
            color: #185a9d;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: color 0.3s ease;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .login-link a:hover {
            color: #43cea2;
        }

        .login-link a:hover::after {
            transform: scaleX(1);
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            .container {
                padding: 2rem;
                margin: 1rem;
                border-radius: 15px;
            }

            .title {
                font-size: 2rem;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .form-group.animated input,
        .form-group.animated select {
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-message {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .form-group select {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 1rem;
            appearance: none;
            background-color: white;
            transition: all 0.3s ease;
            color: #333;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23888' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 15px) center;
            padding-right: 40px;
        }

        .form-group select:focus {
            outline: none;
            border-color: #43cea2;
            box-shadow: 0 0 0 4px rgba(67, 206, 162, 0.1);
        }

        .form-group select option {
            padding: 10px;
        }
    </style>
</head>
<body>
   
    <div class="container">
        <h1 class="title">Agro Fresh</h1>
        <p class="subtitle">Join our community of farmers and buyers</p>

        <form method="POST" action="" id="signupForm">
            <div class="form-group animated">
                <input type="text" name="name" id="name" placeholder="Full Name" required>
                <i class="fas fa-user"></i>
                <div class="error-message" style="display: <?= isset($errors["name"]) ? 'block' : 'none' ?>"><?= $errors["name"] ?? "" ?></div>
            </div>

            <div class="form-group animated">
                <input type="email" name="email" id="email" placeholder="Email Address" required>
                <i class="fas fa-envelope"></i>
                <div class="error-message" style="display: <?= isset($errors["email"]) ? 'block' : 'none' ?>"><?= $errors["email"] ?? "" ?></div>
            </div>

            <div class="form-group animated">
                <input type="tel" name="mobile" id="mobile" placeholder="Mobile Number" required>
                <i class="fas fa-phone"></i>
                <div class="error-message" style="display: <?= isset($errors["mobile"]) ? 'block' : 'none' ?>"><?= $errors["mobile"] ?? "" ?></div>
            </div>

            <div class="form-group animated">
                <input type="text" name="address" id="address" placeholder="Home Address" required>
                <i class="fas fa-home"></i>
                <div class="error-message" style="display: <?= isset($errors["address"]) ? 'block' : 'none' ?>"><?= $errors["address"] ?? "" ?></div>
            </div>

            <div class="row">
                <div class="form-group animated">
                    <select name="state" id="state" required>
                        <option value="">Select State</option>
                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                        <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                        <option value="Assam">Assam</option>
                        <option value="Bihar">Bihar</option>
                        <option value="Chhattisgarh">Chhattisgarh</option>
                        <option value="Goa">Goa</option>
                        <option value="Gujarat">Gujarat</option>
                        <option value="Haryana">Haryana</option>
                        <option value="Himachal Pradesh">Himachal Pradesh</option>
                        <option value="Jharkhand">Jharkhand</option>
                        <option value="Karnataka">Karnataka</option>
                        <option value="Kerala">Kerala</option>
                        <option value="Madhya Pradesh">Madhya Pradesh</option>
                        <option value="Maharashtra">Maharashtra</option>
                        <option value="Manipur">Manipur</option>
                        <option value="Meghalaya">Meghalaya</option>
                        <option value="Mizoram">Mizoram</option>
                        <option value="Nagaland">Nagaland</option>
                        <option value="Odisha">Odisha</option>
                        <option value="Punjab">Punjab</option>
                        <option value="Rajasthan">Rajasthan</option>
                        <option value="Sikkim">Sikkim</option>
                        <option value="Tamil Nadu">Tamil Nadu</option>
                        <option value="Telangana">Telangana</option>
                        <option value="Tripura">Tripura</option>
                        <option value="Uttar Pradesh">Uttar Pradesh</option>
                        <option value="Uttarakhand">Uttarakhand</option>
                        <option value="West Bengal">West Bengal</option>
                        <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                        <option value="Chandigarh">Chandigarh</option>
                        <option value="Dadra and Nagar Haveli">Dadra and Nagar Haveli</option>
                        <option value="Daman and Diu">Daman and Diu</option>
                        <option value="Delhi">Delhi</option>
                        <option value="Lakshadweep">Lakshadweep</option>
                        <option value="Puducherry">Puducherry</option>
                    </select>
                    <i class="fas fa-map"></i>
                    <i class="fas fa-chevron-down"></i>
                    <div class="error-message" style="display: <?= isset($errors["state"]) ? 'block' : 'none' ?>"><?= $errors["state"] ?? "" ?></div>
                </div>

                <div class="form-group animated">
                    <input type="text" name="pincode" id="pincode" placeholder="PIN Code" required>
                    <i class="fas fa-map-pin"></i>
                    <div class="error-message" style="display: <?= isset($errors["pincode"]) ? 'block' : 'none' ?>"><?= $errors["pincode"] ?? "" ?></div>
                </div>
            </div>

            <div class="form-group animated">
                <select name="role" id="role" required>
                    <option value="">Select Role</option>
                    <option value="user">Customer</option>
                    <option value="seller">Seller</option>
                    <option value="delivery">Delivery Personnel</option>
                </select>
                <i class="fas fa-user-tag"></i>
                <div class="error-message" style="display: <?= isset($errors["role"]) ? 'block' : 'none' ?>"><?= $errors["role"] ?? "" ?></div>
            </div>

            <div class="form-group animated">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-lock"></i>
                <div class="error-message" style="display: <?= isset($errors["password"]) ? 'block' : 'none' ?>"><?= $errors["password"] ?? "" ?></div>
            </div>

            <div class="form-group animated">
                <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirm Password" required>
                <i class="fas fa-lock"></i>
                <div class="error-message" style="display: <?= isset($errors["confirmPassword"]) ? 'block' : 'none' ?>"><?= $errors["confirmPassword"] ?? "" ?></div>
            </div>

            <?php if (isset($errors["general"])): ?>
            <div class="error-message" style="display: block; margin-bottom: 1rem;"><?= $errors["general"] ?></div>
            <?php endif; ?>

            <button type="submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const inputs = form.querySelectorAll('input, select');
            const debounceTimeout = 300; // ms delay for validation
            let timeouts = {};

            // Add input event listeners for live validation
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Clear any existing timeout for this input
                    if (timeouts[input.name]) {
                        clearTimeout(timeouts[input.name]);
                    }

                    // Set new timeout for debounced validation
                    timeouts[input.name] = setTimeout(() => {
                        validateInputLive(input);
                    }, debounceTimeout);
                });

                // Add blur event for immediate validation
                input.addEventListener('blur', function() {
                    if (timeouts[input.name]) {
                        clearTimeout(timeouts[input.name]);
                    }
                    validateInputLive(input);
                });
            });

            // Add special handling for password confirmation
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            
            passwordInput.addEventListener('input', function() {
                if (timeouts['password']) {
                    clearTimeout(timeouts['password']);
                }
                timeouts['password'] = setTimeout(() => {
                    validateInputLive(this);
                    if (confirmPasswordInput.value) {
                        validateInputLive(confirmPasswordInput);
                    }
                }, debounceTimeout);
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Always prevent default submission first
                
                let isValid = true;
                let firstError = null;

                // Clear all previous error styles first
                inputs.forEach(input => {
                    const errorElement = input.parentElement.querySelector('.error-message');
                    errorElement.classList.remove('show');
                    input.classList.remove('valid', 'invalid');
                });

                // Validate each field in sequence
                for (const input of inputs) {
                    const isFieldValid = validateInputLive(input);
                    if (!isFieldValid && !firstError) {
                        firstError = input;
                        isValid = false;
                    }
                }

                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (isValid) {
                    this.submit();
                }
            });
        });

        function validateInputLive(input) {
            const errorElement = input.parentElement.querySelector('.error-message');
            let isValid = true;
            let errorMessage = '';

            // Remove previous validation classes
            input.classList.remove('valid', 'invalid');

            // If the field is empty, don't show any validation messages
            if (!input.value.trim()) {
                errorElement.style.display = 'none';
                return true;
            }

            switch(input.name) {
                case 'name':
                    isValid = /^[a-zA-Z ]{3,}$/.test(input.value);
                    errorMessage = 'Name must be at least 3 characters and contain only letters';
                    break;
                case 'email':
                    isValid = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(input.value);
                    errorMessage = 'Please enter a valid email address';
                    break;
                case 'mobile':
                    isValid = /^[6-9]\d{9}$/.test(input.value);
                    errorMessage = 'Please enter a valid 10-digit mobile number starting with 6-9';
                    break;
                case 'pincode':
                    isValid = /^[1-9][0-9]{5}$/.test(input.value);
                    errorMessage = 'Please enter a valid 6-digit PIN code';
                    break;
                case 'address':
                    isValid = input.value.length >= 5;
                    errorMessage = 'Address must be at least 5 characters long';
                    break;
                case 'state':
                    isValid = input.value !== '';
                    errorMessage = 'Please select a state';
                    break;
                case 'password':
                    isValid = validatePasswordRules(input.value);
                    errorMessage = getPasswordErrorMessage(input.value);
                    break;
                case 'confirmPassword':
                    const password = document.getElementById('password').value;
                    isValid = input.value === password && input.value !== '';
                    errorMessage = 'Passwords do not match';
                    break;
                case 'role':
                    isValid = ['user', 'seller', 'delivery'].includes(input.value);
                    errorMessage = 'Please select a valid role';
                    break;
            }

            // Show error message only if the field has a non-empty value and is invalid
            if (!isValid && input.value.trim()) {
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
                input.classList.add('invalid');
            } else {
                errorElement.style.display = 'none';
                if (input.value.trim()) {
                    input.classList.add('valid');
                }
            }

            return isValid;
        }

        function validatePasswordRules(password) {
            return password.length >= 8 && 
                   /[A-Z]/.test(password) && 
                   /[a-z]/.test(password) && 
                   /[0-9]/.test(password) && 
                   /[@$!%*?&]/.test(password);
        }

        function getPasswordErrorMessage(password) {
            if (password.length < 8) return 'Password must be at least 8 characters long';
            if (!/[A-Z]/.test(password)) return 'Password must contain at least one uppercase letter';
            if (!/[a-z]/.test(password)) return 'Password must contain at least one lowercase letter';
            if (!/[0-9]/.test(password)) return 'Password must contain at least one number';
            if (!/[@$!%*?&]/.test(password)) return 'Password must contain at least one special character (@$!%*?&)';
            return '';
        }
    </script>
</body>
</html>