<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';

	if (auth_user()) {
		$redirect = $_GET['redirect'] ?? '/clothyyy/public/index.php';
		header('Location: ' . $redirect);
		exit;
	}
	$error = '';
	$redirect = $_GET['redirect'] ?? '/clothyyy/public/index.php';
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$email = strtolower(trim($_POST['email'] ?? ''));
		$pass = $_POST['password'] ?? '';
		$user = db_fetch_one("SELECT * FROM users WHERE email = :e", [':e' => $email]);
		if ($user && password_verify($pass, $user['password_hash'])) {
			auth_login_user($user);
			$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '/clothyyy/public/index.php';
			header('Location: ' . $redirect);
			exit;
		} else {
			$error = 'Invalid credentials.';
		}
	}
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4" style="max-width:560px">
	<h1 class="h4 mb-3">Login</h1>
	<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
	<form method="post" class="card card-body">
		<input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">
		<div class="mb-3">
			<label class="form-label">Email</label>
			<input type="email" name="email" class="form-control" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Password</label>
			<input type="password" name="password" class="form-control" required>
		</div>
		<button class="btn btn-primary w-100">Login</button>
		<div class="text-center mt-3 small">New user? <a href="/clothyyy/public/auth/register.php">Create an account</a></div>
	</form>
	<div class="text-center mt-3 small"><a href="/clothyyy/public/index.php">Back to Home</a></div>
	</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


