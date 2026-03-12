<?php
require_once 'db_connect.php';

try {
    echo "<h2>Starting Resilient Database Repair...</h2>";

    // Disable foreign key checks to allow dropping and recreating tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Repair Users Table (Add role if missing)
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('customer', 'admin') DEFAULT 'customer' AFTER password");
        echo "✅ Added 'role' column to users table.<br>";
    } else {
        echo "ℹ️ 'role' column already exists in users.<br>";
    }

    // 2. Clear out the modern tables to ensure types match perfectly
    // (We do this because errno 150 usually means columns used in foreign keys don't match types)
    echo "Dropping and recreating modern tables to ensure compatibility...<br>";
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS cart");
    $pdo->exec("DROP TABLE IF EXISTS products");

    // 3. Create products table (Explicitly using INT for IDs)
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        stock INT DEFAULT 0,
        image VARCHAR(255) DEFAULT 'default.png',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "✅ Products table recreated.<br>";

    // 4. Create cart table
    $pdo->exec("CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✅ Cart table recreated.<br>";

    // 5. Create orders table
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
        payment_method VARCHAR(50) DEFAULT 'COD',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✅ Orders table recreated.<br>";
    
    // 6. Create order_items table
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        INDEX (order_id),
        INDEX (product_id),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✅ Order Items table recreated.<br>";

    // Enable foreign key checks back
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 7. Seed products (12 items)
    $pdo->exec("INSERT INTO products (name, category, price, stock, description) VALUES
        ('Premium Soccer Ball', 'Football', 2499.00, 50, 'High-quality professional soccer ball.'),
        ('Tennis Racket Pro', 'Tennis', 7499.00, 20, 'Lightweight carbon fiber tennis racket.'),
        ('Official Basketball', 'Basketball', 2999.00, 30, 'Standard size official match basketball.'),
        ('Boxing Gloves', 'Boxing', 3850.00, 15, 'Durable synthetic leather boxing gloves.'),
        ('Cricket Bat G1', 'Cricket', 5999.00, 10, 'Grade 1 English Willow cricket bat.'),
        ('Badminton Racket', 'Other', 1899.00, 40, 'Excellent choice for beginners and intermediates.'),
        ('Running Shoes X1', 'Other', 4500.00, 25, 'Breathable mesh running shoes with cushioning.'),
        ('Dumbbell Set 10kg', 'Other', 2200.00, 12, 'Pair of 5kg adjustable vinyl dumbbells.'),
        ('Yoga Mat Pro', 'Other', 1200.00, 35, 'Non-slip 6mm thick padded yoga mat.'),
        ('Football Cleats', 'Football', 3499.00, 18, 'Dynamic studs for superior turf grip.'),
        ('Boxing Punching Bag', 'Boxing', 6500.00, 5, 'Heavy-duty 4ft filled punching bag.'),
        ('Table Tennis Set', 'Other', 999.00, 50, '2 paddles and 3 high-quality balls.')");
    echo "✅ Expanded product catalog seeded (12 items).<br>";

    echo "<br><h3 style='color:green;'>All tables are now synchronized!</h3>";
    echo "<p>Please return to <a href='login.html'>Login Page</a> and try again.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>CRITICAL Error: " . $e->getMessage() . "</h3>";
    echo "<p>Error Detail: " . print_r($pdo->errorInfo(), true) . "</p>";
}
?>
