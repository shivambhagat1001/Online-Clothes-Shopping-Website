<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
		$order_id = (int)$_POST['id'];
		$new_payment_status = $_POST['payment_status'];
		
		db_execute("UPDATE orders SET status=:s, payment_status=:ps WHERE id=:id", [
			':s' => $_POST['status'],
			':ps' => $new_payment_status,
			':id' => $order_id
		]);
		
		// Sync payment status in payments table
		$update_payment_sql = "UPDATE payments SET payment_status = :ps, updated_at = NOW()";
		$payment_params = [':ps' => $new_payment_status, ':oid' => $order_id];
		
		// If status is paid, set payment_date if not already set
		if ($new_payment_status === 'paid') {
			$update_payment_sql .= ", payment_date = COALESCE(payment_date, NOW())";
		}
		
		$update_payment_sql .= " WHERE order_id = :oid";
		db_execute($update_payment_sql, $payment_params);
		
		redirect('/clothyyy/public/admin/orders.php');
	}
	$orders = db_fetch_all("SELECT * FROM orders ORDER BY created_at DESC");
	
	// Fetch product details for each order
	$order_products = [];
	foreach ($orders as $order) {
		$order_id = (int)$order['id'];
		$order_products[$order_id] = db_fetch_all("
			SELECT 
				oi.quantity,
				oi.price as item_price,
				pr.id as product_id,
				pr.name as product_name,
				pr.image_url
			FROM order_items oi
			LEFT JOIN products pr ON pr.id = oi.product_id
			WHERE oi.order_id = :oid
		", [':oid' => $order_id]);
	}
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Orders</h1>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th>#</th><th>Customer</th><th>Products</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th></th></tr></thead>
			<tbody>
				<?php foreach ($orders as $o): 
					$order_id = (int)$o['id'];
					$products = $order_products[$order_id] ?? [];
				?>
					<tr>
						<td><?php echo $order_id; ?></td>
						<td>
							<div><?php echo e($o['customer_name']); ?></div>
							<div class="small text-muted"><?php echo e($o['phone']); ?></div>
						</td>
						<td>
							<?php if (!empty($products)): ?>
								<div class="small">
									<?php foreach ($products as $item): ?>
										<div class="mb-1">
											<?php if (!empty($item['image_url'])): ?>
												<img src="<?php echo e($item['image_url']); ?>" 
													alt="<?php echo e($item['product_name']); ?>" 
													class="me-2" 
													style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
											<?php endif; ?>
											<strong><?php echo e($item['product_name']); ?></strong>
											<span class="text-muted">× <?php echo (int)$item['quantity']; ?></span>
											<div class="text-muted" style="font-size: 0.85em;">
												₹<?php echo number_format((float)$item['item_price'], 2); ?> each
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php else: ?>
								<span class="text-muted small">No products</span>
							<?php endif; ?>
						</td>
						<td><strong>₹<?php echo number_format((float)$o['total_amount'], 2); ?></strong></td>
						<td>
							<?php
								$payment_badge = [
									'paid' => 'success',
									'pending' => 'warning',
									'failed' => 'danger',
									'refunded' => 'info'
								];
								$badge_class = $payment_badge[$o['payment_status']] ?? 'secondary';
							?>
							<span class="badge bg-<?php echo $badge_class; ?>">
								<?php echo ucfirst(e($o['payment_status'])); ?>
							</span>
						</td>
						<td>
							<?php
								$status_badge = [
									'processing' => 'primary',
									'shipped' => 'info',
									'delivered' => 'success',
									'cancelled' => 'danger'
								];
								$status_class = $status_badge[$o['status']] ?? 'secondary';
							?>
							<span class="badge bg-<?php echo $status_class; ?>">
								<?php echo ucfirst(e($o['status'])); ?>
							</span>
						</td>
						<td>
							<div class="small"><?php echo date('Y-m-d', strtotime($o['created_at'])); ?></div>
							<div class="small text-muted"><?php echo date('H:i', strtotime($o['created_at'])); ?></div>
						</td>
						<td><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#o<?php echo $order_id; ?>">Update</button></td>
					</tr>
					<tr class="collapse" id="o<?php echo $order_id; ?>">
						<td colspan="8">
							<form method="post" class="d-flex gap-2 align-items-end">
								<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
								<div>
									<label class="form-label small mb-1">Payment</label>
									<select name="payment_status" class="form-select form-select-sm">
										<?php foreach (['pending','paid','failed','refunded'] as $ps): ?>
											<option <?php echo $ps===$o['payment_status']?'selected':''; ?>><?php echo $ps; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div>
									<label class="form-label small mb-1">Status</label>
									<select name="status" class="form-select form-select-sm">
										<?php foreach (['processing','shipped','delivered','cancelled'] as $st): ?>
											<option <?php echo $st===$o['status']?'selected':''; ?>><?php echo $st; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<button class="btn btn-sm btn-primary ms-2" name="update">Save</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


