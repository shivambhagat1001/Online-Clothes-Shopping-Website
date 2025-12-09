<?php
	session_start();
	require_once __DIR__ . '/../../src/config.php';
	require_once __DIR__ . '/../../src/lib/db.php';
	require_once __DIR__ . '/../../src/lib/helpers.php';
	require_once __DIR__ . '/../../src/lib/auth.php';
	auth_require_admin();

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
		$feedback_id = (int)$_POST['id'];
		db_execute("DELETE FROM feedback WHERE id=:id", [':id' => $feedback_id]);
		redirect('/clothyyy/public/admin/feedback.php');
	}

	$rows = db_fetch_all("SELECT * FROM feedback ORDER BY created_at DESC");
?>
<?php include __DIR__ . '/../../src/partials/header.php'; ?>
<div class="container py-4">
	<h1 class="h4 mb-3">Manage Feedback</h1>
	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead><tr><th>ID</th><th>Name</th><th>Rating</th><th>Message</th><th>Time</th><th></th></tr></thead>
			<tbody>
				<?php foreach ($rows as $f): ?>
					<tr>
						<td><?php echo (int)$f['id']; ?></td>
						<td><?php echo e($f['name']); ?></td>
						<td><?php echo (int)$f['rating']; ?>/5</td>
						<td><?php echo nl2br(e($f['message'])); ?></td>
						<td><?php echo e($f['created_at']); ?></td>
						<td>
							<form method="post" class="d-inline" onsubmit="return confirm('Delete this feedback?');">
								<input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>">
								<button name="delete" class="btn btn-sm btn-outline-danger">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/../../src/partials/footer.php'; ?>


