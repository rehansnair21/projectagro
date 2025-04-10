<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your MySQL password
// $dbname = "signup_db";

// Connect to the database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
    echo "Connected successfully";
}

// Initialize error messages
$errors = [];

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input values
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate Full Name
    if (strlen($name) < 3) {
        $errors[] = "Full Name must be at least 3 characters long.";
    }

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Validate Mobile Number (Indian format: 10 digits)
    if (!preg_match("/^[6-9]\d{9}$/", $mobile)) {
        $errors[] = "Invalid mobile number.";
    }

    // Validate Address
    if (strlen($address) < 10) {
        $errors[] = "Address must be at least 10 characters long.";
    }

    // Validate State
    if (empty($state)) {
        $errors[] = "State cannot be empty.";
    }

    // Validate PIN Code (6 digits)
    if (!preg_match("/^\d{6}$/", $pincode)) {
        $errors[] = "Invalid PIN Code.";
    }

    // Validate Password
    if (strlen($password) < 8 || !preg_match("/[a-z]/", $password) || !preg_match("/[A-Z]/", $password) || !preg_match("/\d/", $password) || !preg_match("/[\W_]/", $password)) {
        $errors[] = "Password must be at least 8 characters long and include uppercase, lowercase, number, and a special character.";
    }

    // Validate Confirm Password
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, address, state, pincode, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $email, $mobile, $address, $state, $pincode, $hashedPassword);

        // Execute the statement
        if ($stmt->execute()) {
            echo "Registration successful!";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // Display validation errors
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }
}

// Close the database connection
$conn->close();
?>
