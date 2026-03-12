<?php
session_start();
require_once 'db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized Access! This area is for administrators only.");
}

// Get analytics
$total_sales_stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'");
$total_sales = $total_sales_stmt->fetchColumn() ?: 0;

$order_count_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$order_count = $order_count_stmt->fetchColumn();

$user_count_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$user_count = $user_count_stmt->fetchColumn();

// Get recent orders
$recent_orders = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Sports Advanced</title>
    <link rel="stylesheet" href="style.css?v=1.2">
    <style>
        .dashboard { max-width: 1200px; margin: 0 auto; width: 100%; border-radius: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); border: 1px solid #eee; text-align: center; }
        .stat-card h3 { font-size: 2rem; color: var(--primary-color); margin: 10px 0; }
        .stat-card p { color: #888; font-size: 0.9rem; font-weight: 500; }
        .admin-table { width: 100%; background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #eee; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .status-select { padding: 5px; border-radius: 5px; border: 1px solid #ddd; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header" style="background:#fff; border-bottom: 2px solid var(--primary-color); padding: 20px; margin-bottom: 30px; display:flex; justify-content: space-between; align-items: center; border-radius: 0 0 15px 15px;">
            <h1 style="margin:0;">Admin <span style="color:var(--primary-color)">Control Panel</span></h1>
            <div style="display:flex; gap:15px; align-items:center;">
                <a href="manage_products.php" class="btn-secondary" style="border-radius:30px;">Manage Products</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Revenue</p>
                <h3>₹<?php echo number_format($total_sales, 2); ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Orders</p>
                <h3><?php echo $order_count; ?></h3>
            </div>
            <div class="stat-card">
                <p>Registered Customers</p>
                <h3><?php echo $user_count; ?></h3>
            </div>
        </div>

        <h2>Recent <span style="color:var(--primary-color)">Orders</span></h2>
        <table class="admin-table cart-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><span style="font-weight:700; font-size: 0.8rem; padding: 2px 10px; border-radius: 10px;" class="status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                    <td style="font-size: 0.8rem; color: #888;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-select">
                                <option value="Pending" <?php echo $order['status']=='Pending'?'selected':''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo $order['status']=='Confirmed'?'selected':''; ?>>Confirmed</option>
                                <option value="Shipped" <?php echo $order['status']=='Shipped'?'selected':''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $order['status']=='Delivered'?'selected':''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $order['status']=='Cancelled'?'selected':''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-primary" style="width:auto; padding: 2px 10px; font-size: 0.7rem;">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
