<?php
require_once 'db_connect.php';

echo "<h1>Sports App Database Sync</h1>";

try {
    // 1. Ensure columns exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('customer', 'admin') DEFAULT 'customer'");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_on_sale BOOLEAN DEFAULT FALSE");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS sale_price DECIMAL(10, 2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3, 2) DEFAULT 0.00");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT 'img/default.png'");
    
    // 2. Create Reviews table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Create Wishlist table
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_product (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    echo "<p style='color:green;'>✓ Database tables (Reviews, Wishlist) and columns synced.</p>";

    // 2. Create Admin if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@sports.com', ?, 'admin')")->execute([$pass]);
        echo "<p style='color:green;'>✓ Admin account created: <b>admin / admin123</b></p>";
    } else {
        $pdo->exec("UPDATE users SET role = 'admin' WHERE username = 'admin'");
        echo "<p style='color:blue;'>ℹ Admin account already exists. Role updated.</p>";
    }

    // 3. Mark some items as SALE and add Images
    $pdo->exec("UPDATE products SET is_on_sale = 1, sale_price = price * 0.8 WHERE stock > 0 LIMIT 2");
    
    // 3. Mark some items as SALE
    $pdo->exec("UPDATE products SET is_on_sale = 1, sale_price = price * 0.8 WHERE stock > 0 LIMIT 2");
    
    echo "<p style='color:green;'>✓ Sale items updated (Images removed from display).</p>";

    echo "<hr><a href='login.html' style='padding:10px 20px; background:var(--primary-color, #e67e22); color:white; text-decoration:none; border-radius:5px;'>Go to Login</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
