<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';
	$q = isset($_GET['q']) ? trim($_GET['q']) : '';
	$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
	$gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
	
	$params = [];
	$sql = "SELECT p.*, c.name AS category_name, c.gender FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND (p.rental_only = 0 OR p.rental_only IS NULL)";
	
	if ($category_id > 0) {
		$sql .= " AND p.category_id = :cat_id";
		$params[':cat_id'] = $category_id;
		$category = db_fetch_one("SELECT * FROM categories WHERE id = :id", [':id' => $category_id]);
	}
	
	if ($gender && in_array($gender, ['men', 'women'])) {
		$sql .= " AND c.gender = :gender";
		$params[':gender'] = $gender;
	}
	
	if ($q !== '') {
		$sql .= " AND (p.name LIKE :q OR c.name LIKE :q)";
		$params[':q'] = "%$q%";
	}
	
	$sql .= " ORDER BY p.created_at DESC";
	$products = db_fetch_all($sql, $params);
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-5">
	<div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
		<div>
			<h2>Shop</h2>
			<?php if ($category_id > 0 && isset($category)): ?>
				<p class="text-muted mb-0">Category: <strong><?php echo e($category['name']); ?></strong></p>
			<?php elseif ($gender): ?>
				<p class="text-muted mb-0"><?php echo ucfirst($gender); ?>'s Collection</p>
			<?php endif; ?>
		</div>
		<form method="get" class="d-flex" style="max-width: 400px;">
			<?php if ($category_id > 0): ?>
				<input type="hidden" name="category" value="<?php echo $category_id; ?>">
			<?php endif; ?>
			<?php if ($gender): ?>
				<input type="hidden" name="gender" value="<?php echo e($gender); ?>">
			<?php endif; ?>
			<input class="form-control me-2" type="search" name="q" placeholder="Search products..." value="<?php echo e($q); ?>" style="border-radius: 50px;">
			<button class="btn btn-primary" style="border-radius: 50px; min-width: 100px;">Search</button>
		</form>
	</div>
	
	<?php
		// Show filter breadcrumbs
		$hasFilters = $q || $category_id > 0 || $gender;
		if ($hasFilters):
	?>
		<div class="alert alert-info mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
			<div>
				<?php if ($category_id > 0 && isset($category)): ?>
					<span class="badge bg-primary me-2">Category: <?php echo e($category['name']); ?></span>
				<?php endif; ?>
				<?php if ($gender): ?>
					<span class="badge bg-info me-2"><?php echo ucfirst($gender); ?>'s</span>
				<?php endif; ?>
				<?php if ($q): ?>
					<span class="badge bg-secondary me-2">Search: "<?php echo e($q); ?>"</span>
				<?php endif; ?>
				<strong><?php echo count($products); ?></strong> product(s) found
			</div>
			<a href="/clothyyy/public/products.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
		</div>
	<?php endif; ?>
	
	<?php
		// Show category filter sidebar if not already filtering by category
		if (!$category_id && !$gender):
			$allCategories = db_fetch_all("SELECT * FROM categories WHERE gender IS NOT NULL ORDER BY gender ASC, name ASC");
			$menCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'men'; });
			$womenCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'women'; });
	?>
		<div class="row mb-4">
			<div class="col-12">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title mb-3">Browse by Category</h5>
						<?php if (!empty($menCategories)): ?>
							<div class="mb-3">
								<strong class="text-primary">👔 Men's:</strong>
								<?php foreach ($menCategories as $cat): ?>
									<a href="/clothyyy/public/products.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
										<?php echo e($cat['name']); ?>
									</a>
								<?php endforeach; ?>
								<a href="/clothyyy/public/products.php?gender=men" class="btn btn-sm btn-primary ms-2">View All</a>
							</div>
						<?php endif; ?>
						<?php if (!empty($womenCategories)): ?>
							<div>
								<strong class="text-danger">👗 Women's:</strong>
								<?php foreach ($womenCategories as $cat): ?>
									<a href="/clothyyy/public/products.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-sm btn-outline-danger ms-2">
										<?php echo e($cat['name']); ?>
									</a>
								<?php endforeach; ?>
								<a href="/clothyyy/public/products.php?gender=women" class="btn btn-sm btn-danger ms-2">View All</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
	
	<?php if (empty($products)): ?>
		<div class="alert alert-warning text-center py-5">
			<h5>No products found</h5>
			<p class="mb-0">Try adjusting your search or <a href="/clothyyy/public/products.php">browse all products</a></p>
		</div>
	<?php else: ?>
		<div class="row g-4">
			<?php foreach ($products as $p): ?>
				<div class="col-6 col-md-3">
					<div class="card h-100 product-card">
						<?php if (!empty($p['image_url'])): ?>
							<img src="<?php echo e($p['image_url']); ?>" class="card-img-top" alt="<?php echo e($p['name']); ?>">
						<?php else: ?>
							<div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
								<span class="text-muted">No Image</span>
							</div>
						<?php endif; ?>
						<div class="card-body d-flex flex-column">
							<h5 class="card-title mb-1"><?php echo e($p['name']); ?></h5>
							<small class="text-muted mb-2"><?php echo e($p['category_name'] ?? ''); ?></small>
							<div class="mt-auto d-flex justify-content-between align-items-center">
								<span class="price">₹<?php echo number_format((float)$p['price'], 2); ?></span>
								<a href="/clothyyy/public/product.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-primary">View</a>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


