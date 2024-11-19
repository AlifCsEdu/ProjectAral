-- Insert Categories
INSERT INTO categories (name, description) VALUES
('Western Mains', 'Delicious Western main courses with Malaysian twist'),
('Burgers', 'Juicy burgers with unique Malaysian flavors'),
('Pasta', 'Italian pasta dishes with Asian fusion'),
('Side Dishes', 'Perfect companions to your main course'),
('Beverages', 'Refreshing drinks and beverages');

-- Insert Food Items - Western Mains
INSERT INTO food_items (category_id, name, description, price, is_available) VALUES
(1, 'Classic Chicken Chop', 'Grilled chicken served with black pepper sauce, coleslaw, and fries', 15.90, 1),
(1, 'Lamb Chop', 'Grilled lamb chops with mint sauce, mashed potatoes, and seasonal vegetables', 25.90, 1),
(1, 'Fish & Chips', 'Crispy battered fish fillet served with tartar sauce and fries', 17.90, 1),
(1, 'Grilled Chicken Black Pepper', 'Tender chicken breast with signature black pepper sauce and mixed vegetables', 16.90, 1),
(1, 'Mixed Grill Platter', 'Combination of chicken, lamb, and fish with three different sauces', 29.90, 1),
(1, 'Chicken Maryland', 'Crispy fried chicken served with banana fritter, corn, and fries', 18.90, 1),
(1, 'Sirloin Steak', 'Grilled sirloin steak with mushroom sauce and potato wedges', 28.90, 1);

-- Insert Food Items - Burgers
INSERT INTO food_items (category_id, name, description, price, is_available) VALUES
(2, 'Classic Chicken Burger', 'Grilled chicken patty with lettuce, tomato, and special sauce', 12.90, 1),
(2, 'Beef Burger Deluxe', 'Premium beef patty with cheese, caramelized onions, and BBQ sauce', 14.90, 1),
(2, 'Spicy Chicken Burger', 'Crispy chicken with spicy sauce and Asian slaw', 13.90, 1),
(2, 'Lamb Burger', 'Seasoned lamb patty with mint yogurt sauce and fresh vegetables', 15.90, 1);

-- Insert Food Items - Pasta
INSERT INTO food_items (category_id, name, description, price, is_available) VALUES
(3, 'Chicken Chop Pasta', 'Grilled chicken chop served with aglio olio pasta', 16.90, 1),
(3, 'Seafood Pasta', 'Mixed seafood in creamy white sauce', 18.90, 1),
(3, 'Black Pepper Chicken Pasta', 'Spaghetti with grilled chicken in black pepper sauce', 16.90, 1),
(3, 'Carbonara', 'Classic carbonara with chicken ham and mushrooms', 15.90, 1);

-- Insert Food Items - Side Dishes
INSERT INTO food_items (category_id, name, description, price, is_available) VALUES
(4, 'French Fries', 'Crispy golden fries with special seasoning', 5.90, 1),
(4, 'Coleslaw', 'Fresh cabbage and carrots in creamy dressing', 4.90, 1),
(4, 'Garlic Bread', 'Toasted bread with garlic butter', 4.90, 1),
(4, 'Mashed Potato', 'Creamy mashed potatoes with gravy', 5.90, 1),
(4, 'Onion Rings', 'Crispy battered onion rings', 6.90, 1);

-- Insert Food Items - Beverages
INSERT INTO food_items (category_id, name, description, price, is_available) VALUES
(5, 'Iced Lemon Tea', 'Refreshing homemade lemon tea', 4.90, 1),
(5, 'Fresh Lime Juice', 'Freshly squeezed lime juice', 4.90, 1),
(5, 'Milo Dinosaur', 'Malaysian favorite iced milo with milo powder', 6.90, 1),
(5, 'Soft Drinks', 'Choice of Coke, Sprite, or Fanta', 3.90, 1),
(5, 'Hot Coffee', 'Freshly brewed coffee', 4.90, 1),
(5, 'Hot Tea', 'Choice of English breakfast or green tea', 4.90, 1);
