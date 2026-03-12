<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Search and Category filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?)";
$params = ["%$search%", "%$search%"];

if ($category != '') {
    $query .= " AND category = ?";
    $params[] = $category;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get unique categories for filter
$cat_stmt = $pdo->query("SELECT DISTINCT category FROM products");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get cart count
$cart_count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$cart_count_stmt->execute([$_SESSION['user_id']]);
$cart_count = $cart_count_stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Accessories | Advanced Shop</title>
    <link rel="stylesheet" href="style.css?v=1.3">
    <style>
        .dashboard { max-width: 1200px; margin: 0 auto; width: 100%; }
        .shop-controls { display: flex; justify-content: space-between; margin-bottom: 20px; gap: 15px; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; display: flex; gap: 10px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .filter-box select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .product-card { background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 20px; text-align: center; display: flex; flex-direction: column; }
        .product-card h3 { margin: 10px 0; font-size: 1.2rem; }
        .stock-tag { font-size: 0.8rem; color: #777; margin-bottom: 10px; }
        .product-footer { margin-top: auto; display: flex; flex-direction: column; gap: 10px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header" style="background:#fff; border-bottom: 2px solid var(--primary-color); border-radius: 0 0 15px 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 30px; display:flex; justify-content: space-between; align-items: center;">
            <h1 style="margin:0;">Sports <span style="color:var(--primary-color)">Advanced</span></h1>
            <div style="display:flex; gap:15px; align-items:center;">
                <a href="cart.php" class="cart-header">
                    <span class="cart-icon">🛒</span>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <span style="font-weight:600; color:#333;">Cart</span>
                </a>
                <a href="order_history.php" class="btn-secondary" style="padding: 10px 15px; border-radius:30px;">Orders</a>
                <a href="profile.php" class="btn-secondary" style="padding: 10px 15px; border-radius:30px;">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="shop-controls">
            <form class="search-box" method="GET">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary" style="width:auto; padding: 0 20px;">Search</button>
            </form>
            <div class="filter-box">
                <form method="GET">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 50px;">
                    <p style="font-size: 1.2rem; color: #777;">No products found.</p>
                </div>
            <?php else: ?>
                <?php 
                // Fetch user's wishlist
                $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
                $wishlist_stmt->execute([$_SESSION['user_id']]);
                $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card" style="position:relative;">
                        <?php if (isset($product['is_on_sale']) && $product['is_on_sale']): ?>
                            <div style="position:absolute; top:15px; right:15px; background:#e74c3c; color:white; padding:5px 12px; border-radius:30px; font-size:0.75rem; font-weight:700; z-index:10; box-shadow:0 4px 10px rgba(231, 76, 60, 0.3);">SALE</div>
                        <?php endif; ?>
                        
                        <button onclick="toggleWishlist(<?php echo $product['id']; ?>, this)" class="wishlist-btn <?php echo in_array($product['id'], $user_wishlist) ? 'active' : ''; ?>" style="position:absolute; top:15px; left:15px; background:white; border:none; width:35px; height:35px; border-radius:50%; box-shadow:0 4px 8px rgba(0,0,0,0.1); cursor:pointer; color:#ccc; display:flex; align-items:center; justify-content:center; z-index:11;">
                            ❤
                        </button>

                        <a href="product_details.php?id=<?php echo $product['id']; ?>" style="text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%;">
                            <div class="product-image" style="height: 120px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 3.5rem; transition: transform 0.3s ease;">
                                <?php 
                                    $category_icons = [
                                        'Cricket' => '🏏',
                                        'Football' => '⚽',
                                        'Tennis' => '🎾',
                                        'Basketball' => '🏀',
                                        'Boxing' => '🥊',
                                        'Chess' => '♟️',
                                        'Other' => '🏅'
                                    ];
                                    echo $category_icons[$product['category']] ?? '📦';
                                ?>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #888; text-transform: uppercase;"><?php echo $product['category']; ?></span>
                                <h3 style="margin: 5px 0; font-size: 1.1rem;"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div style="color: #f1c40f; font-size: 0.8rem; margin: 5px 0;">
                                    <?php 
                                    $stars = round($product['rating_avg'] ?? 0);
                                    for($i=1; $i<=5; $i++) echo $i <= $stars ? '★' : '☆';
                                    ?>
                                </div>
                            </div>
                            <div style="margin-top:auto;">
                                <?php if (isset($product['is_on_sale']) && $product['is_on_sale']): ?>
                                    <p style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">
                                        <span style="text-decoration:line-through; color:#aaa; font-size:1rem; margin-right:8px;">₹<?php echo number_format($product['price'], 0); ?></span>
                                        ₹<?php echo number_format($product['sale_price'], 0); ?>
                                    </p>
                                <?php else: ?>
                                    <p style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">₹<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>

                                <p style="font-size: 0.75rem; color: <?php echo $product['stock'] > 0 ? '#4caf50' : '#f44336'; ?>; margin-bottom: 15px;">
                                    <?php echo $product['stock'] > 0 ? 'In Stock ('.$product['stock'].')' : 'Out of Stock'; ?>
                                </p>
                            </div>
                        </a>
                        
                        <?php if($product['stock'] > 0): ?>
                            <div style="display: flex; gap: 8px;">
                                <form action="add_to_cart.php" method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-secondary" style="width: 100%; padding: 8px; font-size: 0.8rem; border-radius: 8px;">Add to Cart</button>
                                </form>
                                <form action="checkout.php" method="POST" style="flex: 1.2;">
                                    <input type="hidden" name="buy_now" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-primary" style="width: 100%; padding: 8px; font-size: 0.8rem; border-radius: 8px;">Buy Now</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <button class="btn-secondary" disabled style="width: 100%; opacity: 0.5;">Out of Stock</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleWishlist(productId, btn) {
        fetch('toggle_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'added') btn.classList.add('active');
            else btn.classList.remove('active');
        });
    }
    </script>
</body>
</html>
