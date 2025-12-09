<?php
	session_start();
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	require_once __DIR__ . '/../../src/partials/header.php';
	$error = '';
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		$admin = db_fetch_one("SELECT * FROM admins WHERE username = :u", [':u' => $username]);
		if ($admin && (password_verify($password, $admin['password_hash']) || $password === $admin['password_hash'])) {
			// If plaintext was stored (from seed), upgrade to bcrypt hash
			if ($password === $admin['password_hash']) {
				$newHash = password_hash($password, PASSWORD_DEFAULT);
				db_execute("UPDATE admins SET password_hash = :h WHERE id = :id", [':h' => $newHash, ':id' => (int)$admin['id']]);
			}
			auth_login_admin($admin['id']);
			header('Location: /clothyyy/public/admin/index.php');
			exit;
		} else {
			$error = 'Invalid credentials.';
		}
	}
?>
<div class="container py-4" style="max-width:560px">
	<h1 class="h4 mb-3">Admin Login</h1>
	<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
	<form method="post" class="card card-body">
		<div class="mb-3">
			<label class="form-label">Username</label>
			<input name="username" class="form-control" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Password</label>
			<input type="password" name="password" class="form-control" required>
		</div>
		<button class="btn btn-primary w-100">Login</button>
	</form>
	<div class="text-center mt-3 small"><a href="/clothyyy/public/index.php">Back to Home</a></div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


