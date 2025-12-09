<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
		db_execute("UPDATE rentals SET status=:s, damage_fee=:df WHERE id=:id", [
			':s' => $_POST['status'],
			':df' => (float)($_POST['damage_fee'] ?? 0),
			':id' => (int)$_POST['id']
		]);
		redirect('/clothyyy/public/admin/rentals.php');
	}
	$rows = db_fetch_all("SELECT r.*, p.name AS product_name, p.image_url AS product_image_url FROM rentals r LEFT JOIN products p ON p.id = r.product_id ORDER BY r.created_at DESC");
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Rentals</h1>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th>ID</th><th>Image</th><th>Product</th><th>Customer</th><th>Days</th><th>Rent Fee</th><th>Deposit</th><th>Status</th><th>Damage Fee</th><th></th></tr></thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><?php echo (int)$r['id']; ?></td>
						<td>
							<?php if (!empty($r['product_image_url'])): ?>
								<img src="<?php echo e($r['product_image_url']); ?>" alt="<?php echo e($r['product_name']); ?>" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;" onerror="this.style.display='none'">
							<?php else: ?>
								<span class="text-muted small">No image</span>
							<?php endif; ?>
						</td>
						<td><?php echo e($r['product_name']); ?></td>
						<td><?php echo e($r['customer_name']); ?></td>
						<td><?php echo (int)$r['days']; ?></td>
						<td>₹<?php echo number_format((float)$r['rent_fee'], 2); ?></td>
						<td>₹<?php echo number_format((float)$r['deposit'], 2); ?></td>
						<td><?php echo e($r['status']); ?></td>
						<td>₹<?php echo number_format((float)($r['damage_fee'] ?? 0), 2); ?></td>
						<td>
							<form method="post" class="d-flex gap-2 align-items-end">
								<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
								<div>
									<label class="form-label small mb-1">Status</label>
									<select name="status" class="form-select form-select-sm">
										<?php foreach (['active','returned','closed','cancelled'] as $s): ?>
											<option <?php echo $s===$r['status']?'selected':''; ?>><?php echo $s; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div>
									<label class="form-label small mb-1">Damage Fee</label>
									<input name="damage_fee" type="number" step="0.01" class="form-control form-control-sm" value="<?php echo e($r['damage_fee'] ?? 0); ?>" style="max-width:120px">
								</div>
								<button class="btn btn-sm btn-primary" name="update">Save</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


