<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Info
$user_stmt = $pdo->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Stats
$order_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$order_count->execute([$user_id]);
$total_orders = $order_count->fetchColumn();

$wish_count = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$wish_count->execute([$user_id]);
$total_wish = $wish_count->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Sports App</title>
    <link rel="stylesheet" href="style.css?v=1.4">
    <style>
        .profile-container { max-width: 800px; margin: 40px auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .profile-header { background: var(--primary-color); color: white; padding: 40px; text-align: center; }
        .profile-avatar { width: 100px; height: 100px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto 15px; color: var(--primary-color); }
        .profile-body { padding: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 15px; text-align: center; }
        .info-section { grid-column: 1 / -1; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn-secondary" style="border-radius: 30px;">← Back to Shop</a>
        </div>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">👤</div>
                <h1 style="margin:0;"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p style="opacity:0.8;"><?php echo htmlspecialchars($user['role']); ?></p>
            </div>
            
            <div class="profile-body">
                <div class="stat-card">
                    <h2 style="color: var(--primary-color); margin:0;"><?php echo $total_orders; ?></h2>
                    <p style="font-size: 0.9rem; color: #777;">Orders Placed</p>
                </div>
                <div class="stat-card">
                    <h2 style="color: var(--primary-color); margin:0;"><?php echo $total_wish; ?></h2>
                    <p style="font-size: 0.9rem; color: #777;">Saved Items</p>
                </div>

                <div class="info-section">
                    <div style="margin-bottom: 15px;">
                        <label style="color: #aaa; font-size: 0.8rem; display: block; text-transform: uppercase;">Email Address</label>
                        <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="color: #aaa; font-size: 0.8rem; display: block; text-transform: uppercase;">Member Since</label>
                        <strong><?php echo date('F d, Y', strtotime($user['created_at'])); ?></strong>
                    </div>
                </div>
                
                <div style="grid-column: 1 / -1; margin-top: 20px; display: flex; gap: 15px;">
                    <a href="order_history.php" class="btn-primary" style="flex:1; text-align:center; text-decoration:none;">View Orders</a>
                    <a href="wishlist.php" class="btn-secondary" style="flex:1; text-align:center; text-decoration:none;">My Wishlist</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
