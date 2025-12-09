<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-5">
	<div class="hero mb-5">
		<h1>Clothyyy</h1>
		<p class="lead mb-4">Shop the latest styles and trends</p>
		<form action="/clothyyy/public/products.php" method="get" class="d-flex justify-content-center gap-2" role="search" style="max-width: 600px; margin: 0 auto;">
			<input class="form-control" type="search" name="q" placeholder="Search for clothes..." aria-label="Search" style="border-radius: 50px;">
			<button class="btn btn-light" type="submit" style="border-radius: 50px; min-width: 120px;">🔍 Search</button>
		</form>
		<div class="mt-4 d-flex justify-content-center gap-3 flex-wrap">
			<a href="/clothyyy/public/products.php" class="btn btn-light">Browse All Products</a>
			<a href="/clothyyy/public/rent/index.php" class="btn btn-light">Rent Clothes</a>
		</div>
	</div>
	
	<?php
		// Fetch categories grouped by gender
		$allCategories = db_fetch_all("SELECT * FROM categories WHERE gender IS NOT NULL ORDER BY gender ASC, name ASC");
		$menCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'men'; });
		$womenCategories = array_filter($allCategories, function($c) { return $c['gender'] === 'women'; });
	?>
	
	<?php if (!empty($menCategories) || !empty($womenCategories)): ?>
		<div class="section-header mb-4">
			<h2>Shop by Category</h2>
		</div>
		
		<?php if (!empty($menCategories)): ?>
			<div class="mb-5">
				<h3 class="h5 mb-3">👔 Men's Collection</h3>
				<div class="d-flex flex-wrap gap-2">
					<?php foreach ($menCategories as $cat): ?>
						<a href="/clothyyy/public/products.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-outline-primary">
							<?php echo e($cat['name']); ?>
						</a>
					<?php endforeach; ?>
					<a href="/clothyyy/public/products.php?gender=men" class="btn btn-primary">
						View All Men's Products →
					</a>
				</div>
			</div>
		<?php endif; ?>
		
		<?php if (!empty($womenCategories)): ?>
			<div class="mb-5">
				<h3 class="h5 mb-3">👗 Women's Collection</h3>
				<div class="d-flex flex-wrap gap-2">
					<?php foreach ($womenCategories as $cat): ?>
						<a href="/clothyyy/public/products.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-outline-danger">
							<?php echo e($cat['name']); ?>
						</a>
					<?php endforeach; ?>
					<a href="/clothyyy/public/products.php?gender=women" class="btn btn-danger">
						View All Women's Products →
					</a>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	
	<div class="section-header">
		<h2>Featured Products</h2>
	</div>
	
	<div class="row g-4">
		<?php
			$products = db_fetch_all("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND (p.rental_only = 0 OR p.rental_only IS NULL) ORDER BY p.created_at DESC LIMIT 8");
			foreach ($products as $p):
		?>
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
   
</div>
<div id="chatbot-root"></div>

<?php include __DIR__ . '/../src/partials/footer.php'; ?>


