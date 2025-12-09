<?php
	session_start();
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_logout_user();
	header('Location: /clothyyy/public/index.php');
	exit;


