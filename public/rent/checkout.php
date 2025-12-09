<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/clothyyy/public/rent/index.php');
	$product_id = (int)($_POST['product_id'] ?? 0);
	$days = max(1, (int)($_POST['days'] ?? 1));
	$p = db_fetch_one("SELECT * FROM products WHERE id = :id AND is_active = 1 AND is_rentable = 1", [':id' => $product_id]);
	if (!$p) die('Invalid product');
	$deposit = round($p['price'] * RENTAL_SECURITY_DEPOSIT_RATE, 2);
	$rent_fee = round(min($p['price'] * 0.1 * $days, $p['price']), 2);

	if (isset($_POST['confirm'])) {
		$name = trim($_POST['name'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		if ($name && $address && $phone) {
			db_execute("INSERT INTO rentals (product_id, customer_name, address, phone, days, rent_fee, deposit, status, created_at) VALUES (:pid,:n,:a,:p,:d,:fee,:dep,'active',NOW())", [
				':pid' => $product_id,
				':n' => $name,
				':a' => $address,
				':p' => $phone,
				':d' => $days,
				':fee' => $rent_fee,
				':dep' => $deposit
			]);
			$rid = (int)db_last_id();
			redirect('/clothyyy/public/rent/summary.php?rid=' . $rid);
		}
	}
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Rental Checkout</h1>
	<div class="row g-4">
		<div class="col-md-6">
			<div class="card mb-3">
				<div class="card-body">
					<h5 class="card-title mb-1"><?php echo e($p['name']); ?></h5>
					<div class="small text-muted mb-2">MRP: ₹<?php echo number_format((float)$p['price'], 2); ?></div>
					<ul class="list-unstyled mb-0">
						<li>Rental days: <strong><?php echo (int)$days; ?></strong></li>
						<li>Rent fee: <strong>₹<?php echo number_format($rent_fee, 2); ?></strong></li>
						<li>Refundable deposit: <strong>₹<?php echo number_format($deposit, 2); ?></strong></li>
					</ul>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
				<input type="hidden" name="days" value="<?php echo (int)$days; ?>">
				<div class="mb-3">
					<label class="form-label">Full Name</label>
					<input name="name" class="form-control" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Address</label>
					<textarea name="address" class="form-control" rows="3" required></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label">Phone</label>
					<input name="phone" class="form-control" required>
				</div>
				<input type="hidden" name="confirm" value="1">
				<button class="btn btn-success">Pay Deposit & Rent</button>
			</form>
			<div class="alert alert-info mt-3">On return, our team verifies for defects. If defective, you may be charged up to the full MRP.</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


