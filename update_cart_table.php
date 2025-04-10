<?php
require_once 'db_connection.php';

// SQL to create/update cart table
$sql = "
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL COMMENT 'Can be either session_id for guests or user_id for logged-in users',
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    is_guest BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True for session-based carts, False for user-based carts',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Execute the SQL
if ($conn->query($sql)) {
    echo "Cart table created/updated successfully\n";
} else {
    echo "Error creating/updating cart table: " . $conn->error . "\n";
}

// Add is_guest column if it doesn't exist
$sql = "SHOW COLUMNS FROM cart LIKE 'is_guest'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE cart ADD COLUMN is_guest BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True for session-based carts, False for user-based carts'";
    if ($conn->query($sql)) {
        echo "Added is_guest column successfully\n";
    } else {
        echo "Error adding is_guest column: " . $conn->error . "\n";
    }
}

// Update user_id column to VARCHAR if needed
$sql = "SHOW COLUMNS FROM cart LIKE 'user_id'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row && strpos(strtolower($row['Type']), 'varchar') === false) {
    $sql = "ALTER TABLE cart MODIFY COLUMN user_id VARCHAR(255) NOT NULL COMMENT 'Can be either session_id for guests or user_id for logged-in users'";
    if ($conn->query($sql)) {
        echo "Updated user_id column type successfully\n";
    } else {
        echo "Error updating user_id column type: " . $conn->error . "\n";
    }
}

$conn->close();
?>
