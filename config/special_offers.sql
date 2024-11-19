-- Create special_offers table
CREATE TABLE IF NOT EXISTS special_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    discount_percentage DECIMAL(5,2),
    minimum_order DECIMAL(10,2),
    code VARCHAR(20) UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_new_user_only BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample offers
INSERT INTO special_offers (title, description, discount_percentage, minimum_order, code, start_date, end_date, is_new_user_only) VALUES
('New User Special', 'Get 20% off on your first order!', 20.00, 0.00, 'NEWUSER20', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), TRUE),
('Free Delivery', 'Free delivery on orders above ₱500', 100.00, 500.00, 'FREEDEL', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), FALSE),
('Weekend Special', '15% off on family meals', 15.00, 0.00, 'WEEKEND15', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), FALSE);
