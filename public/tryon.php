<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';
	require_once __DIR__ . '/../src/lib/auth.php';

	$user = auth_user();
	$prefill = isset($_GET['prefill']) ? (int)$_GET['prefill'] : 0;
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_tryon'])) {
		$name = trim($_POST['name'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		$product_id = (int)($_POST['product_id'] ?? 0);
		if ($name && $address && $phone && $product_id) {
			// Check that product is not a rental product
			$product = db_fetch_one("SELECT id, price, is_rentable, rental_only FROM products WHERE id = :id AND is_active = 1", [':id' => $product_id]);
			if ($product && !$product['is_rentable'] && !$product['rental_only']) {
				$charge = round($product['price'] * TRYON_DELIVERY_RATE, 2);
				db_execute("INSERT INTO tryons (product_id, customer_name, address, phone, delivery_charge, status, return_deadline, created_at) VALUES (:pid,:n,:a,:p,:charge,'scheduled', DATE_ADD(NOW(), INTERVAL :hrs HOUR), NOW())", [
					':pid' => $product_id,
					':n' => $name,
					':a' => $address,
					':p' => $phone,
					':charge' => $charge,
					':hrs' => TRYON_RETURN_HOURS_LIMIT
				]);
				$tid = (int)db_last_id();
				redirect('/clothyyy/public/tryon.php?placed=' . $tid);
			}
		}
	}
	// Exclude rental products (is_rentable = 1 OR rental_only = 1)
	$products = db_fetch_all("SELECT id, name, price, image_url FROM products WHERE is_active = 1 AND (is_rentable = 0 OR is_rentable IS NULL) AND (rental_only = 0 OR rental_only IS NULL) ORDER BY name ASC");
	$placed = isset($_GET['placed']) ? (int)$_GET['placed'] : 0;
	
	// Get tryon requests - only for logged-in user
	$user_tryons = [];
	if ($user) {
		// Match by customer name (user's name) or we could add user_id field later
		$user_tryons = db_fetch_all("SELECT t.*, p.name AS product_name FROM tryons t LEFT JOIN products p ON p.id = t.product_id WHERE t.customer_name = :name ORDER BY t.created_at DESC LIMIT 10", [
			':name' => $user['name']
		]);
	}
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Home Try-On</h1>
	<div class="row g-4">
		<div class="col-md-6">
			<div class="alert alert-warning">
				Return the clothes within <strong><?php echo TRYON_RETURN_HOURS_LIMIT; ?> hours</strong>. Delivery charge equals <strong>10% of the selected product price</strong>.
			</div>
			<?php if ($placed): ?>
				<div class="alert alert-success">Try-On request submitted! ID #<?php echo (int)$placed; ?>. Our delivery partner will contact you.</div>
			<?php endif; ?>
			
			<div class="mb-4">
				<label class="form-label fw-bold mb-3">Select Product</label>
				<div class="table-responsive">
					<table class="table table-hover align-middle">
						<thead>
							<tr>
								<th style="width: 100px;">Image</th>
								<th>Product Name</th>
								<th>Price</th>
								<th>Delivery Charge</th>
								<th style="width: 120px;">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($products as $p): ?>
								<?php $charge = round($p['price'] * TRYON_DELIVERY_RATE, 2); ?>
								<tr id="product-row-<?php echo (int)$p['id']; ?>" class="<?php echo $prefill === (int)$p['id'] ? 'table-primary' : ''; ?>">
									<td>
										<?php if (!empty($p['image_url'])): ?>
											<img src="<?php echo e($p['image_url']); ?>" alt="<?php echo e($p['name']); ?>" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
										<?php else: ?>
											<div class="bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
												<span class="text-muted small">No Image</span>
											</div>
										<?php endif; ?>
									</td>
									<td>
										<strong><?php echo e($p['name']); ?></strong>
									</td>
									<td>
										<span class="fw-semibold">₹<?php echo number_format((float)$p['price'], 2); ?></span>
									</td>
									<td>
										<span class="text-primary fw-semibold">₹<?php echo number_format($charge, 2); ?></span>
										<small class="text-muted d-block">(10% of price)</small>
									</td>
									<td>
										<button type="button" class="btn btn-sm btn-primary select-product-btn" 
												data-product-id="<?php echo (int)$p['id']; ?>"
												data-name="<?php echo e($p['name']); ?>"
												data-price="<?php echo e($p['price']); ?>"
												data-charge="<?php echo e($charge); ?>">
											Select
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div id="selectedProductInfo" class="alert alert-info mt-3" style="display: none;">
					<strong>Selected Product:</strong> <span id="selectedProductName"></span><br>
					<strong>Price:</strong> ₹<span id="selectedProductPrice"></span><br>
					<strong>Delivery Charge:</strong> ₹<span id="selectedProductCharge"></span>
				</div>
			</div>
			
			<form method="post" id="tryonForm">
				<input type="hidden" name="product_id" id="selectedProductId" required>
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
				<input type="hidden" name="request_tryon" value="1">
				<button class="btn btn-primary">Request Try-On</button>
			</form>
		</div>
		<div class="col-md-6">
			<h5>My Try-On Requests</h5>
			<?php if (!$user): ?>
				<div class="alert alert-info">
					Please <a href="/clothyyy/public/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">login</a> to view your try-on requests.
				</div>
			<?php elseif (empty($user_tryons)): ?>
				<div class="alert alert-secondary">
					You haven't made any try-on requests yet.
				</div>
			<?php else: ?>
				<div class="table-responsive">
					<table class="table table-sm">
						<thead><tr><th>ID</th><th>Product</th><th>Status</th><th>Deadline</th><th>Charge</th></tr></thead>
						<tbody>
							<?php foreach ($user_tryons as $t): ?>
								<tr>
									<td><?php echo (int)$t['id']; ?></td>
									<td><?php echo e($t['product_name']); ?></td>
									<td><?php echo e($t['status']); ?></td>
									<td><?php echo e($t['return_deadline']); ?></td>
									<td>₹<?php echo number_format((float)$t['delivery_charge'], 2); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<script>
let selectedProductId = null;

document.addEventListener('DOMContentLoaded', function() {
	// Handle select button clicks
	document.querySelectorAll('.select-product-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const productId = this.dataset.productId;
			const name = this.dataset.name;
			const price = parseFloat(this.dataset.price);
			const charge = parseFloat(this.dataset.charge);
			
			selectProduct(productId, name, price, charge);
		});
	});
	
	// Pre-select if prefill is set
	<?php if ($prefill > 0): ?>
		const prefillBtn = document.querySelector(`[data-product-id="<?php echo $prefill; ?>"]`);
		if (prefillBtn) {
			prefillBtn.click();
		}
	<?php endif; ?>
});

function selectProduct(id, name, price, charge) {
	// Remove previous selection highlighting
	document.querySelectorAll('tbody tr').forEach(row => {
		row.classList.remove('table-primary');
	});
	
	// Highlight selected row
	const selectedRow = document.getElementById(`product-row-${id}`);
	if (selectedRow) {
		selectedRow.classList.add('table-primary');
	}
	
	// Update selected product info
	document.getElementById('selectedProductId').value = id;
	document.getElementById('selectedProductName').textContent = name;
	document.getElementById('selectedProductPrice').textContent = price.toFixed(2);
	document.getElementById('selectedProductCharge').textContent = charge.toFixed(2);
	document.getElementById('selectedProductInfo').style.display = 'block';
	
	selectedProductId = id;
	
	// Scroll to form
	document.getElementById('tryonForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Form validation
document.getElementById('tryonForm')?.addEventListener('submit', function(e) {
	if (!selectedProductId) {
		e.preventDefault();
		alert('Please select a product first by clicking the "Select" button.');
		return false;
	}
});
</script>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>