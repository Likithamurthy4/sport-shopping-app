<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$buy_now_id = $_POST['buy_now'] ?? null;
$items = [];
$total_amount = 0;

if ($buy_now_id) {
    // Single product "Buy Now" flow
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? AND stock > 0");
    $stmt->execute([$buy_now_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header("Location: index.php");
        exit();
    }
    
    $items[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => 1
    ];
    $total_amount = $product['price'];
} else {
    // Standard cart checkout flow
    $stmt = $pdo->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        header("Location: index.php");
        exit();
    }

    foreach ($items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Sports Advanced</title>
    <link rel="stylesheet" href="style.css?v=1.3">
</head>
<body>
    <div class="auth-container" style="max-width: 500px;">
        <h2>Order Checkout</h2>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #eee;">
            <p style="color:#777; font-size: 0.9rem;">Order Summary</p>
            <h3 style="color:var(--primary-color);">₹<?php echo number_format($total_amount, 2); ?></h3>
            <p style="font-size: 0.85rem; color: #555;"><?php echo count($items); ?> product(s) selected.</p>
            <ul style="font-size: 0.8rem; color: #666; padding-left: 20px; margin-top: 10px;">
                <?php foreach($items as $item): ?>
                    <li><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <form action="buy.php" method="POST">
            <input type="hidden" name="checkout_total" value="<?php echo $total_amount; ?>">
            <?php if($buy_now_id): ?>
                <input type="hidden" name="buy_now_id" value="<?php echo $buy_now_id; ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 25px;">
                <label for="location" style="display: block; font-weight:600; font-size: 0.95rem; margin-bottom: 10px;">Delivery Location / Address</label>
                <input type="text" id="location" name="location" placeholder="Enter your full shipping address" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
            </div>

            <p style="font-weight:600; font-size: 0.95rem; margin-bottom: 10px;">Select Payment Method</p>
            
            <div class="payment-options">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="PhonePe" required>
                    <div class="method-icon" style="color:#5f259f;">📱</div>
                    <div>
                        <strong>PhonePe</strong>
                        <p style="font-size: 0.75rem; color:#888;">Pay via PhonePe UPI</p>
                    </div>
                </label>
                
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="GooglePay" required>
                    <div class="method-icon" style="color:#4285f4;">💰</div>
                    <div>
                        <strong>Google Pay</strong>
                        <p style="font-size: 0.75rem; color:#888;">Quick checkout with GPay</p>
                    </div>
                </label>
                
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="COD" required checked>
                    <div class="method-icon">🚚</div>
                    <div>
                        <strong>Cash on Delivery</strong>
                        <p style="font-size: 0.75rem; color:#888;">Pay when you receive</p>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="btn-primary" style="margin-top: 30px;">Place Order</button>
            <a href="index.php" class="auth-footer" style="display:block; text-align:center; text-decoration:none; margin-top:15px; font-size:0.85rem;">Cancel and Return</a>
        </form>
    </div>
</body>
</html>
