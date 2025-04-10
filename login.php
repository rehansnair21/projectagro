<?php
session_start();

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// If user is already logged in, redirect based on role
if (isset($_SESSION['id'])) {
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                $redirect = 'admin_dashboard.php';
                break;
            case 'delivery':
                $redirect = 'delivery_dashboard.php';
                break;
            default:
                $redirect = 'template.php';
        }
    } else {
        $redirect = 'template.php';
    }
    header("Location: $redirect");
    exit();
}

require_once 'session_config.php';
initSession();

// If user is already logged in, redirect based on role
if (isset($_SESSION['id']) && isSessionValid()) {
    $redirect = isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'template.php';
    header("Location: $redirect");
    exit();
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    require_once 'db_connection.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role, is_admin FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception("Execution error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['id'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Determine redirect based on role
                $redirect = 'template.php'; // default redirect
                if ($user['role'] === 'admin' || $user['is_admin'] == 1) {
                    $redirect = 'admin_dashboard.php';
                } elseif ($user['role'] === 'delivery') {
                    $redirect = 'delivery_dashboard.php';
                }
                
                echo json_encode([
                    'success' => true,
                    'redirect' => $redirect
                ]);
                exit();
            }
        }
        
        // If we get here, login failed
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password. Please try again.'
        ]);
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgroFresh</title>
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
            background-image: url('https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?crop=entropy&cs=tinysrgb&fm=jpg&ixid=MnwzMjM4NDZ8MHwxfHJhbmRvbXx8fHx8fHx8fDE2MjY4NjQ0NTE&ixlib=rb-1.2.1&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .container {
            background: rgba(255, 255, 255, 0.85);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        .title {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-wrapper {
            position: relative;
            flex: 1;
        }

        .form-group i.input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            transition: color 0.3s ease;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #43cea2;
            box-shadow: 0 0 0 4px rgba(67, 206, 162, 0.1);
        }

        .form-group input:focus + i.input-icon {
            color: #43cea2;
        }

        .action-button {
            padding: 0;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            width: 50px;
            height: 50px;
        }

        .action-button i {
            font-size: 1.1rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 206, 162, 0.3);
            background: linear-gradient(135deg, #185a9d, #43cea2);
        }

        .action-button:active {
            transform: translateY(0);
        }

        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 35px;
            height: 35px;
        }

        .show-password i {
            font-size: 1.1rem;
        }

        .show-password .tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            pointer-events: none;
        }

        .show-password .tooltip::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            border-left: 6px solid #333;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
        }

        .show-password:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%);
        }

        /* Adjust password input padding to accommodate the toggle button */
        .input-wrapper input[type="password"],
        .input-wrapper input[type="text"] {
            padding-right: 50px;
        }

        .home-button {
            position: fixed;
            top: 2rem;
            left: 2rem;
            padding: 1rem 2rem;
            background: white;
            color: #185a9d;
            text-decoration: none;
            border-radius: 100px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 206, 162, 0.3);
            background: linear-gradient(135deg, #185a9d, #43cea2);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-link, .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a, .signup-link a {
            color: #185a9d;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .login-link a:hover, .signup-link a:hover {
            color: #43cea2;
        }

        .error-message {
            color: #ff4747;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <a href="guest.php" class="home-button">
        <i class="fas fa-home"></i> Home
    </a>

    <div class="container">
        <div class="title">Login</div>

        <div id="error-message" class="error-message" style="display: none;">
            Invalid email or password. Please try again.
        </div>

        <form id="loginForm" method="post">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" placeholder="Email" name="email" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                <button type="button" class="action-button" title="Clear email">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="Password" required id="password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="show-password" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                        <span class="tooltip">Show password</span>
                    </button>
                </div>
                <button type="button" class="action-button" title="Generate password">
                    <i class="fas fa-key"></i>
                </button>
            </div>

            <button type="submit" class="login-button">Login</button>

            <div class="login-link">
                <a href="forgotpassword.php">Forgot Password?</a>
            </div>
            <div class="signup-link">
                <a href="signup.php">Don't have an account? Sign Up</a>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const tooltip = togglePassword.querySelector('.tooltip');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    const errorDiv = document.getElementById('error-message');
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>
