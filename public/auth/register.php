<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';

	$error = '';
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$name = trim($_POST['name'] ?? '');
		$email = strtolower(trim($_POST['email'] ?? ''));
		$pass = $_POST['password'] ?? '';
		if (!$name || !$email || !$pass) {
			$error = 'Please fill all fields.';
		} else {
			$exists = db_fetch_one("SELECT id FROM users WHERE email = :e", [':e' => $email]);
			if ($exists) {
				$error = 'Email already registered.';
			} else {
				$hash = password_hash($pass, PASSWORD_DEFAULT);
				db_execute("INSERT INTO users (name, email, password_hash, created_at) VALUES (:n,:e,:h,NOW())", [
					':n' => $name, ':e' => $email, ':h' => $hash
				]);
				$user = db_fetch_one("SELECT * FROM users WHERE email = :e", [':e' => $email]);
				auth_login_user($user);
				header('Location: /clothyyy/public/index.php');
				exit;
			}
		}
	}
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4" style="max-width:560px">
	<h1 class="h4 mb-3">Create Account</h1>
	<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
	<form method="post" class="card card-body">
		<div class="mb-3">
			<label class="form-label">Name</label>
			<input name="name" class="form-control" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Email</label>
			<input type="email" name="email" class="form-control" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Password</label>
			<input type="password" name="password" class="form-control" required>
		</div>
		<button class="btn btn-primary w-100">Register</button>
		<div class="text-center mt-3 small">Already have an account? <a href="/clothyyy/public/auth/login.php">Login</a></div>
	</form>
	<div class="text-center mt-3 small"><a href="/clothyyy/public/index.php">Back to Home</a></div>
	</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


