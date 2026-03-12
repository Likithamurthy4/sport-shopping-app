<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Remove from wishlist
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")->execute([$user_id, (int)$_GET['remove']]);
    header("Location: wishlist.php");
    exit();
}

// Fetch wishlist products
$stmt = $pdo->prepare("SELECT p.* FROM products p JOIN wishlist w ON p.id = w.product_id WHERE w.user_id = ?");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist | Sports App</title>
    <link rel="stylesheet" href="style.css?v=1.4">
</head>
<body>
    <div class="dashboard">
        <div style="margin-bottom: 30px; display:flex; justify-content: space-between; align-items: center;">
            <h2 style="margin:0; text-align: left;">My <span style="color: var(--primary-color);">Wishlist</span></h2>
            <a href="index.php" class="btn-secondary" style="border-radius: 30px;">Back to Shop</a>
        </div>

        <?php if (empty($products)): ?>
            <div style="text-align:center; padding: 60px; background: white; border-radius: 20px;">
                <p style="color: #777; font-size: 1.1rem;">Your wishlist is currently empty.</p>
                <br>
                <a href="index.php" class="btn-primary" style="display:inline-block; width: auto; text-decoration: none;">Go Explore</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" style="text-decoration:none; color:inherit;">
                            <div class="product-image">⚽</div>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p style="color: var(--primary-color); font-weight: 700;">₹<?php echo number_format($product['price'], 2); ?></p>
                        </a>
                        <div style="display:flex; gap:10px; margin-top:15px;">
                            <form action="add_to_cart.php" method="POST" style="flex:1;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn-primary" style="padding: 8px; font-size: 0.8rem;">Add to Cart</button>
                            </form>
                            <a href="wishlist.php?remove=<?php echo $product['id']; ?>" class="btn-secondary" style="padding: 8px; font-size: 0.8rem; color: #e74c3c;">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
