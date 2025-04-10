-- Create seller table
CREATE TABLE IF NOT EXISTS `seller` (
    `seller_id` INT AUTO_INCREMENT PRIMARY KEY,
    `id` INT NOT NULL,
    `seller_name` VARCHAR(255) NOT NULL,
    `location` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create products table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image_url` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`seller_id`) REFERENCES `seller`(`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update existing products to have a default seller if any exist
UPDATE `products` SET `seller_id` = (SELECT `seller_id` FROM `seller` LIMIT 1) WHERE `seller_id` IS NULL;