<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
	$r = db_fetch_one("SELECT r.*, p.name AS product_name FROM rentals r LEFT JOIN products p ON p.id = r.product_id WHERE r.id = :id", [':id' => $rid]);
	if (!$r) die('Not found');
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Rental Summary</h1>
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between">
				<div>
					<div><strong>Rental ID:</strong> #<?php echo (int)$r['id']; ?></div>
					<div><strong>Item:</strong> <?php echo e($r['product_name']); ?></div>
					<div><strong>Days:</strong> <?php echo (int)$r['days']; ?></div>
				</div>
				<div class="text-end">
					<div>Rent Fee: ₹<?php echo number_format((float)$r['rent_fee'], 2); ?></div>
					<div>Deposit: ₹<?php echo number_format((float)$r['deposit'], 2); ?></div>
					<div class="fw-bold">Status: <?php echo e($r['status']); ?></div>
				</div>
			</div>
			<div class="mt-3">Our team will contact you for delivery and pickup schedule.</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>








