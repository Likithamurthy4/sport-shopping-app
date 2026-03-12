<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History | Sports Advanced</title>
    <link rel="stylesheet" href="style.css?v=1.2">
    <style>
        .dashboard { max-width: 1000px; margin: 0 auto; width: 100%; border-radius: 20px; }
        .order-card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid #eee; position: relative; }
        .status-badge { position: absolute; top: 30px; right: 30px; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .status-Pending { background: #fff3e0; color: #ef6c00; }
        .status-Confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-Shipped { background: #e3f2fd; color: #1565c0; }
        .status-Delivered { background: #f1f8e9; color: #33691e; }
        .status-Cancelled { background: #ffebee; color: #c62828; }
        .order-details-btn { font-size: 0.8rem; color: var(--primary-color); text-decoration: none; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>My <span style="color:var(--primary-color)">Order History</span></h2>
            <a href="index.php" class="btn-secondary" style="border-radius:30px;">Back to Shop</a>
        </div>

        <?php if (empty($orders)): ?>
            <div style="background:#fff; padding: 50px; border-radius:20px; text-align:center; border: 1px solid #eee;">
                <p style="color:#777; font-size: 1.1rem;">You haven't placed any orders yet.</p>
                <br>
                <a href="index.php" class="btn-primary" style="display:inline-block; width:auto; text-decoration:none;">Explore Shop</a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    <h3 style="margin-bottom:10px;">Order #<?php echo $order['id']; ?></h3>
                    <p style="color:#888; font-size: 0.85rem;">Date: <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                    <p style="color:#888; font-size: 0.85rem; margin-bottom: 20px;">Payment: <?php echo $order['payment_method']; ?></p>
                    
                    <div style="display:flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #f5f5f5; padding-top: 15px;">
                        <div>
                            <p style="font-size: 0.9rem; color: #555;">Total Amount</p>
                            <p style="font-size: 1.3rem; font-weight: 700; color: var(--primary-color);">₹<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
