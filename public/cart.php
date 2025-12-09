<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['update'])) {
			foreach ($_POST['qty'] ?? [] as $pid => $qty) {
				session_cart_set((int)$pid, (int)$qty);
			}
			redirect('/clothyyy/public/cart.php');
		}
		if (isset($_POST['clear'])) {
			session_cart_clear();
			redirect('/clothyyy/public/cart.php');
		}
	}

	$cart = session_cart();
	$productIds = array_keys($cart);
	$items = [];
	$subtotal = 0.0;
	if ($productIds) {
		$in = implode(',', array_fill(0, count($productIds), '?'));
		$rows = db_fetch_all("SELECT id, name, price, image_url FROM products WHERE id IN ($in)", $productIds);
		$map = [];
		foreach ($rows as $r) $map[$r['id']] = $r;
		foreach ($cart as $pid => $qty) {
			if (!isset($map[$pid])) continue;
			$p = $map[$pid];
			$line = $p['price'] * $qty;
			$subtotal += $line;
			$items[] = ['id' => $pid, 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => (int)$qty, 'image_url' => $p['image_url'], 'line' => $line];
		}
	}
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-5">
	<div class="section-header">
		<h2>🛒 Your Cart</h2>
	</div>
	<?php if (!$items): ?>
		<div class="alert alert-info text-center py-5">
			<h5>Your cart is empty</h5>
			<p class="mb-0">Start shopping to add items to your cart!</p>
			<a href="/clothyyy/public/products.php" class="btn btn-primary mt-3">Browse Products</a>
		</div>
	<?php else: ?>
		<form method="post">
			<div class="card mb-4">
				<div class="table-responsive">
					<table class="table align-middle mb-0">
						<thead>
							<tr>
								<th>Item</th>
								<th style="width:120px">Quantity</th>
								<th style="width:140px">Price</th>
								<th style="width:140px">Total</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($items as $it): ?>
								<tr>
									<td>
										<div class="d-flex align-items-center gap-3">
											<?php if (!empty($it['image_url'])): ?>
												<img src="<?php echo e($it['image_url']); ?>" class="rounded" style="width:80px;height:80px;object-fit:cover;box-shadow: var(--shadow-sm);" alt="">
											<?php else: ?>
												<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:80px;height:80px;">
													<span class="text-muted small">No Image</span>
												</div>
											<?php endif; ?>
											<div>
												<div class="fw-semibold mb-1">
													<a href="/clothyyy/public/product.php?id=<?php echo (int)$it['id']; ?>" class="text-decoration-none"><?php echo e($it['name']); ?></a>
												</div>
												<small class="text-muted">₹<?php echo number_format($it['price'], 2); ?> each</small>
											</div>
										</div>
									</td>
									<td>
										<input type="number" name="qty[<?php echo (int)$it['id']; ?>]" value="<?php echo (int)$it['qty']; ?>" min="0" class="form-control text-center">
									</td>
									<td><span class="fw-semibold">₹<?php echo number_format($it['price'], 2); ?></span></td>
									<td class="fw-bold price">₹<?php echo number_format($it['line'], 2); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="row g-4">
				<div class="col-md-6">
					<div class="d-flex gap-2">
						<button name="update" class="btn btn-outline-secondary">🔄 Update Cart</button>
						<button name="clear" class="btn btn-outline-danger" onclick="return confirm('Clear all items from cart?')">🗑️ Clear Cart</button>
					</div>
				</div>
				<div class="col-md-6">
					<div class="card">
						<div class="card-body">
							<h5 class="card-title mb-3">Order Summary</h5>
							<div class="d-flex justify-content-between mb-2">
								<span>Subtotal:</span>
								<span class="fw-semibold">₹<?php echo number_format($subtotal, 2); ?></span>
							</div>
							<hr>
							<div class="d-flex justify-content-between mb-3">
								<span class="fs-5 fw-bold">Total:</span>
								<span class="fs-5 fw-bold price">₹<?php echo number_format($subtotal, 2); ?></span>
							</div>
							<a href="/clothyyy/public/checkout.php" class="btn btn-primary w-100 btn-lg">🚀 Proceed to Checkout</a>
						</div>
					</div>
				</div>
			</div>
		</form>
	<?php endif; ?>
</div>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


