<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: index.php");
    exit();
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch reviews
$stmt_rev = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt_rev->execute([$product_id]);
$reviews = $stmt_rev->fetchAll();

// Handle Review Submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)")->execute([$product_id, $user_id, $rating, $comment]);
    
    // Update Average Rating
    $pdo->prepare("UPDATE products SET rating_avg = (SELECT AVG(rating) FROM reviews WHERE product_id = ?) WHERE id = ?")->execute([$product_id, $product_id]);
    
    header("Location: product_details.php?id=$product_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | Sports App</title>
    <link rel="stylesheet" href="style.css?v=1.4">
    <style>
        .details-container { max-width: 900px; margin: 40px auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .product-image-large { background: #f8f8fb; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 8rem; min-height: 400px; }
        .star-rating { color: #f1c40f; font-size: 1.2rem; }
        .review-card { padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 15px; }
        .review-form { background: #f9f9f9; padding: 25px; border-radius: 15px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn-secondary" style="border-radius: 30px;">← Back to Shop</a>
        </div>

        <div class="details-container">
            <div class="product-image-large">
                <?php 
                $emoji = "⚽"; // Simplification for demo
                echo $emoji;
                ?>
            </div>
            
            <div class="product-info">
                <span style="color: #888; text-transform: uppercase; font-size: 0.8rem;"><?php echo $product['category']; ?></span>
                <h1 style="margin: 10px 0;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="star-rating" style="margin-bottom: 15px;">
                    <?php 
                    $stars = round($product['rating_avg']);
                    for($i=1; $i<=5; $i++) echo $i <= $stars ? '★' : '☆';
                    ?>
                    <span style="color: #666; font-size: 0.9rem; margin-left:10px;">(<?php echo count($reviews); ?> Reviews)</span>
                </div>

                <p style="color: #555; line-height: 1.6; margin-bottom: 25px;"><?php echo htmlspecialchars($product['description']); ?></p>

                <div style="margin-bottom: 30px;">
                    <?php if ($product['is_on_sale']): ?>
                        <h2 style="text-align: left; color: var(--primary-color);">
                            <span style="text-decoration:line-through; color:#aaa; font-size: 1.2rem; margin-right:15px;">₹<?php echo number_format($product['price'], 0); ?></span>
                            ₹<?php echo number_format($product['sale_price'], 0); ?>
                        </h2>
                    <?php else: ?>
                        <h2 style="text-align: left; color: var(--primary-color);">₹<?php echo number_format($product['price'], 2); ?></h2>
                    <?php endif; ?>
                </div>

                <form action="add_to_cart.php" method="POST" style="margin-bottom: 10px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="btn-primary" style="padding: 15px;">Add to Cart</button>
                </form>
            </div>
        </div>

        <div style="max-width: 900px; margin: 40px auto;">
            <h2>User <span style="color: var(--primary-color);">Reviews</span></h2>
            
            <div class="review-form">
                <h3>Write a Review</h3>
                <form method="POST">
                    <input type="hidden" name="submit_review" value="1">
                    <div class="input-group">
                        <label>Rating</label>
                        <select name="rating" required>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Comment</label>
                        <textarea name="comment" rows="3" placeholder="What did you think of this product?"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width: auto; padding: 10px 30px;">Post Review</button>
                </form>
            </div>

            <div style="margin-top: 30px;">
                <?php if (empty($reviews)): ?>
                    <p style="color: #888;">No reviews yet. Be the first to review!</p>
                <?php else: ?>
                    <?php foreach($reviews as $rev): ?>
                        <div class="review-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <strong><?php echo htmlspecialchars($rev['username']); ?></strong>
                                <span class="star-rating"><?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?></span>
                            </div>
                            <p style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                            <span style="font-size: 0.7rem; color: #aaa;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
