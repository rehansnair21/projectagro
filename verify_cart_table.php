<?php
require_once 'db_connection.php';

// Drop and recreate cart table with correct structure
$sql = "
DROP TABLE IF EXISTS cart;

CREATE TABLE cart (
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

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "Cart table structure updated successfully!\n";
} else {
    echo "Error updating cart table: " . $conn->error . "\n";
}

$conn->close();
?>
