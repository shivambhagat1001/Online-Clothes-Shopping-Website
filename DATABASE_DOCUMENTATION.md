# Database Tables Documentation - Clothyyy Project

This document explains all database tables, their structure, relationships, and where they are used throughout the project.

## Database Overview
- **Database Name**: `clothyyy`
- **Character Set**: `utf8mb4`
- **Connection**: MySQL via PDO (configured in `src/config.php`)

---

## Table: `categories`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(100), NOT NULL)
- gender (ENUM('men', 'women'), DEFAULT NULL)
```

### Purpose
Stores product categories with gender classification (men/women).

### Relationships
- Referenced by: `products.category_id` (FOREIGN KEY, ON DELETE SET NULL)

### Usage Locations

**Read Operations:**
- `public/products.php` - Filter products by category and gender
- `public/index.php` - Display categories in navigation/filters
- `public/admin/categories.php` - List all categories for admin management
- `public/admin/products.php` - Dropdown for selecting category when creating/editing products

**Write Operations:**
- `public/admin/categories.php` - CREATE (INSERT) new categories
- `public/admin/categories.php` - UPDATE existing categories
- `public/admin/categories.php` - DELETE categories

**Sample Queries:**
```php
// Get all categories with gender
SELECT * FROM categories WHERE gender IS NOT NULL ORDER BY gender ASC, name ASC

// Get category by ID
SELECT * FROM categories WHERE id = :id
```

---

## Table: `users`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(150), NOT NULL)
- email (VARCHAR(190), NOT NULL, UNIQUE)
- password_hash (VARCHAR(255), NOT NULL)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores customer/user accounts for the e-commerce platform.

### Relationships
- Referenced by: `orders.user_id` (FOREIGN KEY, ON DELETE SET NULL)

### Usage Locations

**Read Operations:**
- `public/auth/login.php` - Authenticate user login
- `public/auth/register.php` - Check if email already exists
- `public/checkout.php` - Verify user exists when placing order
- `public/payment.php` - Verify user exists when processing payment

**Write Operations:**
- `public/auth/register.php` - CREATE (INSERT) new user accounts

**Sample Queries:**
```php
// Login check
SELECT * FROM users WHERE email = :e

// Check email exists
SELECT id FROM users WHERE email = :e

// Create new user
INSERT INTO users (name, email, password_hash, created_at) VALUES (:n, :e, :h, NOW())
```

---

## Table: `admins`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- username (VARCHAR(100), NOT NULL, UNIQUE)
- password_hash (VARCHAR(255), NOT NULL)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores administrator accounts for backend management.

### Relationships
- None (standalone table)

### Usage Locations

**Read Operations:**
- `public/admin/login.php` - Authenticate admin login

**Write Operations:**
- `public/admin/login.php` - UPDATE password hash (if password change needed)
- `database/schema.sql` - Seed default admin (username: admin, password: admin123)

**Sample Queries:**
```php
// Admin login
SELECT * FROM admins WHERE username = :u

// Update admin password
UPDATE admins SET password_hash = :h WHERE id = :id
```

---

## Table: `products`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(200), NOT NULL)
- description (TEXT)
- price (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- image_url (VARCHAR(500))
- category_id (INT, DEFAULT NULL, FOREIGN KEY → categories.id)
- is_active (TINYINT(1), NOT NULL, DEFAULT 1)
- is_rentable (TINYINT(1), NOT NULL, DEFAULT 0)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores all products/clothing items. Can be marked as active/inactive and rentable/non-rentable.

### Relationships
- References: `categories.id` (FOREIGN KEY, ON DELETE SET NULL)
- Referenced by: 
  - `order_items.product_id` (ON DELETE RESTRICT)
  - `tryons.product_id` (ON DELETE RESTRICT)
  - `rentals.product_id` (ON DELETE RESTRICT)

### Usage Locations

**Read Operations:**
- `public/index.php` - Display featured products on homepage
- `public/products.php` - List all active products with filtering
- `public/product.php` - Show single product details
- `public/cart.php` - Get product details for cart items
- `public/checkout.php` - Get product details for checkout
- `public/payment.php` - Get product details for payment
- `public/tryon.php` - List products for try-on selection
- `public/rent/index.php` - List only rentable products
- `public/rent/checkout.php` - Get product details for rental
- `public/admin/products.php` - Admin product management list
- `public/admin/index.php` - Count total products (dashboard)

**Write Operations:**
- `public/admin/products.php` - CREATE (INSERT) new products
- `public/admin/products.php` - UPDATE existing products
- `public/admin/products.php` - DELETE products

**Sample Queries:**
```php
// Get active products with category
SELECT p.*, c.name AS category_name, c.gender 
FROM products p 
LEFT JOIN categories c ON c.id = p.category_id 
WHERE p.is_active = 1

// Get rentable products
SELECT p.* FROM products p 
WHERE p.is_active = 1 AND p.is_rentable = 1

// Get single product
SELECT p.*, c.name AS category_name 
FROM products p 
LEFT JOIN categories c ON c.id = p.category_id 
WHERE p.id = :id AND p.is_active = 1
```

---

## Table: `orders`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, DEFAULT NULL, FOREIGN KEY → users.id)
- customer_name (VARCHAR(150), NOT NULL)
- address (TEXT, NOT NULL)
- phone (VARCHAR(40), NOT NULL)
- total_amount (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- payment_method (VARCHAR(30), NOT NULL)
- payment_status (VARCHAR(30), NOT NULL, DEFAULT 'pending')
- status (VARCHAR(30), NOT NULL, DEFAULT 'processing')
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores purchase orders from customers. Can be linked to a user account or be guest orders.

### Relationships
- References: `users.id` (FOREIGN KEY, ON DELETE SET NULL)
- Referenced by: `order_items.order_id` (FOREIGN KEY, ON DELETE CASCADE)

### Usage Locations

**Read Operations:**
- `public/orders.php` - List user's orders, cancel orders
- `public/admin/orders.php` - Admin view of all orders
- `public/admin/index.php` - Count total orders (dashboard)

**Write Operations:**
- `public/checkout.php` - CREATE (INSERT) new order (COD payment)
- `public/payment.php` - CREATE (INSERT) new order (UPI payment)
- `public/orders.php` - UPDATE status to 'cancelled'
- `public/admin/orders.php` - UPDATE status and payment_status

**Sample Queries:**
```php
// Get user orders
SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC

// Create order
INSERT INTO orders (user_id, customer_name, address, phone, total_amount, 
                    payment_method, payment_status, status, created_at) 
VALUES (:uid, :n, :a, :p, :t, :pm, 'pending', 'processing', NOW())

// Update order status
UPDATE orders SET status = :s, payment_status = :ps WHERE id = :id
```

---

## Table: `order_items`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- order_id (INT, NOT NULL, FOREIGN KEY → orders.id)
- product_id (INT, NOT NULL, FOREIGN KEY → products.id)
- quantity (INT, NOT NULL, DEFAULT 1)
- price (DECIMAL(10,2), NOT NULL, DEFAULT 0)
```

### Purpose
Stores individual items within an order (line items). Links products to orders with quantity and price.

### Relationships
- References: 
  - `orders.id` (FOREIGN KEY, ON DELETE CASCADE)
  - `products.id` (FOREIGN KEY, ON DELETE RESTRICT)

### Usage Locations

**Write Operations:**
- `public/checkout.php` - CREATE (INSERT) order items when order is placed
- `public/payment.php` - CREATE (INSERT) order items when payment is processed

**Note:** This table is primarily used for order creation. There's no dedicated read operation, but items are typically joined with orders when needed.

**Sample Queries:**
```php
// Insert order item
INSERT INTO order_items (order_id, product_id, quantity, price) 
VALUES (:o, :pid, :q, :price)
```

---

## Table: `payments`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- order_id (INT, NOT NULL, FOREIGN KEY → orders.id)
- payment_method (VARCHAR(30), NOT NULL)
- payment_provider (VARCHAR(50), DEFAULT NULL)
- payment_status (VARCHAR(30), NOT NULL, DEFAULT 'pending')
- amount (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- transaction_id (VARCHAR(255), DEFAULT NULL)
- payment_date (DATETIME, DEFAULT NULL)
- notes (TEXT, DEFAULT NULL)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- updated_at (DATETIME, DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP)
```

### Purpose
Stores detailed payment information for all orders. Provides comprehensive payment tracking with transaction IDs, payment providers, and status history. This table allows admins to understand payment details separately from order information.

### Relationships
- References: `orders.id` (FOREIGN KEY, ON DELETE CASCADE)

### Usage Locations

**Read Operations:**
- `public/admin/payments.php` - View all payments with detailed information
- `public/admin/index.php` - Count total payments (dashboard)

**Write Operations:**
- `public/payment.php` - CREATE (INSERT) payment record when UPI payment is confirmed
- `public/checkout.php` - CREATE (INSERT) payment record for COD orders
- `public/admin/payments.php` - UPDATE payment status and notes
- `public/admin/orders.php` - UPDATE payment status (syncs with payments table)

**Sample Queries:**
```php
// Create payment record (UPI)
INSERT INTO payments (order_id, payment_method, payment_provider, payment_status, 
                      amount, transaction_id, payment_date, created_at) 
VALUES (:oid, 'upi', :provider, 'paid', :amt, :txn, NOW(), NOW())

// Create payment record (COD)
INSERT INTO payments (order_id, payment_method, payment_provider, payment_status, 
                      amount, notes, created_at) 
VALUES (:oid, 'cod', NULL, 'pending', :amt, 'Cash on Delivery - Payment pending', NOW())

// Get all payments with order details
SELECT p.*, o.customer_name, o.phone, o.total_amount as order_total
FROM payments p
LEFT JOIN orders o ON o.id = p.order_id
ORDER BY p.created_at DESC

// Update payment status
UPDATE payments SET payment_status = :ps, updated_at = NOW() WHERE id = :id

// Get payment statistics
SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'paid'
```

**Payment Status Values:**
- `pending` - Payment not yet received (e.g., COD)
- `paid` - Payment successfully received
- `failed` - Payment attempt failed
- `refunded` - Payment was refunded

**Payment Methods:**
- `upi` - UPI payment (Google Pay, Paytm, PhonePe, Razorpay, etc.)
- `cod` - Cash on Delivery

**Payment Providers (for UPI):**
- `gpay` - Google Pay
- `paytm` - Paytm
- `phonepe` - PhonePe
- `razorpay` - Razorpay
- Or any other UPI provider

**Key Features:**
- Transaction IDs are auto-generated for UPI payments (format: `TXN{UNIQUEID}{TIMESTAMP}`)
- Payment date is automatically set when status changes to 'paid'
- Notes field allows admins to add additional payment information
- Updated_at timestamp tracks when payment records are modified
- Payment status is synced with orders table for consistency

---

## Table: `tryons`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- product_id (INT, NOT NULL, FOREIGN KEY → products.id)
- customer_name (VARCHAR(150), NOT NULL)
- address (TEXT, NOT NULL)
- phone (VARCHAR(40), NOT NULL)
- delivery_charge (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- status (VARCHAR(30), NOT NULL, DEFAULT 'scheduled')
- return_deadline (DATETIME, DEFAULT NULL)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores home try-on requests. Customers can request products to be delivered for trying on at home with a return deadline.

### Relationships
- References: `products.id` (FOREIGN KEY, ON DELETE RESTRICT)

### Usage Locations

**Read Operations:**
- `public/tryon.php` - Display recent try-on requests
- `public/admin/tryons.php` - Admin view of all try-on requests
- `public/admin/index.php` - Count total try-ons (dashboard)

**Write Operations:**
- `public/tryon.php` - CREATE (INSERT) new try-on request
- `public/admin/tryons.php` - UPDATE status (e.g., 'completed', 'returned')

**Sample Queries:**
```php
// Create try-on request
INSERT INTO tryons (product_id, customer_name, address, phone, delivery_charge, 
                    status, return_deadline, created_at) 
VALUES (:pid, :n, :a, :p, :charge, 'scheduled', 
        DATE_ADD(NOW(), INTERVAL :hrs HOUR), NOW())

// Get try-ons with product name
SELECT t.*, p.name AS product_name 
FROM tryons t 
LEFT JOIN products p ON p.id = t.product_id 
ORDER BY t.created_at DESC

// Update try-on status
UPDATE tryons SET status = :s WHERE id = :id
```

**Business Logic:**
- Delivery charge = 10% of product price (TRYON_DELIVERY_RATE)
- Return deadline = 3 hours from creation (TRYON_RETURN_HOURS_LIMIT)

---

## Table: `rentals`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- product_id (INT, NOT NULL, FOREIGN KEY → products.id)
- customer_name (VARCHAR(150), NOT NULL)
- address (TEXT, NOT NULL)
- phone (VARCHAR(40), NOT NULL)
- days (INT, NOT NULL, DEFAULT 1)
- rent_fee (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- deposit (DECIMAL(10,2), NOT NULL, DEFAULT 0)
- damage_fee (DECIMAL(10,2), DEFAULT 0)
- status (VARCHAR(30), NOT NULL, DEFAULT 'active')
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores rental transactions. Customers can rent products for a specified number of days with a security deposit.

### Relationships
- References: `products.id` (FOREIGN KEY, ON DELETE RESTRICT)

### Usage Locations

**Read Operations:**
- `public/rent/summary.php` - Display rental summary after checkout
- `public/admin/rentals.php` - Admin view of all rentals
- `public/admin/index.php` - Count total rentals (dashboard)

**Write Operations:**
- `public/rent/checkout.php` - CREATE (INSERT) new rental
- `public/admin/rentals.php` - UPDATE status and damage_fee (when product returned)

**Sample Queries:**
```php
// Create rental
INSERT INTO rentals (product_id, customer_name, address, phone, days, 
                     rent_fee, deposit, status, created_at) 
VALUES (:pid, :n, :a, :p, :d, :fee, :dep, 'active', NOW())

// Get rental with product name
SELECT r.*, p.name AS product_name 
FROM rentals r 
LEFT JOIN products p ON p.id = r.product_id 
ORDER BY r.created_at DESC

// Update rental (when returned)
UPDATE rentals SET status = :s, damage_fee = :df WHERE id = :id
```

**Business Logic:**
- Security deposit = 40% of product price by default (RENTAL_SECURITY_DEPOSIT_RATE)
- If product returned defective, damage_fee can be charged up to full price
- Rent fee is calculated based on rental days

---

## Table: `feedback`

### Structure
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR(120), NOT NULL)
- rating (INT, NOT NULL, DEFAULT 5)
- message (TEXT, NOT NULL)
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
```

### Purpose
Stores customer feedback/reviews with ratings.

### Relationships
- None (standalone table)

### Usage Locations

**Read Operations:**
- `public/feedback.php` - Display recent feedback on public page
- `public/admin/feedback.php` - Admin view of all feedback
- `public/admin/index.php` - Count total feedback (dashboard)

**Write Operations:**
- `public/feedback.php` - CREATE (INSERT) new feedback

**Sample Queries:**
```php
// Create feedback
INSERT INTO feedback (name, rating, message, created_at) 
VALUES (:n, :r, :m, NOW())

// Get all feedback
SELECT * FROM feedback ORDER BY created_at DESC

// Get recent feedback (public view)
SELECT * FROM feedback ORDER BY created_at DESC LIMIT 20
```

---

## Database Relationships Summary

```
categories (1) ──→ (many) products
users (1) ──→ (many) orders
orders (1) ──→ (many) order_items
orders (1) ──→ (many) payments
products (1) ──→ (many) order_items
products (1) ──→ (many) tryons
products (1) ──→ (many) rentals
```

## Key Business Rules

1. **Products**: Must be marked `is_active = 1` to be visible. Must be marked `is_rentable = 1` to be rentable.
2. **Orders**: Can be linked to a user account (`user_id`) or be guest orders (`user_id = NULL`).
3. **Try-ons**: Return deadline is automatically set to 3 hours from creation.
4. **Rentals**: Security deposit is 40% of product price by default.
5. **Categories**: Can be filtered by gender ('men' or 'women').

## Database Helper Functions

All database operations use helper functions from `src/lib/db.php`:
- `db_pdo()` - Get PDO connection
- `db_fetch_all($sql, $params)` - Execute SELECT and return all rows
- `db_fetch_one($sql, $params)` - Execute SELECT and return single row
- `db_execute($sql, $params)` - Execute INSERT/UPDATE/DELETE
- `db_last_id()` - Get last inserted ID

