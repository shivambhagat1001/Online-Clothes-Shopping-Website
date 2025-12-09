<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	// Update payment status
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
		$payment_id = (int)$_POST['id'];
		$payment_status = trim($_POST['payment_status'] ?? '');
		$notes = trim($_POST['notes'] ?? '');
		
		// Update payment record
		$update_sql = "UPDATE payments SET payment_status = :ps";
		$params = [':ps' => $payment_status, ':id' => $payment_id];
		
		if ($notes !== '') {
			$update_sql .= ", notes = :notes";
			$params[':notes'] = $notes;
		}
		
		// If status is paid, set payment_date
		if ($payment_status === 'paid' && !isset($_POST['payment_date_set'])) {
			$update_sql .= ", payment_date = NOW()";
		}
		
		$update_sql .= ", updated_at = NOW() WHERE id = :id";
		
		db_execute($update_sql, $params);
		
		// Also update the order's payment_status to keep them in sync
		$payment = db_fetch_one("SELECT order_id FROM payments WHERE id = :id", [':id' => $payment_id]);
		if ($payment) {
			db_execute("UPDATE orders SET payment_status = :ps WHERE id = :oid", [
				':ps' => $payment_status,
				':oid' => $payment['order_id']
			]);
		}
		
		redirect('/clothyyy/public/admin/payments.php');
	}

	// Get all payments with order and customer details
	$payments = db_fetch_all("
		SELECT 
			p.*,
			o.customer_name,
			o.phone,
			o.total_amount as order_total
		FROM payments p
		LEFT JOIN orders o ON o.id = p.order_id
		ORDER BY p.created_at DESC
	");
	
	// Fetch product details for each payment (through order)
	$payment_products = [];
	foreach ($payments as $payment) {
		$order_id = (int)$payment['order_id'];
		$payment_products[$order_id] = db_fetch_all("
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
	<h1 class="h4 mb-3">Payment Management</h1>
	
	<!-- Summary Cards -->
	<div class="row g-3 mb-4">
		<div class="col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="h5 mb-0"><?php 
						$total_paid = db_fetch_one("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'paid'")['total'] ?? 0;
						echo '₹' . number_format((float)$total_paid, 2);
					?></div>
					<div class="small text-muted">Total Paid</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="h5 mb-0"><?php 
						$total_pending = db_fetch_one("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'pending'")['total'] ?? 0;
						echo '₹' . number_format((float)$total_pending, 2);
					?></div>
					<div class="small text-muted">Pending</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="h5 mb-0"><?php 
						$total_failed = db_fetch_one("SELECT COUNT(*) as total FROM payments WHERE payment_status = 'failed'")['total'] ?? 0;
						echo (int)$total_failed;
					?></div>
					<div class="small text-muted">Failed Payments</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card text-center">
				<div class="card-body">
					<div class="h5 mb-0"><?php 
						$total_refunded = db_fetch_one("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'refunded'")['total'] ?? 0;
						echo '₹' . number_format((float)$total_refunded, 2);
					?></div>
					<div class="small text-muted">Refunded</div>
				</div>
			</div>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>#</th>
					<th>Order ID</th>
					<th>Customer</th>
					<th>Products</th>
					<th>Method</th>
					<th>Provider</th>
					<th>Amount</th>
					<th>Status</th>
					<th>Transaction ID</th>
					<th>Payment Date</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($payments as $p): 
					$order_id = (int)$p['order_id'];
					$products = $payment_products[$order_id] ?? [];
				?>
					<tr>
						<td><?php echo (int)$p['id']; ?></td>
						<td>
							<a href="/clothyyy/public/admin/orders.php" class="text-decoration-none">
								#<?php echo $order_id; ?>
							</a>
						</td>
						<td>
							<div><?php echo e($p['customer_name']); ?></div>
							<div class="small text-muted"><?php echo e($p['phone']); ?></div>
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
						<td>
							<span class="badge bg-secondary"><?php echo strtoupper(e($p['payment_method'])); ?></span>
						</td>
						<td>
							<?php if ($p['payment_provider']): ?>
								<span class="badge bg-info"><?php echo e($p['payment_provider']); ?></span>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<td>
							<strong>₹<?php echo number_format((float)$p['amount'], 2); ?></strong>
							<?php if ($p['amount'] != $p['order_total']): ?>
								<div class="small text-warning">Order: ₹<?php echo number_format((float)$p['order_total'], 2); ?></div>
							<?php endif; ?>
						</td>
						<td>
							<?php
								$status_class = [
									'paid' => 'success',
									'pending' => 'warning',
									'failed' => 'danger',
									'refunded' => 'info'
								];
								$status_badge = $status_class[$p['payment_status']] ?? 'secondary';
							?>
							<span class="badge bg-<?php echo $status_badge; ?>">
								<?php echo ucfirst(e($p['payment_status'])); ?>
							</span>
						</td>
						<td>
							<?php if ($p['transaction_id']): ?>
								<code class="small"><?php echo e($p['transaction_id']); ?></code>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($p['payment_date']): ?>
								<?php echo date('Y-m-d H:i', strtotime($p['payment_date'])); ?>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<td>
							<div class="small"><?php echo date('Y-m-d', strtotime($p['created_at'])); ?></div>
							<div class="small text-muted"><?php echo date('H:i', strtotime($p['created_at'])); ?></div>
						</td>
						<td>
							<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#p<?php echo (int)$p['id']; ?>">
								Update
							</button>
						</td>
					</tr>
					<tr class="collapse" id="p<?php echo (int)$p['id']; ?>">
						<td colspan="12">
							<div class="card bg-light">
								<div class="card-body">
									<form method="post" class="row g-3">
										<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
										<div class="col-md-3">
											<label class="form-label small mb-1">Payment Status</label>
											<select name="payment_status" class="form-select form-select-sm" required>
												<?php foreach (['pending', 'paid', 'failed', 'refunded'] as $ps): ?>
													<option value="<?php echo $ps; ?>" <?php echo $ps === $p['payment_status'] ? 'selected' : ''; ?>>
														<?php echo ucfirst($ps); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="col-md-6">
											<label class="form-label small mb-1">Notes</label>
											<input type="text" name="notes" class="form-control form-control-sm" 
												value="<?php echo e($p['notes'] ?? ''); ?>" 
												placeholder="Add payment notes...">
										</div>
										<div class="col-md-3 d-flex align-items-end">
											<button class="btn btn-sm btn-primary" name="update">Save Changes</button>
										</div>
										<?php if ($p['notes']): ?>
											<div class="col-12">
												<small class="text-muted">
													<strong>Current Notes:</strong> <?php echo e($p['notes']); ?>
												</small>
											</div>
										<?php endif; ?>
									</form>
								</div>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if (empty($payments)): ?>
					<tr>
						<td colspan="12" class="text-center text-muted py-4">No payments found.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


