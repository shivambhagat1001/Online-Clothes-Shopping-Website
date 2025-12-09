<?php
session_start();
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/lib/db.php';
require_once __DIR__ . '/../../src/lib/helpers.php';
require_once __DIR__ . '/../../src/lib/auth.php';

// Require user to be logged in
$user = auth_user();
if (!$user) {
	header('Location: /clothyyy/public/auth/login.php');
	exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
	die('Invalid order ID');
}

// Fetch order details
$order = db_fetch_one("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = :id AND o.user_id = :uid", [
	':id' => $order_id,
	':uid' => $user['id']
]);

if (!$order) {
	die('Order not found or you do not have permission to view this order.');
}

// Only allow download if payment is successful
if ($order['payment_status'] !== 'paid') {
	die('Bill can only be downloaded for paid orders.');
}

// Fetch order items with product details
$order_items = db_fetch_all("
	SELECT 
		oi.quantity,
		oi.price as item_price,
		pr.id as product_id,
		pr.name as product_name,
		pr.image_url
	FROM order_items oi
	LEFT JOIN products pr ON pr.id = oi.product_id
	WHERE oi.order_id = :oid
", [':oid' => $order_id]);

// Calculate subtotal
$subtotal = 0;
foreach ($order_items as $item) {
	$subtotal += (float)$item['item_price'] * (int)$item['quantity'];
}

// Calculate shipping (if any)
$shipping = (float)$order['total_amount'] - $subtotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bill - Order #<?php echo $order_id; ?></title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: Arial, sans-serif;
			padding: 20px;
			background: #f5f5f5;
		}
		.bill-container {
			max-width: 800px;
			margin: 0 auto;
			background: white;
			padding: 40px;
			box-shadow: 0 0 10px rgba(0,0,0,0.1);
		}
		.bill-header {
			border-bottom: 3px solid #333;
			padding-bottom: 20px;
			margin-bottom: 30px;
		}
		.bill-header h1 {
			color: #333;
			font-size: 28px;
			margin-bottom: 10px;
		}
		.bill-header p {
			color: #666;
			font-size: 14px;
		}
		.bill-info {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
			margin-bottom: 30px;
		}
		.info-section h3 {
			color: #333;
			font-size: 16px;
			margin-bottom: 10px;
			border-bottom: 1px solid #ddd;
			padding-bottom: 5px;
		}
		.info-section p {
			color: #666;
			font-size: 14px;
			line-height: 1.6;
			margin: 5px 0;
		}
		.products-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 30px;
		}
		.products-table th {
			background: #333;
			color: white;
			padding: 12px;
			text-align: left;
			font-size: 14px;
		}
		.products-table td {
			padding: 12px;
			border-bottom: 1px solid #ddd;
			font-size: 14px;
		}
		.products-table tr:last-child td {
			border-bottom: none;
		}
		.product-image {
			width: 50px;
			height: 50px;
			object-fit: cover;
			border-radius: 4px;
		}
		.product-info {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.product-name {
			font-weight: bold;
			color: #333;
		}
		.text-right {
			text-align: right;
		}
		.bill-summary {
			margin-top: 20px;
			border-top: 2px solid #333;
			padding-top: 20px;
		}
		.summary-row {
			display: flex;
			justify-content: space-between;
			padding: 8px 0;
			font-size: 14px;
		}
		.summary-row.total {
			font-size: 18px;
			font-weight: bold;
			border-top: 2px solid #333;
			padding-top: 10px;
			margin-top: 10px;
		}
		.bill-footer {
			margin-top: 40px;
			padding-top: 20px;
			border-top: 1px solid #ddd;
			text-align: center;
			color: #666;
			font-size: 12px;
		}
		.print-button {
			text-align: center;
			margin: 20px 0;
		}
		.btn-print {
			background: #007bff;
			color: white;
			padding: 10px 20px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 16px;
		}
		.btn-print:hover {
			background: #0056b3;
		}
		@media print {
			body {
				background: white;
				padding: 0;
			}
			.print-button {
				display: none;
			}
			.bill-container {
				box-shadow: none;
				padding: 20px;
			}
		}
	</style>
</head>
<body>
	<div class="print-button">
		<button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
	</div>
	
	<div class="bill-container">
		<div class="bill-header">
			<h1>INVOICE</h1>
			<p>Order #<?php echo $order_id; ?></p>
			<p>Date: <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
		</div>
		
		<div class="bill-info">
			<div class="info-section">
				<h3>Bill To:</h3>
				<p><strong><?php echo e($order['customer_name']); ?></strong></p>
				<p><?php echo nl2br(e($order['address'])); ?></p>
				<p>Phone: <?php echo e($order['phone']); ?></p>
				<p>Email: <?php echo e($order['email'] ?? 'N/A'); ?></p>
			</div>
			<div class="info-section">
				<h3>Payment Information:</h3>
				<p><strong>Payment Method:</strong> <?php echo strtoupper(e($order['payment_method'])); ?></p>
				<p><strong>Payment Status:</strong> <span style="color: green; font-weight: bold;"><?php echo strtoupper(e($order['payment_status'])); ?></span></p>
				<p><strong>Order Status:</strong> <?php echo ucfirst(e($order['status'])); ?></p>
			</div>
		</div>
		
		<table class="products-table">
			<thead>
				<tr>
					<th style="width: 50px;">Image</th>
					<th>Product</th>
					<th style="width: 80px;" class="text-right">Quantity</th>
					<th style="width: 100px;" class="text-right">Unit Price</th>
					<th style="width: 120px;" class="text-right">Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($order_items as $item): ?>
					<tr>
						<td>
							<?php if (!empty($item['image_url'])): ?>
								<img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['product_name']); ?>" class="product-image">
							<?php else: ?>
								<div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">No Image</div>
							<?php endif; ?>
						</td>
						<td>
							<div class="product-name"><?php echo e($item['product_name']); ?></div>
						</td>
						<td class="text-right"><?php echo (int)$item['quantity']; ?></td>
						<td class="text-right">₹<?php echo number_format((float)$item['item_price'], 2); ?></td>
						<td class="text-right"><strong>₹<?php echo number_format((float)$item['item_price'] * (int)$item['quantity'], 2); ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<div class="bill-summary">
			<div class="summary-row">
				<span>Subtotal:</span>
				<span>₹<?php echo number_format($subtotal, 2); ?></span>
			</div>
			<?php if ($shipping > 0): ?>
			<div class="summary-row">
				<span>Shipping:</span>
				<span>₹<?php echo number_format($shipping, 2); ?></span>
			</div>
			<?php endif; ?>
			<div class="summary-row total">
				<span>Total Amount:</span>
				<span>₹<?php echo number_format((float)$order['total_amount'], 2); ?></span>
			</div>
		</div>
		
		<div class="bill-footer">
			<p>Thank you for your purchase!</p>
			<p><?php echo APP_NAME; ?> - Your trusted clothing partner</p>
		</div>
	</div>
</body>
</html>



