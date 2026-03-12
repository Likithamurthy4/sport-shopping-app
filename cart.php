<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Remove item from cart
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    header("Location: cart.php");
    exit();
}

// Update quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_qty'])) {
    $cart_id = (int)$_POST['cart_id'];
    $new_qty = (int)$_POST['quantity'];
    if ($new_qty > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_qty, $cart_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    }
    header("Location: cart.php");
    exit();
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.id as cart_id, p.name, p.price, c.quantity, p.id as p_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Sports Advanced</title>
    <link rel="stylesheet" href="style.css?v=1.2">
    <style>
        .dashboard { max-width: 900px; margin: 0 auto; width: 100%; border-radius: 20px; }
        .cart-container { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .qty-input { width: 60px; padding: 5px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .remove-link { color: #ff0000; text-decoration: none; font-size: 0.8rem; font-weight: 600; }
        .remove-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="cart-container">
            <h2 style="text-align:left; margin-bottom:20px;">Your <span style="color:var(--primary-color)">Advanced Cart</span></h2>
            
            <?php if (empty($cart_items)): ?>
                <div style="text-align:center; padding: 40px;">
                    <p style="color:#777; font-size:1.1rem;">Your cart is empty.</p>
                    <br>
                    <a href="index.php" class="btn-primary" style="display:inline-block; width:auto; text-decoration:none;">Go Shopping</a>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <input type="number" name="quantity" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                        <button type="submit" name="update_qty" style="display:none;"></button>
                                    </form>
                                </td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td><a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="remove-link">Remove</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-total">
                    <div>
                        <p style="color:#777;">Grand Total</p>
                        <h3>₹<?php echo number_format($total, 2); ?></h3>
                    </div>
                    <div style="display:flex; gap:15px;">
                        <a href="index.php" class="btn-secondary">Continue Shopping</a>
                        <form action="checkout.php" method="POST">
                            <input type="hidden" name="total" value="<?php echo $total; ?>">
                            <button type="submit" class="btn-primary">Proceed to Checkout</button>
                        </form>
                    </div>
                </div>
                <p style="font-size: 0.8rem; color: #999; margin-top: 15px;">Tip: Change quantity and press Enter to update.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
