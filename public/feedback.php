<?php
	session_start();
	require_once __DIR__ . '/../src/config.php';
	require_once __DIR__ . '/../src/lib/db.php';
	require_once __DIR__ . '/../src/lib/helpers.php';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
		$name = trim($_POST['name'] ?? '');
		$rating = (int)($_POST['rating'] ?? 5);
		$message = trim($_POST['message'] ?? '');
		if ($name && $message) {
			db_execute("INSERT INTO feedback (name, rating, message, created_at) VALUES (:n,:r,:m,NOW())", [
				':n' => $name, ':r' => $rating, ':m' => $message
			]);
			redirect('/clothyyy/public/feedback.php?ok=1');
		}
	}
	$list = db_fetch_all("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 20");
	$ok = isset($_GET['ok']);
?>
<?php include __DIR__ . '/../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Feedback</h1>
	<?php if ($ok): ?><div class="alert alert-success">Thanks for your feedback!</div><?php endif; ?>
	<div class="row g-4">
		<div class="col-md-6">
			<form method="post">
				<div class="mb-3">
					<label class="form-label">Your Name</label>
					<input name="name" class="form-control" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Rating</label>
					<select name="rating" class="form-select">
						<?php for ($i=5;$i>=1;$i--): ?>
							<option value="<?php echo $i; ?>"><?php echo $i; ?> star<?php echo $i>1?'s':''; ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="mb-3">
					<label class="form-label">Message</label>
					<textarea name="message" class="form-control" rows="4" required></textarea>
				</div>
				<input type="hidden" name="submit_feedback" value="1">
				<button class="btn btn-primary">Submit</button>
			</form>
		</div>
		<div class="col-md-6">
			<h5 class="mb-3">Recent Feedback</h5>
			<?php foreach ($list as $f): ?>
				<div class="border rounded p-3 mb-2">
					<div class="d-flex justify-content-between">
						<strong><?php echo e($f['name']); ?></strong>
						<span class="text-warning"><?php echo str_repeat('★', (int)$f['rating']); ?><?php echo str_repeat('☆', 5 - (int)$f['rating']); ?></span>
					</div>
					<div><?php echo nl2br(e($f['message'])); ?></div>
					<small class="text-muted"><?php echo e($f['created_at']); ?></small>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php include __DIR__ . '/../src/partials/footer.php'; ?>


