-- Drop the existing cart table if it exists
DROP TABLE IF EXISTS cart;

-- Create the cart table with proper relationships
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
