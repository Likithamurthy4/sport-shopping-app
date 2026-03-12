<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_POST['payment_method'] ?? 'COD';
$total = $_POST['checkout_total'] ?? 0;
$buy_now_id = $_POST['buy_now_id'] ?? null;

// If it's a UPI payment and not yet confirmed, show the QR screen
if (($method == 'PhonePe' || $method == 'GooglePay') && !isset($_GET['confirm_payment'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UPI Payment | Sports App</title>
    <link rel="stylesheet" href="style.css?v=1.3">
</head>
<body>
    <div class="auth-container" style="text-align:center; max-width: 450px;">
        <h2 style="margin-bottom:10px;">Pay with <?php echo $method; ?></h2>
        <p style="color:#777; font-size: 0.9rem;">Amount to pay: <strong>₹<?php echo number_format($total, 2); ?></strong></p>
        
        <div class="qr-container" style="margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 15px; border: 1px solid #eee;">
             <div style="width:200px; height:200px; margin:0 auto; background:white; padding:10px; border:1px solid #eee; display:flex; align-items:center; justify-content:center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=sportsapp@upi%26pn=Sports%20Accessories%26am=<?php echo $total; ?>%26cu=INR" alt="UPI QR Code" style="width:100%;">
            </div>
            <p style="font-size: 0.8rem; color: #555; margin-top: 15px;">Scan this QR with any UPI app (GPay, PhonePe, Paytm)</p>
        </div>
        
        <form action="buy.php?confirm_payment=1" method="POST">
            <input type="hidden" name="payment_method" value="<?php echo $method; ?>">
            <input type="hidden" name="checkout_total" value="<?php echo $total; ?>">
            <input type="hidden" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
            <?php if($buy_now_id): ?><input type="hidden" name="buy_now_id" value="<?php echo $buy_now_id; ?>"><?php endif; ?>
            <button type="submit" class="btn-primary">I Have Paid</button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
}

// Process order
try {
    $pdo->beginTransaction();

    $order_items_to_save = [];

    if ($buy_now_id) {
        // Buy Now Logic
        $stmt_p = $pdo->prepare("SELECT id, price, stock FROM products WHERE id = ? AND stock > 0");
        $stmt_p->execute([$buy_now_id]);
        $prod = $stmt_p->fetch();
        if (!$prod) throw new Exception("Product unavailable.");
        $order_items_to_save[] = ['id' => $prod['id'], 'qty' => 1, 'price' => $prod['price']];
    } else {
        // Cart Logic
        $stmt_cart = $pdo->prepare("SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt_cart->execute([$user_id]);
        $rows = $stmt_cart->fetchAll();
        foreach ($rows as $r) $order_items_to_save[] = ['id' => $r['product_id'], 'qty' => $r['quantity'], 'price' => $r['price']];
    }

    if (empty($order_items_to_save)) throw new Exception("Empty order.");

    $final_total = 0;
    foreach ($order_items_to_save as $item) $final_total += $item['price'] * $item['qty'];

    // Create order
    $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method) VALUES (?, ?, ?)");
    $stmt_order->execute([$user_id, $final_total, $method]);
    $order_id = $pdo->lastInsertId();

    // Save items & Update stock
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($order_items_to_save as $item) {
        $stmt_item->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
        $stmt_stock->execute([$item['qty'], $item['id']]);
    }

    // Clear cart ONLY if it was a cart checkout
    if (!$buy_now_id) {
        $stmt_clear = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear->execute([$user_id]);
    }

    $pdo->commit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Success | Sports App</title>
    <link rel="stylesheet" href="style.css?v=1.3">
</head>
<body>
    <div class="auth-container" style="text-align:center;">
        <div style="font-size: 5rem; color: #4CAF50; margin-bottom: 20px;">✓</div>
        <h2>Order Confirmed!</h2>
        <div style="background: #f9f9f9; padding: 25px; border-radius: 15px; border: 1px dashed var(--primary-color); margin: 30px 0;">
            <p style="font-size: 0.9rem; color: #666;">Order ID: <strong>#<?php echo $order_id; ?></strong></p>
            <p style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color); margin-top: 10px;">₹<?php echo number_format($final_total, 2); ?></p>
            <?php if(isset($_POST['location']) && !empty($_POST['location'])): ?>
                <p style="font-size: 0.8rem; color: #888; margin-top: 15px;">Shipping to: <br><strong><?php echo htmlspecialchars($_POST['location']); ?></strong></p>
            <?php endif; ?>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="order_history.php" class="btn-secondary" style="flex:1; text-align:center;">History</a>
            <a href="index.php" class="btn-primary" style="flex:2; text-align:center;">Back to Shop</a>
        </div>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>
