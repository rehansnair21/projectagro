<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmfolio - Sign Up</title>
    <link rel="stylesheet" href="sign.css">
</head>
<body>
    <div class="home-button-container">
        <a href="http://localhost/mini%20project/home/html5up-dimension/index.html" class="home-button">Home</a>
    </div>

    <div class="left-container">
        <h1 class="title">Customer <br>Sign Up</h1>
        <?php
        include '../db_config.php'; // Include database configuration file
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

            // Validating form fields
            validate("name", $_POST["name"], "/^[a-zA-Z ]{3,}$/", "Name must be at least 3 characters and contain only letters and spaces.");
            validate("email", $_POST["email"], "/^[^\s@]+@[^\s@]+\.[^\s@]+$/", "Please enter a valid email address.");
            validate("mobile", $_POST["mobile"], "/^\d{10}$/", "Please enter a valid 10-digit mobile number.");
            validate("address", $_POST["address"], "/^[a-zA-Z0-9 .,-]+$/", "Address must not be empty and can only contain letters, numbers, spaces, commas, periods, or hyphens.");
            validate("state", $_POST["state"], "/^[a-zA-Z ]+$/", "State must only contain letters and spaces.");
            validate("pincode", $_POST["pincode"], "/^\d{6}$/", "Please enter a valid 6-digit PIN code.");

            $password = trim($_POST["password"]);
            $confirmPassword = trim($_POST["confirmPassword"]);

            // Validating password
            if (strlen($password) < 8) {
                $errors["password"] = "Password must be at least 8 characters long.";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors["password"] = "Password must contain at least one uppercase letter.";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $errors["password"] = "Password must contain at least one lowercase letter.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors["password"] = "Password must contain at least one number.";
            } elseif (!preg_match('/[@$!%*?&]/', $password)) {
                $errors["password"] = "Password must contain at least one special character.";
            } elseif ($password !== $confirmPassword) {
                $errors["confirmPassword"] = "Passwords do not match.";
            }

            // If no errors, insert data into the database
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Hash the password for security
                $sql = "INSERT INTO users (full_name, email, mobile, address, state, pincode, password) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssss",
                    $data["name"],
                    $data["email"],
                    $data["mobile"],
                    $data["address"],
                    $data["state"],
                    $data["pincode"],
                    $hashedPassword
                );

                if ($stmt->execute()) {
                    header('Location: http://localhost/mini%20project%20agro%20fresh/login.html'); // Redirect to login page
                    exit();
                } else {
                    echo "<div class='error'>Error: " . $stmt->error . "</div>";
                }
                $stmt->close();
            }
        }
        ?>

        <form id="signupForm" action="" method="POST">
            <div class="form-group">
                <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                <div class="error-message"><?= $errors["name"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                <div class="error-message"><?= $errors["email"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Mobile Number" required>
                <div class="error-message"><?= $errors["mobile"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" id="address" name="address" placeholder="Home Address" required>
                <div class="error-message"><?= $errors["address"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" id="state" name="state" placeholder="State" required>
                <div class="error-message"><?= $errors["state"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" id="pincode" name="pincode" placeholder="PIN Code" required>
                <div class="error-message"><?= $errors["pincode"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <div class="error-message"><?= $errors["password"] ?? "" ?></div>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                <div class="error-message"><?= $errors["confirmPassword"] ?? "" ?></div>
            </div>
            <button type="submit" class="submit-btn">Sign Up</button>
        </form>
    </div>
</body>
</html>
