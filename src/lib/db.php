<?php
	require_once __DIR__ . '/../config.php';

	function db_pdo() {
		static $pdo = null;
		if ($pdo !== null) return $pdo;
		$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		];
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		return $pdo;
	}

	function db_fetch_all($sql, $params = []) {
		$stmt = db_pdo()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	function db_fetch_one($sql, $params = []) {
		$stmt = db_pdo()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetch();
	}

	function db_execute($sql, $params = []) {
		$stmt = db_pdo()->prepare($sql);
		return $stmt->execute($params);
	}

	function db_last_id() {
		return db_pdo()->lastInsertId();
	}








