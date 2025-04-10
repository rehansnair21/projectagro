<?php
// Add debugging at the top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connection.php';


// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php?redirect=seller_register.php");
    exit();
}

$user_id = $_SESSION['id'];

// Create sellerdetails table if it doesn't exist
$create_seller_table = "CREATE TABLE IF NOT EXISTS sellerdetails (
    id INT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
)";

if (!$conn->query($create_seller_table)) {
    die("Error creating sellerdetails table: " . $conn->error);
}

// Check if user is already a seller
$check_seller = $conn->query("SELECT id FROM sellerdetails WHERE id = " . $conn->real_escape_string($user_id));

if (!$check_seller) {
    die("Error checking seller status: " . $conn->error);
}

if ($check_seller->num_rows > 0) {
    header("Location: seller_dashboard.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user details from users table
    $user_query = $conn->query("SELECT * FROM users WHERE id = " . $conn->real_escape_string($user_id));

    if (!$user_query) {
        die("Error fetching user details: " . $conn->error);
    }

    if ($user_query->num_rows > 0) {
        $user_data = $user_query->fetch_assoc();
        
        // Insert into sellerdetails table using prepared statement
        $insert_sql = "INSERT INTO sellerdetails (id, full_name, email, mobile, address, state, pincode, password) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_seller = $conn->prepare($insert_sql);
        
        if (!$insert_seller) {
            die("Error preparing seller insert: " . $conn->error);
        }
        
        $insert_seller->bind_param("isssssss", 
            $user_data['id'],
            $user_data['full_name'],
            $user_data['email'],
            $user_data['mobile'],
            $user_data['address'],
            $user_data['state'],
            $user_data['pincode'],
            $user_data['password']
        );

        if ($insert_seller->execute()) {
            // Create products table if it doesn't exist
            $create_products = "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                seller_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                stock INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (seller_id) REFERENCES sellerdetails(id)
                ON DELETE CASCADE ON UPDATE CASCADE
            )";
            
            if ($conn->query($create_products)) {
                header("Location: seller_dashboard.php");
                exit();
            } else {
                $error_message = "Error creating products table: " . $conn->error;
            }
        } else {
            $error_message = "Error registering as seller: " . $insert_seller->error;
        }
    } else {
        $error_message = "User not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
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
            background-color: var(--light);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .header p {
            color: #64748b;
            font-size: 1.2rem;
        }

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .success {
            background-color: #dcfce7;
            color: #166534;
        }

        .error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .seller-info {
            background: rgba(34, 197, 94, 0.1);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .seller-info h2 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-item label {
            display: block;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-item p {
            color: var(--dark);
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Become a Seller</h1>
            <p>Join AgroFresh as a seller and grow your business</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="seller-info">
            <h2>Your Information</h2>
            <?php
            $user_info = $conn->query("SELECT * FROM users WHERE id = " . $conn->real_escape_string($user_id));
            if ($user_info && $user_info->num_rows > 0) {
                $user = $user_info->fetch_assoc();
            ?>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="info-item">
                    <label>Mobile</label>
                    <p><?php echo htmlspecialchars($user['mobile']); ?></p>
                </div>
                <div class="info-item">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($user['address']); ?></p>
                </div>
                <div class="info-item">
                    <label>State</label>
                    <p><?php echo htmlspecialchars($user['state']); ?></p>
                </div>
                <div class="info-item">
                    <label>Pincode</label>
                    <p><?php echo htmlspecialchars($user['pincode']); ?></p>
                </div>
            </div>
            <?php } ?>
        </div>

        <form method="POST" action="">
            <p style="margin-bottom: 1rem; color: #64748b;">
                By clicking "Confirm & Register", you agree to our terms and conditions for sellers.
            </p>
            <button type="submit" class="submit-btn">Confirm & Register as Seller</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
