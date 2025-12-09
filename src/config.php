<?php
	// Basic configuration
	define('APP_NAME', 'Clothyyy');
	define('BASE_URL', '/clothyyy/public');
	// Database config (XAMPP default)
	define('DB_HOST', '127.0.0.1');
	define('DB_NAME', 'clothyyy');
	define('DB_USER', 'root');
	define('DB_PASS', '');
	define('DB_CHARSET', 'utf8mb4');
	// Business rules
	define('TRYON_RETURN_HOURS_LIMIT', 3);
	define('TRYON_DELIVERY_RATE', 0.10); // 10% of product price for try-on delivery
	define('RENTAL_SECURITY_DEPOSIT_RATE', 0.4); // 40% of price by default
	// File upload settings
	define('UPLOAD_DIR', __DIR__ . '/../public/uploads/images/');
	define('UPLOAD_URL', BASE_URL . '/uploads/images/');
	define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
	define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);


