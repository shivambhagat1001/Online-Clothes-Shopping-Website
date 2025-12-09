<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	$q = isset($_GET['q']) ? trim($_GET['q']) : '';
	$params = [];
	$sql = "SELECT p.* FROM products p WHERE p.is_active = 1 AND p.is_rentable = 1";
	if ($q !== '') {
		$sql .= " AND p.name LIKE :q";
		$params[':q'] = "%$q%";
	}
	$sql .= " ORDER BY p.created_at DESC";
	$products = db_fetch_all($sql, $params);
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 mb-0">Rent Clothes</h1>
		<form method="get" class="d-flex" style="max-width: 380px;">
			<input class="form-control me-2" type="search" name="q" placeholder="Search rentals..." value="<?php echo e($q); ?>">
			<button class="btn btn-outline-success">Search</button>
		</form>
	</div>
	<div class="alert alert-info">Pay a refundable deposit (default <?php echo (int)(RENTAL_SECURITY_DEPOSIT_RATE*100); ?>% of product price). If returned defective, you pay up to full price.</div>
	<div class="row g-3">
		<?php foreach ($products as $p): ?>
			<div class="col-6 col-md-3">
				<div class="card h-100">
					<?php if (!empty($p['image_url'])): ?>
						<img src="<?php echo e($p['image_url']); ?>" class="card-img-top" alt="<?php echo e($p['name']); ?>">
					<?php else: ?>
						<div class="ratio ratio-4x3 bg-light"></div>
					<?php endif; ?>
					<div class="card-body d-flex flex-column">
						<h5 class="card-title mb-1"><?php echo e($p['name']); ?></h5>
						<div class="small text-muted mb-2">MRP: ₹<?php echo number_format((float)$p['price'], 2); ?></div>
						<div class="mt-auto">
							<form action="/clothyyy/public/rent/checkout.php" method="post">
								<input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
								<div class="mb-2">
									<label class="form-label small mb-1">Rental Days</label>
									<input type="number" name="days" value="1" min="1" class="form-control form-control-sm" style="max-width:100px" required>
								</div>
								<button class="btn btn-success btn-sm w-100">Rent Now</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


