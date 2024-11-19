-- Create database
CREATE DATABASE IF NOT EXISTS arals_food_ordering;
USE arals_food_ordering;

-- Drop existing tables in reverse order of dependencies
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS food_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food items table
CREATE TABLE IF NOT EXISTS food_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    delivery_address TEXT,
    contact_number VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    food_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (food_item_id) REFERENCES food_items(id)
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    food_item_id INT,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (food_item_id) REFERENCES food_items(id)
);

-- Insert Categories
INSERT INTO categories (name, description) VALUES
('Rice Meals', 'Delicious Filipino rice meals'),
('Noodles', 'Various noodle dishes from different cuisines'),
('Beverages', 'Refreshing drinks and beverages'),
('Snacks', 'Light meals and finger foods'),
('Desserts', 'Sweet treats and desserts');

-- Insert Food Items
-- Rice Meals
INSERT INTO food_items (category_id, name, description, price, image_path, is_available) VALUES
(1, 'Chicken Adobo', 'Classic Filipino adobo with tender chicken', 120.00, 'images/menu/chicken-adobo.jpg', TRUE),
(1, 'Pork Sisig', 'Sizzling chopped pork with egg', 150.00, 'images/menu/sisig.jpg', TRUE),
(1, 'Beef Tapa', 'Sweet and savory beef tapa with garlic rice', 130.00, 'images/menu/tapa.jpg', TRUE),
(1, 'Lechon Kawali', 'Crispy deep-fried pork belly', 140.00, 'images/menu/lechon-kawali.jpg', TRUE),
(1, 'Bangus Sisig', 'Healthy alternative sisig made with milkfish', 135.00, 'images/menu/bangus-sisig.jpg', TRUE);

-- Noodles
INSERT INTO food_items (category_id, name, description, price, image_path, is_available) VALUES
(2, 'Pancit Canton', 'Stir-fried noodles with vegetables and meat', 100.00, 'images/menu/pancit-canton.jpg', TRUE),
(2, 'Pancit Malabon', 'Seafood noodles with rich orange sauce', 120.00, 'images/menu/pancit-malabon.jpg', TRUE),
(2, 'Beef Mami', 'Hot noodle soup with tender beef', 110.00, 'images/menu/beef-mami.jpg', TRUE),
(2, 'Pancit Bihon', 'Light and healthy rice noodles', 95.00, 'images/menu/pancit-bihon.jpg', TRUE),
(2, 'Palabok', 'Rice noodles with shrimp sauce and toppings', 115.00, 'images/menu/palabok.jpg', TRUE);

-- Beverages
INSERT INTO food_items (category_id, name, description, price, image_path, is_available) VALUES
(3, 'Sago\'t Gulaman', 'Sweet drink with tapioca pearls and jelly', 45.00, 'images/menu/sagot-gulaman.jpg', TRUE),
(3, 'Buko Juice', 'Fresh young coconut juice', 50.00, 'images/menu/buko-juice.jpg', TRUE),
(3, 'Calamansi Juice', 'Refreshing citrus juice', 40.00, 'images/menu/calamansi-juice.jpg', TRUE),
(3, 'Mango Shake', 'Fresh mango smoothie', 65.00, 'images/menu/mango-shake.jpg', TRUE),
(3, 'Iced Tea', 'House-made iced tea', 35.00, 'images/menu/iced-tea.jpg', TRUE);

-- Snacks
INSERT INTO food_items (category_id, name, description, price, image_path, is_available) VALUES
(4, 'Chicken Wings', '6pcs crispy chicken wings', 180.00, 'images/menu/chicken-wings.jpg', TRUE),
(4, 'Calamares', 'Crispy fried squid rings', 160.00, 'images/menu/calamares.jpg', TRUE),
(4, 'Nachos', 'Tortilla chips with cheese and toppings', 140.00, 'images/menu/nachos.jpg', TRUE),
(4, 'Siomai', '6pcs steamed dumplings', 80.00, 'images/menu/siomai.jpg', TRUE),
(4, 'French Fries', 'Crispy potato fries', 90.00, 'images/menu/french-fries.jpg', TRUE);

-- Desserts
INSERT INTO food_items (category_id, name, description, price, image_path, is_available) VALUES
(5, 'Halo-Halo', 'Mixed sweets with shaved ice and ice cream', 95.00, 'images/menu/halo-halo.jpg', TRUE),
(5, 'Leche Flan', 'Creamy caramel custard', 70.00, 'images/menu/leche-flan.jpg', TRUE),
(5, 'Turon', 'Banana and jackfruit spring rolls', 50.00, 'images/menu/turon.jpg', TRUE),
(5, 'Buko Pandan', 'Young coconut and pandan jelly dessert', 75.00, 'images/menu/buko-pandan.jpg', TRUE),
(5, 'Ube Halaya', 'Purple yam jam', 65.00, 'images/menu/ube-halaya.jpg', TRUE);

-- Insert sample admin user
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$8WkSBvQg9Y1xVTvzJ7vkUe6OGi8Wd2IwqDO4mpC7UG7KOtZqF9KDi', 'admin@arals.com', 'System Administrator', 'admin');
-- Password is 'admin123'

-- Insert sample regular user
INSERT INTO users (username, password, email, full_name, address, phone, role) VALUES
('user', '$2y$10$8WkSBvQg9Y1xVTvzJ7vkUe6OGi8Wd2IwqDO4mpC7UG7KOtZqF9KDi', 'user@example.com', 'John Doe', '123 Sample St, Manila', '09123456789', 'user');
-- Password is 'admin123'
