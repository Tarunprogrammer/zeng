<?php
// order_success.php
// Order Success Screen & Receipt
session_start();

require_once 'config.php';

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Order not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed Successfully - Zeng Customs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">Zeng <span>Customs</span></div>
        <nav class="nav-links">
            <a href="index.php">Design Studio</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <span style="color: var(--text-secondary); margin-left: 1.5rem;">Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Sign In</a>
                <a href="register.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="app-container" style="display: block;">
        <div class="receipt-card">
            <div class="success-icon">✓</div>
            <h2>Thank You for Your Order!</h2>
            <p>Your custom t-shirt has been ordered. Our printing team is on it!</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; text-align: left; margin-bottom: 2rem;">
                <!-- Left: Mockup Preview -->
                <div>
                    <h3 style="font-family: var(--font-heading); margin-bottom: 1rem; color: var(--text-secondary); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Your Custom T-Shirt</h3>
                    <img src="<?php echo htmlspecialchars($order['preview_image']); ?>" alt="Order Mockup" class="preview-mockup-img" style="margin-bottom: 0;">
                </div>
                
                <!-- Right: Receipt Details -->
                <div>
                    <h3 style="font-family: var(--font-heading); margin-bottom: 1rem; color: var(--text-secondary); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Order Details</h3>
                    <div class="receipt-details">
                        <div class="receipt-row">
                            <span class="receipt-label">Order Reference:</span>
                            <span class="receipt-value">#ZENG-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Order Date:</span>
                            <span class="receipt-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Size:</span>
                            <span class="receipt-value"><?php echo htmlspecialchars($order['shirt_size']); ?></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Shirt Color:</span>
                            <span class="receipt-value" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); background-color: <?php echo htmlspecialchars($order['shirt_color']); ?>;"></span>
                                <?php echo htmlspecialchars($order['shirt_color']); ?>
                            </span>
                        </div>
                        <?php if ($order['design_type'] !== 'none'): ?>
                            <div class="receipt-row">
                                <span class="receipt-label">Front Design:</span>
                                <span class="receipt-value" style="text-transform: capitalize;"><?php echo htmlspecialchars($order['design_type']); ?> (<?php echo basename(htmlspecialchars($order['design_src'])); ?>)</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['back_design_type'] !== 'none'): ?>
                            <div class="receipt-row">
                                <span class="receipt-label">Back Design:</span>
                                <span class="receipt-value" style="text-transform: capitalize;"><?php echo htmlspecialchars($order['back_design_type']); ?> (<?php echo basename(htmlspecialchars($order['back_design_src'])); ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div class="receipt-row">
                            <span class="receipt-label">Order Status:</span>
                            <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                        </div>
                    </div>

                    <h3 style="font-family: var(--font-heading); margin-bottom: 1rem; color: var(--text-secondary); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Shipping Address</h3>
                    <div class="receipt-details" style="margin-bottom: 0;">
                        <div class="receipt-row" style="border-bottom: none; display: block;">
                            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_email']); ?> | <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                            <div style="margin-top: 0.75rem; font-size: 0.9rem; color: var(--text-primary); white-space: pre-line; line-height: 1.4;"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <a href="index.php" class="order-btn" style="text-decoration: none; display: inline-flex; width: auto; padding: 0.85rem 2rem;">
                Create Another Design
            </a>
        </div>
    </main>
</body>
</html>
