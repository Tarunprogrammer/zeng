<?php
// admin.php
// Administration Panel for Zeng Customs

session_start();
require_once 'config.php';

// Auth and Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    
    if ($order_id && in_array($action, ['completed', 'cancelled', 'pending'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([
                ':status' => $action,
                ':id' => $order_id
            ]);
            $success_message = "Order #$order_id status updated to '$action'.";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all orders
try {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Orders Admin Panel - Zeng Customs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">Zeng <span>Customs</span></div>
        <nav class="nav-links">
            <a href="index.php">Design Studio</a>
            <a href="admin.php" class="active">Admin Panel</a>
            <span style="color: var(--text-secondary); margin-left: 1.5rem;">Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="app-container" style="display: block;">
        <div class="admin-card">
            <h2 style="font-family: var(--font-heading); margin-bottom: 0.5rem; font-weight: 800;">Custom Shirt Orders</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Manage printing queues, review client designs, and process incoming customized shirt orders.</p>

            <?php if (isset($success_message)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--accent-success); color: var(--accent-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.95rem;">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--accent-error); color: var(--accent-error); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.95rem;">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Customer Details</th>
                            <th>Shirt specs</th>
                            <th>Design</th>
                            <th>Mockup</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 3rem 0;">
                                    No custom shirt orders found yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <!-- Ref ID -->
                                    <td style="font-weight: 700; white-space: nowrap;">
                                        #ZENG-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    
                                    <!-- Customer Details -->
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                    </td>
                                    
                                    <!-- Shirt Specs -->
                                    <td>
                                        <div style="margin-bottom: 0.25rem;">Size: <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($order['shirt_size']); ?></strong></div>
                                        <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--text-secondary);">
                                            Color: 
                                            <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); background-color: <?php echo htmlspecialchars($order['shirt_color']); ?>;"></span>
                                            <?php echo htmlspecialchars($order['shirt_color']); ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Design details -->
                                    <td>
                                        <?php if ($order['design_type'] !== 'none'): ?>
                                            <div style="margin-bottom: 0.5rem;">
                                                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Front</div>
                                                <div style="font-size: 0.9rem; font-weight: 500; text-transform: capitalize; color: var(--text-primary); line-height: 1.2;">
                                                    <?php echo htmlspecialchars($order['design_type']); ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-secondary); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($order['design_src']); ?>">
                                                    <?php echo basename(htmlspecialchars($order['design_src'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['back_design_type'] !== 'none'): ?>
                                            <div>
                                                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Back</div>
                                                <div style="font-size: 0.9rem; font-weight: 500; text-transform: capitalize; color: var(--text-primary); line-height: 1.2;">
                                                    <?php echo htmlspecialchars($order['back_design_type']); ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-secondary); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($order['back_design_src']); ?>">
                                                    <?php echo basename(htmlspecialchars($order['back_design_src'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['design_type'] === 'none' && $order['back_design_type'] === 'none'): ?>
                                            <div style="font-size: 0.85rem; color: var(--text-secondary); font-style: italic;">No design</div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Mockup Thumbnail -->
                                    <td>
                                        <button class="btn-view" onclick="openPreviewModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['preview_image']); ?>')">
                                            View Mockup
                                        </button>
                                    </td>
                                    
                                    <!-- Order status -->
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Action buttons -->
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <?php if ($order['status'] !== 'completed'): ?>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="action" value="completed">
                                                    <button type="submit" class="btn-action btn-complete" title="Mark Completed">✓ Complete</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] !== 'cancelled'): ?>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="action" value="cancelled">
                                                    <button type="submit" class="btn-action btn-cancel" title="Cancel Order">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal for Order Visual Verification -->
    <div class="modal" id="mockup-modal" onclick="closePreviewModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="close-modal" onclick="closePreviewModal()">&times;</span>
            <h2 id="modal-title" style="font-family: var(--font-heading); margin-bottom: 1.5rem; font-weight: 700;">Order Mockup</h2>
            <img id="modal-mockup-img" src="" alt="Client Composition Preview" class="preview-mockup-img">
            
            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button class="btn-view" onclick="closePreviewModal()">Close Preview</button>
            </div>
        </div>
    </div>

    <!-- Script to control visual overlay -->
    <script>
        function openPreviewModal(orderId, imageBase64) {
            const modal = document.getElementById('mockup-modal');
            const title = document.getElementById('modal-title');
            const img = document.getElementById('modal-mockup-img');
            
            title.textContent = `Order Mockup Preview #ZENG-${orderId.toString().padStart(6, '0')}`;
            img.src = imageBase64;
            
            modal.classList.add('show');
        }

        function closePreviewModal(e) {
            // Close if triggered directly or by target
            const modal = document.getElementById('mockup-modal');
            modal.classList.remove('show');
        }
    </script>
</body>
</html>
