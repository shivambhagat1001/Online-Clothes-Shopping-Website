<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$product = db_fetch_one("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = :id AND p.is_active = 1", [':id' => $id]);
	if (!$product) {
		http_response_code(404);
		die('Product not found');
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
		session_cart_add($id, (int)($_POST['qty'] ?? 1));
		redirect('/clothyyy/public/cart.php');
	}
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-5">
	<div class="row g-4">
		<div class="col-md-6">
			<div class="card" style="overflow: hidden;">
				<?php if (!empty($product['image_url'])): ?>
					<img src="<?php echo e($product['image_url']); ?>" class="img-fluid" alt="<?php echo e($product['name']); ?>" style="width: 100%; height: auto;">
				<?php else: ?>
					<div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
						<span class="text-muted">No Image Available</span>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card p-4">
				<h1 class="h2 mb-2"><?php echo e($product['name']); ?></h1>
				<p class="text-muted mb-3">
					<span class="badge bg-primary"><?php echo e($product['category_name'] ?? 'Uncategorized'); ?></span>
				</p>
				<div class="mb-4">
					<span class="price">₹<?php echo number_format((float)$product['price'], 2); ?></span>
				</div>
				<?php if (!empty($product['description'])): ?>
					<div class="mb-4">
						<h5 class="h6 mb-2">Description</h5>
						<p class="text-muted"><?php echo nl2br(e($product['description'])); ?></p>
					</div>
				<?php endif; ?>
				<form method="post" class="d-flex align-items-center gap-2 mb-3">
					<input type="hidden" name="add_cart" value="1">
					<label class="form-label mb-0">Quantity:</label>
					<input type="number" name="qty" value="1" min="1" class="form-control" style="max-width:100px">
					<button class="btn btn-primary flex-grow-1" data-add-cart>🛒 Add to Cart</button>
				</form>
				<div class="d-flex gap-2 flex-wrap">
					<a href="/clothyyy/public/tryon.php?prefill=<?php echo (int)$product['id']; ?>" class="btn btn-outline-secondary">🏠 Request Home Try-On</a>
					<a href="/clothyyy/public/products.php" class="btn btn-outline-primary">← Back to Shop</a>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


