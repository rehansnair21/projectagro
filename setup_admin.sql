-- Add role column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user';

-- Add is_admin column if it doesn't exist (for backward compatibility)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0;

-- Create admin user if not exists
INSERT INTO users (full_name, email, password, role, is_admin) 
VALUES (
    'Admin User', 
    'admin@agrofresh.com', 
    '$2y$10$YourSalt123YourSalt1u0K1HkzYOZ9qQ9P6RzJ.tWY5hP8vQX6W',
    'admin',
    1
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    is_admin = 1;
