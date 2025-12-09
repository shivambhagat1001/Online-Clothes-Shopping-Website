<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();
	$stats = [
		'products' => db_fetch_one("SELECT COUNT(*) c FROM products")['c'] ?? 0,
		'orders' => db_fetch_one("SELECT COUNT(*) c FROM orders")['c'] ?? 0,
		'payments' => db_fetch_one("SELECT COUNT(*) c FROM payments")['c'] ?? 0,
		'tryons' => db_fetch_one("SELECT COUNT(*) c FROM tryons")['c'] ?? 0,
		'rentals' => db_fetch_one("SELECT COUNT(*) c FROM rentals")['c'] ?? 0,
		'feedback' => db_fetch_one("SELECT COUNT(*) c FROM feedback")['c'] ?? 0,
	];
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Admin Dashboard</h1>
	<div class="row g-3">
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['products']; ?></div>
					<div>Products</div>
					<a href="/clothyyy/public/admin/products.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['orders']; ?></div>
					<div>Orders</div>
					<a href="/clothyyy/public/admin/orders.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['payments']; ?></div>
					<div>Payments</div>
					<a href="/clothyyy/public/admin/payments.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['tryons']; ?></div>
					<div>Try-Ons</div>
					<a href="/clothyyy/public/admin/tryons.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['rentals']; ?></div>
					<div>Rentals</div>
					<a href="/clothyyy/public/admin/rentals.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6"><?php echo (int)$stats['feedback']; ?></div>
					<div>Feedback</div>
					<a href="/clothyyy/public/admin/feedback.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
		<div class="col-6 col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="display-6">📁</div>
					<div>Categories</div>
					<a href="/clothyyy/public/admin/categories.php" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


