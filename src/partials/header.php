<?php
	if (session_status() === PHP_SESSION_NONE) session_start();
	require_once __DIR__ . '/../config.php';
	require_once __DIR__ . '/../lib/helpers.php';
	require_once __DIR__ . '/../lib/auth.php';
	$user = auth_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo APP_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="/clothyyy/public/assets/css/styles.css" rel="stylesheet">
	<link rel="icon" href="data:,">
</head>
<body>
	<nav class="navbar navbar-expand-lg bg-body-tertiary">
		<div class="container">
			<a class="navbar-brand fw-bold" href="/clothyyy/public/index.php"><?php echo APP_NAME; ?></a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav me-auto mb-2 mb-lg-0">
					<li class="nav-item"><a class="nav-link" href="/clothyyy/public/products.php">🛍️ Shop</a></li>
					<li class="nav-item"><a class="nav-link" href="/clothyyy/public/rent/index.php">👔 Rent</a></li>
					<li class="nav-item"><a class="nav-link" href="/clothyyy/public/tryon.php">🏠 Try-On</a></li>
					<li class="nav-item"><a class="nav-link" href="/clothyyy/public/orders.php">📦 Orders</a></li>
					<li class="nav-item"><a class="nav-link" href="/clothyyy/public/feedback.php">💬 Feedback</a></li>
				</ul>
				<div class="d-flex align-items-center gap-2">
					<a href="/clothyyy/public/cart.php" class="btn btn-outline-primary me-2 position-relative">
						🛒 Cart
						<?php
						$cartCount = count(session_cart());
						if ($cartCount > 0):
						?>
							<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
								<?php echo $cartCount; ?>
							</span>
						<?php endif; ?>
					</a>
					<?php if ($user): ?>
						<span class="small text-muted d-none d-md-inline">👋 Hi, <?php echo e($user['name']); ?></span>
						<a href="/clothyyy/public/auth/logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
					<?php else: ?>
						<a href="/clothyyy/public/auth/login.php" class="btn btn-outline-secondary btn-sm">Login</a>
						<a href="/clothyyy/public/auth/register.php" class="btn btn-primary btn-sm">Sign Up</a>
					<?php endif; ?>
					<a href="/clothyyy/public/admin/login.php" class="btn btn-outline-dark btn-sm">⚙️ Admin</a>
				</div>
			</div>
		</div>
	</nav>
	<div id="chatbot-root"></div>


