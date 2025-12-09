
CREATE DATABASE IF NOT EXISTS clothyyy CHARACTER SET utf8mb4;
USE clothyyy;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  gender ENUM('men', 'women') DEFAULT NULL
);

-- Users and Admins
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed default admin (username: admin, password: admin123)
INSERT INTO admins (username, password_hash)
VALUES ('admin', 'admin123')
ON DUPLICATE KEY UPDATE username = VALUES(username);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image_url VARCHAR(500),
  category_id INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_rentable TINYINT(1) NOT NULL DEFAULT 0,
  rental_only TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  customer_name VARCHAR(150) NOT NULL,
  address TEXT NOT NULL,
  phone VARCHAR(40) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(30) NOT NULL,
  payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
  status VARCHAR(30) NOT NULL DEFAULT 'processing',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_user
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS tryons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  address TEXT NOT NULL,
  phone VARCHAR(40) NOT NULL,
  delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  return_deadline DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS rentals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  address TEXT NOT NULL,
  phone VARCHAR(40) NOT NULL,
  days INT NOT NULL DEFAULT 1,
  rent_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  deposit DECIMAL(10,2) NOT NULL DEFAULT 0,
  damage_fee DECIMAL(10,2) DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  rating INT NOT NULL DEFAULT 5,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  payment_method VARCHAR(30) NOT NULL,
  payment_provider VARCHAR(50) DEFAULT NULL,
  payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  transaction_id VARCHAR(255) DEFAULT NULL,
  payment_date DATETIME DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Seed sample data
INSERT INTO categories (name, gender) VALUES 
('T-Shirts', 'men'), 
('Pants', 'men'), 
('Formal Wears', 'men'), 
('Jackets', 'men'),
('Tops', 'women'),
('Dresses', 'women'),
('Sarees', 'women'),
('Jeans', 'women');
INSERT INTO products (name, description, price, image_url, category_id, is_active, is_rentable)
VALUES
('Classic Tee', 'Soft cotton classic t-shirt', 499.00, 'https://picsum.photos/seed/tee/600/400', 1, 1, 1),
('Blue Jeans', 'Slim fit denim', 1499.00, 'https://picsum.photos/seed/jeans/600/400', 2, 1, 1),
('Summer Dress', 'Floral print dress', 1999.00, 'https://picsum.photos/seed/dress/600/400', 3, 1, 1),
('Leather Jacket', 'Genuine leather biker jacket', 6999.00, 'https://picsum.photos/seed/jacket/600/400', 4, 1, 0);


