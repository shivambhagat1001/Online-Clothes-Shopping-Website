<?php
	if (session_status() === PHP_SESSION_NONE) session_start();
	require_once __DIR__ . '/db.php';

	function auth_user() {
		return $_SESSION['user'] ?? null;
	}

	function auth_admin_id() {
		return $_SESSION['admin_id'] ?? null;
	}

	function auth_require_admin() {
		if (!auth_admin_id()) {
			header('Location: /clothyyy/public/admin/login.php');
			exit;
		}
	}

	function auth_login_user($user) {
		$_SESSION['user'] = [
			'id' => (int)$user['id'],
			'name' => $user['name'],
			'email' => $user['email']
		];
	}

	function auth_logout_user() {
		unset($_SESSION['user']);
	}

	function auth_login_admin($adminId) {
		$_SESSION['admin_id'] = (int)$adminId;
	}

	function auth_logout_admin() {
		unset($_SESSION['admin_id']);
	}


