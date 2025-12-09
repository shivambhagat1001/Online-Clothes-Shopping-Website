<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';
	require_once __DIR__ . '/../src/lib/auth.php';

	// Require user to be logged in
	if (!auth_user()) {
		redirect('/clothyyy/public/auth/login.php?redirect=' . urlencode('/clothyyy/public/checkout.php'));
	}

	$cart = session_cart();
	if (!$cart) {
		redirect('/clothyyy/public/cart.php');
	}
	$productIds = array_keys($cart);
	$in = implode(',', array_fill(0, count($productIds), '?'));
	$rows = db_fetch_all("SELECT id, name, price FROM products WHERE id IN ($in)", $productIds);
	$map = [];
	foreach ($rows as $r) $map[$r['id']] = $r;
	$subtotal = 0.0;
	foreach ($cart as $pid => $qty) {
		if (!isset($map[$pid])) continue;
		$subtotal += $map[$pid]['price'] * $qty;
	}

	// Calculate shipping charges: Free for orders ≥₹1499, else 10% of subtotal
	$shipping_charge = 0.0;
	if ($subtotal < 1499) {
		$shipping_charge = $subtotal * 0.10;
	}
	$total = $subtotal + $shipping_charge;

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
		$name = trim($_POST['name'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		$payment_method = $_POST['payment_method'] ?? 'upi';
		if ($name && $address && $phone) {
			// If UPI selected, forward to payment page for fake provider UI
			if ($payment_method === 'upi') {
				$_SESSION['pending_order'] = [
					'name' => $name,
					'address' => $address,
					'phone' => $phone,
					'amount' => $total,
					'subtotal' => $subtotal,
					'shipping' => $shipping_charge,
				];
				redirect('/clothyyy/public/payment.php');
			} else {
				// COD: record as pending payment
				$user_id = null;
				if (isset($_SESSION['user']['id'])) {
					$user_id_val = (int)$_SESSION['user']['id'];
					// Validate that user exists in database
					$user_exists = db_fetch_one("SELECT id FROM users WHERE id = :id", [':id' => $user_id_val]);
					if ($user_exists) {
						$user_id = $user_id_val;
					}
				}
				db_execute("INSERT INTO orders (user_id, customer_name, address, phone, total_amount, payment_method, payment_status, status, created_at) VALUES (:uid,:n,:a,:p,:t,:pm,'pending','processing',NOW())", [
					':uid' => $user_id, ':n' => $name, ':a' => $address, ':p' => $phone, ':t' => $total, ':pm' => $payment_method
				]);
				$orderId = (int)db_last_id();
				foreach ($cart as $pid => $qty) {
					if (!isset($map[$pid])) continue;
					db_execute("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:o,:pid,:q,:price)", [
						':o' => $orderId, ':pid' => $pid, ':q' => $qty, ':price' => $map[$pid]['price']
					]);
				}
				// Insert payment record for COD
				db_execute("INSERT INTO payments (order_id, payment_method, payment_provider, payment_status, amount, notes, created_at) VALUES (:oid, 'cod', NULL, 'pending', :amt, 'Cash on Delivery - Payment pending', NOW())", [
					':oid' => $orderId,
					':amt' => $total
				]);
				session_cart_clear();
				redirect('/clothyyy/public/orders.php?placed=' . $orderId);
			}
		}
	}
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-5">
	<div class="section-header">
		<h2>💳 Checkout</h2>
	</div>
	<div class="row g-4">
		<div class="col-md-7">
			<div class="card">
				<div class="card-body p-4">
					<h5 class="card-title mb-4">Shipping Information</h5>
					<form method="post" id="checkoutForm">
						<div class="mb-3">
							<label class="form-label">Full Name <span class="text-danger">*</span></label>
							<input name="name" class="form-control" required placeholder="Enter your full name">
						</div>
						<div class="mb-3">
							<label class="form-label">Delivery Address <span class="text-danger">*</span></label>
							<textarea name="address" class="form-control" rows="4" required placeholder="Enter your complete address"></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Phone Number <span class="text-danger">*</span></label>
							<input name="phone" type="tel" class="form-control" required placeholder="Enter your phone number">
						</div>
						<div class="mb-4">
							<label class="form-label">Payment Method <span class="text-danger">*</span></label>
							<select name="payment_method" class="form-select" id="paymentMethod">
								<option value="upi">💳 UPI Payment (Demo)</option>
								<option value="cod">💰 Cash on Delivery</option>
							</select>
						</div>
						<input type="hidden" name="place_order" value="1">
						<button class="btn btn-primary btn-lg w-100" id="payBtn">
							<span id="btnText">Continue to Payment</span>
						</button>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-5">
			<div class="card sticky-top" style="top: 100px;">
				<div class="card-body p-4">
					<h5 class="card-title mb-4">Order Summary</h5>
					<div class="d-flex justify-content-between mb-3">
						<span class="text-muted">Subtotal:</span>
						<span class="fw-semibold">₹<?php echo number_format($subtotal, 2); ?></span>
					</div>
					<div class="d-flex justify-content-between mb-3">
						<span class="text-muted">Shipping:</span>
						<?php if ($shipping_charge == 0): ?>
							<span class="fw-semibold text-success">FREE</span>
						<?php else: ?>
							<span class="fw-semibold">₹<?php echo number_format($shipping_charge, 2); ?></span>
						<?php endif; ?>
					</div>
					<?php if ($shipping_charge > 0): ?>
						<div class="alert alert-info small mb-3">
							<strong>💡 Tip:</strong> Get FREE shipping on orders above ₹1499!
						</div>
					<?php endif; ?>
					<hr>
					<div class="d-flex justify-content-between mb-3">
						<span class="fs-5 fw-bold">Total:</span>
						<span class="fs-5 fw-bold price">₹<?php echo number_format($total, 2); ?></span>
					</div>
					<div class="alert alert-info small mb-0">
						<!-- <strong>Note:</strong> UPI payment is a demo. No actual payment will be processed. -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	const method = document.getElementById('paymentMethod');
	const payBtn = document.getElementById('payBtn');
	const form = document.getElementById('checkoutForm');
	payBtn.addEventListener('click', function(e) {
		// Let form post to checkout for COD; for UPI we redirect server-side after storing pending_order
	});
})();
</script>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


