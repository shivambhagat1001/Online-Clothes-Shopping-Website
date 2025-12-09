<?php
	function e($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	function redirect($path) {
		header('Location: ' . $path);
		exit;
	}

	function base_url($path = '') {
		return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
	}

	function session_cart() {
		if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
		return $_SESSION['cart'];
	}

	function session_cart_add($productId, $qty = 1) {
		if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
		if (!isset($_SESSION['cart'][$productId])) $_SESSION['cart'][$productId] = 0;
		$_SESSION['cart'][$productId] += max(1, (int)$qty);
	}

	function session_cart_set($productId, $qty) {
		if ($qty <= 0) {
			unset($_SESSION['cart'][$productId]);
			return;
		}
		$_SESSION['cart'][$productId] = (int)$qty;
	}

	function session_cart_clear() {
		$_SESSION['cart'] = [];
	}

	/**
	 * Handle image file upload
	 * @param array $file $_FILES array element
	 * @return string|false Returns uploaded file URL on success, false on failure
	 */
	function upload_image($file) {
		if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
			return false;
		}

		// Validate file type
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
			return false;
		}

		// Validate file size
		if ($file['size'] > MAX_FILE_SIZE) {
			return false;
		}

		// Create upload directory if it doesn't exist
		if (!is_dir(UPLOAD_DIR)) {
			mkdir(UPLOAD_DIR, 0755, true);
		}

		// Generate unique filename
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		$filename = uniqid('img_', true) . '.' . $extension;
		$filepath = UPLOAD_DIR . $filename;

		// Move uploaded file
		if (move_uploaded_file($file['tmp_name'], $filepath)) {
			return UPLOAD_URL . $filename;
		}

		return false;
	}

	/**
	 * Delete uploaded image file
	 * @param string $imageUrl Full URL or path to image
	 * @return bool True on success, false on failure
	 */
	function delete_uploaded_image($imageUrl) {
		if (empty($imageUrl)) {
			return false;
		}

		// Extract filename from URL
		$filename = basename($imageUrl);
		$filepath = UPLOAD_DIR . $filename;

		// Check if file exists and is in upload directory
		if (file_exists($filepath) && strpos(realpath($filepath), realpath(UPLOAD_DIR)) === 0) {
			return @unlink($filepath);
		}

		return false;
	}


