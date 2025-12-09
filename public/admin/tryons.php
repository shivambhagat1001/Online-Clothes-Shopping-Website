<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
		db_execute("UPDATE tryons SET status=:s WHERE id=:id", [
			':s' => $_POST['status'],
			':id' => (int)$_POST['id']
		]);
		redirect('/clothyyy/public/admin/tryons.php');
	}
	$rows = db_fetch_all("SELECT t.*, p.name AS product_name, p.image_url AS product_image FROM tryons t LEFT JOIN products p ON p.id = t.product_id ORDER BY t.created_at DESC");
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Try-Ons</h1>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th>ID</th><th>Product</th><th>Customer</th><th>Charge</th><th>Status</th><th>Deadline</th><th></th></tr></thead>
			<tbody>
				<?php foreach ($rows as $t): ?>
					<tr>
						<td><?php echo (int)$t['id']; ?></td>
						<td>
							<div class="d-flex align-items-center gap-2">
								<?php if (!empty($t['product_image'])): ?>
									<img src="<?php echo e($t['product_image']); ?>" 
										alt="<?php echo e($t['product_name']); ?>" 
										style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
								<?php endif; ?>
								<span><?php echo e($t['product_name']); ?></span>
							</div>
						</td>
						<td><?php echo e($t['customer_name']); ?></td>
						<td>₹<?php echo number_format((float)$t['delivery_charge'], 2); ?></td>
						<td><?php echo e($t['status']); ?></td>
						<td><?php echo e($t['return_deadline']); ?></td>
						<td>
							<form method="post" class="d-flex gap-2">
								<input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
								<select name="status" class="form-select form-select-sm" style="max-width:160px">
									<?php foreach (['scheduled','delivered','returned','late','cancelled'] as $s): ?>
										<option <?php echo $s===$t['status']?'selected':''; ?>><?php echo $s; ?></option>
									<?php endforeach; ?>
								</select>
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


