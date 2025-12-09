<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	$error = '';
	$success = '';

	// Simple CRUD for products
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['create'])) {
			$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
			$is_rentable = isset($_POST['is_rentable']) ? (int)$_POST['is_rentable'] : 0;
			$rental_only = isset($_POST['rental_only']) ? (int)$_POST['rental_only'] : 0;
			
			// Handle image upload (priority over URL)
			$image_url = '';
			if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
				$uploaded_url = upload_image($_FILES['image_file']);
				if ($uploaded_url) {
					$image_url = $uploaded_url;
				} else {
					$error = 'Failed to upload image. Please check file type (JPEG, PNG, GIF, WebP) and size (max 5MB).';
				}
			} else {
				// Fallback to URL if no file uploaded
				$image_url = trim($_POST['image_url'] ?? '');
			}
			
			if (empty($error)) {
				db_execute("INSERT INTO products (name, description, price, image_url, category_id, is_active, is_rentable, rental_only, created_at) VALUES (:n,:d,:p,:img,:cid,:ia,:ir,:ro,NOW())", [
					':n' => trim($_POST['name'] ?? ''),
					':d' => trim($_POST['description'] ?? ''),
					':p' => (float)($_POST['price'] ?? 0),
					':img' => $image_url,
					':cid' => (int)($_POST['category_id'] ?? 0),
					':ia' => $is_active,
					':ir' => $is_rentable,
					':ro' => $rental_only,
				]);
				$success = 'Product created successfully!';
			}
		}
		if (isset($_POST['update'])) {
			$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
			$is_rentable = isset($_POST['is_rentable']) ? (int)$_POST['is_rentable'] : 0;
			$rental_only = isset($_POST['rental_only']) ? (int)$_POST['rental_only'] : 0;
			$product_id = (int)$_POST['id'];
			
			// Get current product to check for old image
			$current_product = db_fetch_one("SELECT image_url FROM products WHERE id = :id", [':id' => $product_id]);
			$old_image_url = $current_product['image_url'] ?? '';
			
			// Handle image upload (priority over URL)
			$image_url = $old_image_url; // Keep old image by default
			if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
				$uploaded_url = upload_image($_FILES['image_file']);
				if ($uploaded_url) {
					// Delete old uploaded image if it exists
					if (!empty($old_image_url) && strpos($old_image_url, UPLOAD_URL) === 0) {
						delete_uploaded_image($old_image_url);
					}
					$image_url = $uploaded_url;
				} else {
					$error = 'Failed to upload image. Please check file type (JPEG, PNG, GIF, WebP) and size (max 5MB).';
				}
			} elseif (!empty(trim($_POST['image_url'] ?? ''))) {
				// If URL is provided and no file uploaded, use URL
				$new_url = trim($_POST['image_url']);
				// Delete old uploaded image if switching to URL
				if (!empty($old_image_url) && strpos($old_image_url, UPLOAD_URL) === 0 && $new_url !== $old_image_url) {
					delete_uploaded_image($old_image_url);
				}
				$image_url = $new_url;
			}
			
			if (empty($error)) {
				db_execute("UPDATE products SET name=:n, description=:d, price=:p, image_url=:img, category_id=:cid, is_active=:ia, is_rentable=:ir, rental_only=:ro WHERE id=:id", [
					':n' => trim($_POST['name'] ?? ''),
					':d' => trim($_POST['description'] ?? ''),
					':p' => (float)($_POST['price'] ?? 0),
					':img' => $image_url,
					':cid' => (int)($_POST['category_id'] ?? 0),
					':ia' => $is_active,
					':ir' => $is_rentable,
					':ro' => $rental_only,
					':id' => $product_id
				]);
				$success = 'Product updated successfully!';
			}
		}
		if (isset($_POST['delete'])) {
			$product_id = (int)$_POST['id'];
			$product = db_fetch_one("SELECT image_url FROM products WHERE id = :id", [':id' => $product_id]);
			if ($product && !empty($product['image_url']) && strpos($product['image_url'], UPLOAD_URL) === 0) {
				delete_uploaded_image($product['image_url']);
			}
			db_execute("DELETE FROM products WHERE id=:id", [':id' => $product_id]);
			$success = 'Product deleted successfully!';
		}
		
		if (empty($error)) {
			redirect('/clothyyy/public/admin/products.php');
		}
	}

	$products = db_fetch_all("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC");
	$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Products</h1>
	
	<?php if ($error): ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<?php echo e($error); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	
	<?php if ($success): ?>
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			<?php echo e($success); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	
	<div class="row g-4">
		<div class="col-md-5">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Create Product</h5>
					<form method="post" enctype="multipart/form-data">
						<div class="mb-2"><input name="name" class="form-control" placeholder="Name" required></div>
						<div class="mb-2"><textarea name="description" class="form-control" placeholder="Description"></textarea></div>
						<div class="mb-2"><input name="price" type="number" step="0.01" class="form-control" placeholder="Price" required></div>
						
						<div class="mb-2">
							<label class="form-label small">Upload Image from Device</label>
							<input name="image_file" type="file" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="createImageFile">
							<small class="text-muted">Max 5MB. Formats: JPEG, PNG, GIF, WebP</small>
							<div id="createImagePreview" class="mt-2" style="display: none;">
								<img id="createPreviewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
							</div>
						</div>
						
						<div class="mb-2">
							<label class="form-label small">Or Enter Image URL</label>
							<input name="image_url" class="form-control" placeholder="Image URL (if not uploading file)">
							<small class="text-muted">Leave empty if uploading file above</small>
						</div>
						<div class="mb-2">
							<select name="category_id" class="form-select">
								<option value="0">No category</option>
								<?php foreach ($categories as $c): ?>
									<option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['name']); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-check mb-2">
							<input type="hidden" name="is_active" value="0">
							<input class="form-check-input" type="checkbox" name="is_active" id="ia" value="1" checked>
							<label class="form-check-label" for="ia">Active</label>
						</div>
						<div class="form-check mb-2">
							<input type="hidden" name="is_rentable" value="0">
							<input class="form-check-input" type="checkbox" name="is_rentable" id="ir" value="1">
							<label class="form-check-label" for="ir">Rentable</label>
						</div>
						<div class="form-check mb-3">
							<input type="hidden" name="rental_only" value="0">
							<input class="form-check-input" type="checkbox" name="rental_only" id="ro" value="1">
							<label class="form-check-label" for="ro">Only shows in rental section (not in main home page)</label>
						</div>
						<button class="btn btn-primary" name="create">Create</button>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-7">
			<div class="table-responsive">
				<table class="table align-middle">
					<thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Category</th><th>Active</th><th>Rent</th><th>Rental Only</th><th></th></tr></thead>
					<tbody>
						<?php foreach ($products as $p): ?>
							<tr>
								<td><?php echo (int)$p['id']; ?></td>
								<td>
									<?php if (!empty($p['image_url'])): ?>
										<img src="<?php echo e($p['image_url']); ?>" alt="<?php echo e($p['name']); ?>" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;" onerror="this.style.display='none'">
									<?php else: ?>
										<span class="text-muted small">No image</span>
									<?php endif; ?>
								</td>
								<td><?php echo e($p['name']); ?></td>
								<td>₹<?php echo number_format((float)$p['price'], 2); ?></td>
								<td><?php echo e($p['category_name']); ?></td>
								<td><?php echo $p['is_active'] ? 'Yes' : 'No'; ?></td>
								<td><?php echo $p['is_rentable'] ? 'Yes' : 'No'; ?></td>
								<td><?php echo (isset($p['rental_only']) && $p['rental_only']) ? 'Yes' : 'No'; ?></td>
								<td>
									<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit<?php echo (int)$p['id']; ?>">Edit</button>
									<form method="post" class="d-inline">
										<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
										<button name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete product?')">Delete</button>
									</form>
								</td>
							</tr>
							<tr class="collapse" id="edit<?php echo (int)$p['id']; ?>">
								<td colspan="9">
									<form method="post" enctype="multipart/form-data" class="border rounded p-3">
										<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
										<div class="row g-2">
											<div class="col-md-4"><input name="name" class="form-control" value="<?php echo e($p['name']); ?>" required></div>
											<div class="col-md-2"><input name="price" type="number" step="0.01" class="form-control" value="<?php echo e($p['price']); ?>" required></div>
											
											<div class="col-12 mb-2">
												<label class="form-label small">Upload New Image</label>
												<input name="image_file" type="file" class="form-control form-control-sm" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" id="editImageFile<?php echo (int)$p['id']; ?>">
												<small class="text-muted">Max 5MB. Leave empty to keep current image.</small>
												<div id="editImagePreview<?php echo (int)$p['id']; ?>" class="mt-2" style="display: none;">
													<img id="editPreviewImg<?php echo (int)$p['id']; ?>" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
												</div>
											</div>
											
											<div class="col-12 mb-2">
												<label class="form-label small">Or Enter Image URL</label>
												<input name="image_url" class="form-control form-control-sm" value="<?php echo e($p['image_url']); ?>" placeholder="Image URL">
												<?php if (!empty($p['image_url'])): ?>
													<div class="mt-1">
														<small>Current: </small>
														<img src="<?php echo e($p['image_url']); ?>" alt="Current" class="img-thumbnail" style="max-width: 100px; max-height: 100px;" onerror="this.style.display='none'">
													</div>
												<?php endif; ?>
											</div>
											<div class="col-md-3">
												<select name="category_id" class="form-select">
													<option value="0">No category</option>
													<?php foreach ($categories as $c): ?>
														<option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === (int)$p['category_id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
													<?php endforeach; ?>
												</select>
											</div>
											<div class="col-12"><textarea name="description" class="form-control" rows="2"><?php echo e($p['description']); ?></textarea></div>
											<div class="col-md-3 form-check ms-2">
												<input type="hidden" name="is_active" value="0">
												<input class="form-check-input" type="checkbox" name="is_active" id="eia<?php echo (int)$p['id']; ?>" value="1" <?php echo $p['is_active'] ? 'checked' : ''; ?>>
												<label class="form-check-label" for="eia<?php echo (int)$p['id']; ?>">Active</label>
											</div>
											<div class="col-md-3 form-check">
												<input type="hidden" name="is_rentable" value="0">
												<input class="form-check-input" type="checkbox" name="is_rentable" id="eir<?php echo (int)$p['id']; ?>" value="1" <?php echo $p['is_rentable'] ? 'checked' : ''; ?>>
												<label class="form-check-label" for="eir<?php echo (int)$p['id']; ?>">Rentable</label>
											</div>
											<div class="col-md-3 form-check">
												<input type="hidden" name="rental_only" value="0">
												<input class="form-check-input" type="checkbox" name="rental_only" id="ero<?php echo (int)$p['id']; ?>" value="1" <?php echo (isset($p['rental_only']) && $p['rental_only']) ? 'checked' : ''; ?>>
												<label class="form-check-label" for="ero<?php echo (int)$p['id']; ?>">Rental Only</label>
											</div>
											<div class="col-12">
												<button class="btn btn-sm btn-primary" name="update">Save</button>
											</div>
										</div>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
// Image preview for create form
document.getElementById('createImageFile')?.addEventListener('change', function(e) {
	const file = e.target.files[0];
	if (file) {
		const reader = new FileReader();
		reader.onload = function(e) {
			document.getElementById('createPreviewImg').src = e.target.result;
			document.getElementById('createImagePreview').style.display = 'block';
		};
		reader.readAsDataURL(file);
	} else {
		document.getElementById('createImagePreview').style.display = 'none';
	}
});

// Image preview for edit forms
<?php foreach ($products as $p): ?>
document.getElementById('editImageFile<?php echo (int)$p['id']; ?>')?.addEventListener('change', function(e) {
	const file = e.target.files[0];
	if (file) {
		const reader = new FileReader();
		reader.onload = function(e) {
			document.getElementById('editPreviewImg<?php echo (int)$p['id']; ?>').src = e.target.result;
			document.getElementById('editImagePreview<?php echo (int)$p['id']; ?>').style.display = 'block';
		};
		reader.readAsDataURL(file);
	} else {
		document.getElementById('editImagePreview<?php echo (int)$p['id']; ?>').style.display = 'none';
	}
});
<?php endforeach; ?>
</script>

<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


