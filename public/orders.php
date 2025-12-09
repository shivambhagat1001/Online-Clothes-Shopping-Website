<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';
	require_once __DIR__ . '/../src/lib/auth.php';

	// Require user to be logged in
	$user = auth_user();
	if (!$user) {
		header('Location: /clothyyy/public/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
		exit;
	}

	// Handle order cancellation
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
		$order_id = (int)($_POST['order_id'] ?? 0);
		if ($order_id > 0) {
			// Verify order belongs to user and is not delivered
			$order = db_fetch_one("SELECT id, status FROM orders WHERE id = :id AND user_id = :uid", [
				':id' => $order_id,
				':uid' => $user['id']
			]);
			
			if ($order && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled') {
				db_execute("UPDATE orders SET status = 'cancelled' WHERE id = :id", [
					':id' => $order_id
				]);
				$success = 'Order #' . $order_id . ' has been cancelled successfully.';
			} else {
				$error = 'Order cannot be cancelled. It may already be delivered or cancelled.';
			}
		}
	}

	// Only fetch orders for the logged-in user with user email
	$orders = db_fetch_all("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.user_id = :uid ORDER BY o.created_at DESC", [
		':uid' => $user['id']
	]);
	
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
	
	$placed = isset($_GET['placed']) ? (int)$_GET['placed'] : 0;
	$success = isset($success) ? $success : '';
	$error = isset($error) ? $error : '';
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-2">
		<h1 class="h4 mb-0">Your Orders</h1>
	</div>
	<?php if ($placed): ?>
		<div class="alert alert-success">Order placed successfully!</div>
	<?php endif; ?>
	<?php if ($success): ?>
		<div class="alert alert-success alert-dismissible fade show">
			<?php echo e($success); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	<?php if ($error): ?>
		<div class="alert alert-danger alert-dismissible fade show">
			<?php echo e($error); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	<?php if (empty($orders)): ?>
		<div class="alert alert-info">
			<p class="mb-0">You haven't placed any orders yet.</p>
			<a href="/clothyyy/public/products.php" class="btn btn-primary btn-sm mt-2">Browse Products</a>
		</div>
	<?php else: ?>
		<div class="table-responsive">
			<table class="table table-striped align-middle">
				<thead>
					<tr>
						<th>#</th>
						<th>Email</th>
						<th>Address</th>
						<th>Payment Method</th>
						<th>Products</th>
						<th>Total</th>
						<th>Payment</th>
						<th>Status</th>
						<th>Placed</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($orders as $o): 
						$order_id = (int)$o['id'];
						$products = $order_products[$order_id] ?? [];
					?>
						<tr>
							<td><?php echo $order_id; ?></td>
							<td>
								<small><?php echo e($o['email'] ?? 'N/A'); ?></small>
							</td>
							<td>
								<small class="text-muted" style="max-width: 200px; display: block;">
									<?php echo nl2br(e($o['address'])); ?>
								</small>
							</td>
							<td>
								<span class="badge bg-info">
									<?php echo strtoupper(e($o['payment_method'])); ?>
								</span>
							</td>
							<td>
								<?php if (!empty($products)): ?>
									<div class="small">
										<?php foreach ($products as $item): ?>
											<div class="mb-2 d-flex align-items-center gap-2">
												<?php if (!empty($item['image_url'])): ?>
													<img src="<?php echo e($item['image_url']); ?>" 
														alt="<?php echo e($item['product_name']); ?>" 
														style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
												<?php else: ?>
													<div class="bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 4px;">
														<span class="text-muted" style="font-size: 0.7em;">No Img</span>
													</div>
												<?php endif; ?>
												<div>
													<strong><?php echo e($item['product_name']); ?></strong>
													<div class="text-muted" style="font-size: 0.85em;">
														Qty: <?php echo (int)$item['quantity']; ?> × ₹<?php echo number_format((float)$item['item_price'], 2); ?>
													</div>
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
								<span class="badge bg-<?php 
									echo $o['payment_status'] === 'paid' ? 'success' : 
										($o['payment_status'] === 'pending' ? 'warning' : 
										($o['payment_status'] === 'failed' ? 'danger' : 'secondary')); 
								?>">
									<?php echo e($o['payment_status']); ?>
								</span>
							</td>
							<td>
								<span class="badge bg-<?php 
									echo $o['status'] === 'delivered' ? 'success' : 
										($o['status'] === 'shipped' ? 'info' : 
										($o['status'] === 'processing' ? 'primary' : 
										($o['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
								?>">
									<?php echo e($o['status']); ?>
								</span>
							</td>
							<td><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></td>
							<td>
								<div class="d-flex flex-column gap-1">
									<?php if ($o['payment_status'] === 'paid'): ?>
										<a href="/clothyyy/public/orders/bill.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-success" target="_blank">
											📄 Download Bill
										</a>
									<?php endif; ?>
									<?php if ($o['status'] !== 'delivered' && $o['status'] !== 'cancelled'): ?>
										<form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel order #<?php echo $order_id; ?>?');">
											<input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
											<button type="submit" name="cancel_order" class="btn btn-sm btn-outline-danger">Cancel</button>
										</form>
									<?php else: ?>
										<span class="text-muted small">N/A</span>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


