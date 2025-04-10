-- Drop existing seller table if it exists
DROP TABLE IF EXISTS seller;

-- Create new seller table with user-like attributes
CREATE TABLE seller (
    seller_id INT PRIMARY KEY AUTO_INCREMENT,
    id INT UNIQUE,  -- This is the user_id from users table
    seller_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location TEXT NOT NULL,
    profile_image VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (id) REFERENCES users(id)
);
