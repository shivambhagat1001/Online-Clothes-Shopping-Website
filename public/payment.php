<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';

	// Ensure we have a cart and a pending order context
	$cart = $_SESSION['cart'] ?? [];
	if (!$cart) {
		redirect('/clothyyy/public/cart.php');
	}

	// Hydrate products and compute total
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

	// If coming from checkout, pending_order should exist
	if (!isset($_SESSION['pending_order'])) {
		// fallback to a minimal temp user input form
		$_SESSION['pending_order'] = [
			'name' => '',
			'address' => '',
			'phone' => '',
			'amount' => $total,
			'subtotal' => $subtotal,
			'shipping' => $shipping_charge,
		];
	} else {
		// Update amount to include shipping if not already set
		if (!isset($_SESSION['pending_order']['shipping'])) {
			$_SESSION['pending_order']['amount'] = $total;
			$_SESSION['pending_order']['subtotal'] = $subtotal;
			$_SESSION['pending_order']['shipping'] = $shipping_charge;
		}
	}
	$pending = $_SESSION['pending_order'];
	$amount = $pending['amount'] ?? $total;
	$shipping = $pending['shipping'] ?? $shipping_charge;

	// Confirm fake payment
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
		$name = trim($pending['name'] ?? '');
		$address = trim($pending['address'] ?? '');
		$phone = trim($pending['phone'] ?? '');
		$payment_provider = trim($_POST['payment_provider'] ?? 'gpay');
		if ($name && $address && $phone) {
			$user_id = null;
			if (isset($_SESSION['user']['id'])) {
				$user_id_val = (int)$_SESSION['user']['id'];
				// Validate that user exists in database
				$user_exists = db_fetch_one("SELECT id FROM users WHERE id = :id", [':id' => $user_id_val]);
				if ($user_exists) {
					$user_id = $user_id_val;
				}
			}
			db_execute("INSERT INTO orders (user_id, customer_name, address, phone, total_amount, payment_method, payment_status, status, created_at) VALUES (:uid,:n,:a,:p,:t,'upi','paid','processing',NOW())", [
				':uid' => $user_id, ':n' => $name, ':a' => $address, ':p' => $phone, ':t' => $amount
			]);
			$orderId = (int)db_last_id();
			foreach ($cart as $pid => $qty) {
				if (!isset($map[$pid])) continue;
				db_execute("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:o,:pid,:q,:price)", [
					':o' => $orderId, ':pid' => $pid, ':q' => $qty, ':price' => $map[$pid]['price']
				]);
			}
			// Insert payment record
			$transaction_id = 'TXN' . strtoupper(uniqid()) . time();
			db_execute("INSERT INTO payments (order_id, payment_method, payment_provider, payment_status, amount, transaction_id, payment_date, created_at) VALUES (:oid, 'upi', :provider, 'paid', :amt, :txn, NOW(), NOW())", [
				':oid' => $orderId,
				':provider' => $payment_provider,
				':amt' => $amount,
				':txn' => $transaction_id
			]);
			// Clear context
			session_cart_clear();
			unset($_SESSION['pending_order']);
			redirect('/clothyyy/public/orders.php?placed=' . $orderId);
		}
	}
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<style>
.upi-brands{display:flex;gap:8px;flex-wrap:wrap}
.upi-brand{border:1px solid #dee2e6;border-radius:10px;padding:8px 12px;background:#fff;cursor:pointer;display:flex;align-items:center;gap:8px}
.upi-brand.active{outline:2px solid var(--bs-primary)}
.qr-box{width:220px;height:220px;border:8px solid #111;border-radius:12px;background:repeating-linear-gradient(45deg,#000 0,#000 2px,#fff 2px,#fff 4px);position:relative;overflow:hidden}
.qr-pulse{position:absolute;inset:0;background:radial-gradient(circle at center, rgba(13,110,253,.15), transparent 60%);animation:pulse 2s infinite}
@keyframes pulse{0%{opacity:.6}50%{opacity:.15}100%{opacity:.6}}
.pay-wave{width:14px;height:14px;border-radius:50%;background:var(--bs-success);box-shadow:0 0 0 0 rgba(25,135,84,.7);animation:wave 1.8s infinite;margin-left:6px}
@keyframes wave{0%{box-shadow:0 0 0 0 rgba(25,135,84,.7)}70%{box-shadow:0 0 0 14px rgba(25,135,84,0)}100%{box-shadow:0 0 0 0 rgba(25,135,84,0)}}
.spinner-pay{width:22px;height:22px;border:3px solid #e9ecef;border-top-color:var(--bs-primary);border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.checkmark{width:64px;height:64px;border-radius:50%;background:#e8f5eb;position:relative;margin:0 auto 8px;border:2px solid #52c41a;display:none}
.checkmark::after{content:'';position:absolute;left:18px;top:28px;width:12px;height:24px;border:solid #52c41a;border-width:0 4px 4px 0;transform:rotate(45deg)}
.success .checkmark{display:block;animation:pop .4s ease}
@keyframes pop{0%{transform:scale(.8)}60%{transform:scale(1.05)}100%{transform:scale(1)}}
</style>
<div class="container py-4">
	<div class="row g-4">
		<div class="col-lg-7">
			<div class="card">
				<div class="card-body">
					<h1 class="h5 mb-3">Pay with UPI</h1>
					<div class="d-flex align-items-center justify-content-between mb-3">
						<div class="d-flex align-items-center">
							<strong class="me-2">Amount:</strong>
							<div class="h4 mb-0">₹<?php echo number_format($amount, 2); ?></div>
							<div class="pay-wave" title="secure"></div>
						</div>
						<div class="text-muted small">Pay to: <strong>Clothyyy@upi</strong></div>
					</div>
					<div class="mb-3">
						<div class="upi-brands" id="brandList">
							<div class="upi-brand active" data-brand="gpay"><img src="https://img.icons8.com/color/24/google-pay.png" alt=""> GPay</div>
							<div class="upi-brand" data-brand="paytm"><img src="https://img.icons8.com/color/24/paytm.png" alt=""> Paytm</div>
							<div class="upi-brand" data-brand="phonepe"><img src="https://img.icons8.com/color/24/phonepe.png" alt=""> PhonePe</div>
							<div class="upi-brand" data-brand="razorpay"><img src="https://img.icons8.com/fluency/24/razorpay.png" alt=""> Razorpay</div>
						</div>
					</div>
					<div class="row g-3">
						<div class="col-md-5">
							<div class="d-flex flex-column align-items-center">
								<div class="qr-box mb-2">
									<div class="qr-pulse"></div>
								</div>
								<div class="small text-muted">Scan to pay using your UPI app</div>
							</div>
						</div>
						<div class="col-md-7">
							<div class="mb-3">
								<label class="form-label">Or pay via UPI ID</label>
								<div class="input-group">
									<input class="form-control" id="upiId" placeholder="you@bank">
									<span class="input-group-text">✓</span>
								</div>
								<div class="form-text">We'll simulate a successful collect request.</div>
							</div>
							<div class="d-flex align-items-center gap-3">
								<button class="btn btn-success" id="payNowBtn">
									<span class="me-2">Pay Now</span>
									<span class="spinner-pay d-none" id="spinner"></span>
								</button>
								<a href="/clothyyy/public/checkout.php" class="btn btn-outline-secondary">Back</a>
							</div>
							<form method="post" id="confirmForm" class="d-none">
								<input type="hidden" name="confirm_payment" value="1">
								<input type="hidden" name="payment_provider" id="paymentProvider" value="gpay">
							</form>
						</div>
					</div>
					<hr class="my-4">
					<div id="statusArea" class="text-center">
						<div class="checkmark"></div>
						<div class="fw-semibold" id="statusText">Awaiting payment…</div>
						<div class="small text-muted">Do not refresh this page while processing.</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Order Summary</h5>
					<ul class="list-group list-group-flush">
						<?php foreach ($cart as $pid => $qty): if (!isset($map[$pid])) continue; ?>
							<li class="list-group-item d-flex justify-content-between">
								<span><?php echo e($map[$pid]['name']); ?> × <?php echo (int)$qty; ?></span>
								<span>₹<?php echo number_format($map[$pid]['price'] * $qty, 2); ?></span>
							</li>
						<?php endforeach; ?>
						<li class="list-group-item d-flex justify-content-between">
							<span class="text-muted">Subtotal:</span>
							<span>₹<?php echo number_format($subtotal, 2); ?></span>
						</li>
						<li class="list-group-item d-flex justify-content-between">
							<span class="text-muted">Shipping:</span>
							<?php if ($shipping == 0): ?>
								<span class="text-success">FREE</span>
							<?php else: ?>
								<span>₹<?php echo number_format($shipping, 2); ?></span>
							<?php endif; ?>
						</li>
						<li class="list-group-item d-flex justify-content-between">
							<strong>Total</strong>
							<strong>₹<?php echo number_format($amount, 2); ?></strong>
						</li>
					</ul>
					<div class="mt-3 small text-muted">
						Paid via UPI (demo). This simulates providers like GPay, Razorpay, Paytm.
					</div>
				</div>
			</div>
			<div class="card mt-3">
				<div class="card-body">
					<h6 class="mb-1">Billing to</h6>
					<div><?php echo e($pending['name']); ?></div>
					<div class="text-muted small"><?php echo nl2br(e($pending['address'])); ?></div>
					<div class="text-muted small">Phone: <?php echo e($pending['phone']); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
(function(){
	const brands = document.querySelectorAll('.upi-brand');
	const payBtn = document.getElementById('payNowBtn');
	const spinner = document.getElementById('spinner');
	const statusText = document.getElementById('statusText');
	const statusArea = document.getElementById('statusArea');
	const form = document.getElementById('confirmForm');
	brands.forEach(b=>{
		b.addEventListener('click', ()=>{
			brands.forEach(x=>x.classList.remove('active'));
			b.classList.add('active');
			document.getElementById('paymentProvider').value = b.dataset.brand;
		});
	});
	payBtn.addEventListener('click', ()=>{
		spinner.classList.remove('d-none');
		payBtn.disabled = true;
		statusText.textContent = 'Processing payment via ' + (document.querySelector('.upi-brand.active')?.dataset.brand || 'upi') + '…';
		setTimeout(()=>{
			statusArea.classList.add('success');
			statusText.textContent = 'Payment successful!';
			setTimeout(()=>form.submit(), 700);
		}, 1800);
	});
})();
</script>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


