<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['create'])) {
			$name = trim($_POST['name'] ?? '');
			$gender = $_POST['gender'] ?? null;
			if ($gender && !in_array($gender, ['men', 'women'])) $gender = null;
			db_execute("INSERT INTO categories (name, gender) VALUES (:n, :g)", [
				':n' => $name,
				':g' => $gender
			]);
			redirect('/clothyyy/public/admin/categories.php');
		}
		if (isset($_POST['update'])) {
			$name = trim($_POST['name'] ?? '');
			$gender = $_POST['gender'] ?? null;
			if ($gender && !in_array($gender, ['men', 'women'])) $gender = null;
			db_execute("UPDATE categories SET name=:n, gender=:g WHERE id=:id", [
				':n' => $name,
				':g' => $gender,
				':id' => (int)$_POST['id']
			]);
			redirect('/clothyyy/public/admin/categories.php');
		}
		if (isset($_POST['delete'])) {
			db_execute("DELETE FROM categories WHERE id=:id", [':id' => (int)$_POST['id']]);
			redirect('/clothyyy/public/admin/categories.php');
		}
	}
	
	// Fetch categories grouped by gender
	$allCategories = db_fetch_all("SELECT * FROM categories ORDER BY gender ASC, name ASC");
	$menCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'men'; });
	$womenCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'women'; });
	$otherCategories = array_filter($allCategories, function($c) { return empty($c['gender']); });
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Categories by Gender</h1>
	
	<div class="row g-4 mb-4">
		<div class="col-md-4">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Create Category</h5>
					<form method="post">
						<div class="mb-3">
							<label class="form-label">Category Name <span class="text-danger">*</span></label>
							<input name="name" class="form-control" placeholder="e.g., T-Shirts, Dresses" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Gender</label>
							<select name="gender" class="form-select">
								<option value="">Select Gender</option>
								<option value="men">Men</option>
								<option value="women">Women</option>
							</select>
						</div>
						<button class="btn btn-primary w-100" name="create">Add Category</button>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-8">
			<!-- Men Categories -->
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h5 class="mb-0">👔 Men's Categories</h5>
				</div>
				<div class="card-body">
					<?php if (empty($menCategories)): ?>
						<p class="text-muted mb-0">No men's categories yet.</p>
					<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
										<th>ID</th>
										<th>Name</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($menCategories as $c): ?>
										<tr>
											<td><?php echo (int)$c['id']; ?></td>
											<td><strong><?php echo e($c['name']); ?></strong></td>
											<td>
												<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#c<?php echo (int)$c['id']; ?>">Edit</button>
												<form method="post" class="d-inline">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<button name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Delete</button>
												</form>
											</td>
										</tr>
										<tr class="collapse" id="c<?php echo (int)$c['id']; ?>">
											<td colspan="3">
												<form method="post" class="d-flex gap-2 align-items-end">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<div class="flex-grow-1">
														<label class="form-label small">Name</label>
														<input name="name" class="form-control form-control-sm" value="<?php echo e($c['name']); ?>" required>
													</div>
													<div style="width: 150px;">
														<label class="form-label small">Gender</label>
														<select name="gender" class="form-select form-select-sm">
															<option value="">None</option>
															<option value="men" <?php echo $c['gender'] === 'men' ? 'selected' : ''; ?>>Men</option>
															<option value="women" <?php echo $c['gender'] === 'women' ? 'selected' : ''; ?>>Women</option>
														</select>
													</div>
													<button class="btn btn-sm btn-primary" name="update">Save</button>
												</form>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Women Categories -->
			<div class="card mb-4">
				<div class="card-header bg-danger text-white">
					<h5 class="mb-0">👗 Women's Categories</h5>
				</div>
				<div class="card-body">
					<?php if (empty($womenCategories)): ?>
						<p class="text-muted mb-0">No women's categories yet.</p>
					<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
										<th>ID</th>
										<th>Name</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($womenCategories as $c): ?>
										<tr>
											<td><?php echo (int)$c['id']; ?></td>
											<td><strong><?php echo e($c['name']); ?></strong></td>
											<td>
												<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#c<?php echo (int)$c['id']; ?>">Edit</button>
												<form method="post" class="d-inline">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<button name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Delete</button>
												</form>
											</td>
										</tr>
										<tr class="collapse" id="c<?php echo (int)$c['id']; ?>">
											<td colspan="3">
												<form method="post" class="d-flex gap-2 align-items-end">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<div class="flex-grow-1">
														<label class="form-label small">Name</label>
														<input name="name" class="form-control form-control-sm" value="<?php echo e($c['name']); ?>" required>
													</div>
													<div style="width: 150px;">
														<label class="form-label small">Gender</label>
														<select name="gender" class="form-select form-select-sm">
															<option value="">None</option>
															<option value="men" <?php echo $c['gender'] === 'men' ? 'selected' : ''; ?>>Men</option>
															<option value="women" <?php echo $c['gender'] === 'women' ? 'selected' : ''; ?>>Women</option>
														</select>
													</div>
													<button class="btn btn-sm btn-primary" name="update">Save</button>
												</form>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Other Categories (no gender) -->
			<?php if (!empty($otherCategories)): ?>
				<div class="card">
					<div class="card-header bg-secondary text-white">
						<h5 class="mb-0">📦 Other Categories</h5>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
										<th>ID</th>
										<th>Name</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($otherCategories as $c): ?>
										<tr>
											<td><?php echo (int)$c['id']; ?></td>
											<td><?php echo e($c['name']); ?></td>
											<td>
												<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#c<?php echo (int)$c['id']; ?>">Edit</button>
												<form method="post" class="d-inline">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<button name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Delete</button>
												</form>
											</td>
										</tr>
										<tr class="collapse" id="c<?php echo (int)$c['id']; ?>">
											<td colspan="3">
												<form method="post" class="d-flex gap-2 align-items-end">
													<input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
													<div class="flex-grow-1">
														<label class="form-label small">Name</label>
														<input name="name" class="form-control form-control-sm" value="<?php echo e($c['name']); ?>" required>
													</div>
													<div style="width: 150px;">
														<label class="form-label small">Gender</label>
														<select name="gender" class="form-select form-select-sm">
															<option value="">None</option>
															<option value="men" <?php echo $c['gender'] === 'men' ? 'selected' : ''; ?>>Men</option>
															<option value="women" <?php echo $c['gender'] === 'women' ? 'selected' : ''; ?>>Women</option>
														</select>
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
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


