CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellerdetails(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Insert sample products
INSERT INTO products (name, description, price, image_url, category, stock) VALUES
('Fresh Apples', 'Premium quality fresh apples, rich in nutrients and perfect for daily consumption.', 120.00, 'https://source.unsplash.com/random/300x200/?apple', 'fruits', 100),
('Sweet Oranges', 'Juicy and sweet oranges, packed with Vitamin C and natural goodness.', 80.00, 'https://source.unsplash.com/random/300x200/?orange', 'fruits', 150),
('Fresh Tomatoes', 'Farm-fresh tomatoes, perfect for salads and cooking.', 40.00, 'https://source.unsplash.com/random/300x200/?tomato', 'vegetables', 200),
('Fresh Potatoes', 'Premium quality potatoes, perfect for all your cooking needs.', 30.00, 'https://source.unsplash.com/random/300x200/?potato', 'vegetables', 300),
('Fresh Spinach', 'Fresh spinach, rich in iron and vitamins.', 60.00, 'https://source.unsplash.com/random/300x200/?spinach', 'vegetables', 100),
('Fresh Carrots', 'Fresh carrots, perfect for salads and cooking.', 50.00, 'https://source.unsplash.com/random/300x200/?carrot', 'vegetables', 250);
