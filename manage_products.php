<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized Access!");
}

// Handle Add Product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = trim($_POST['description']);

    $pdo->prepare("INSERT INTO products (name, category, price, stock, description) VALUES (?, ?, ?, ?, ?)")->execute([$name, $category, $price, $stock, $description]);
    header("Location: manage_products.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: manage_products.php");
    exit();
}

// Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products | Sports Advanced</title>
    <link rel="stylesheet" href="style.css?v=1.2">
    <style>
        .dashboard { max-width: 1100px; margin: 0 auto; width: 100%; border-radius: 20px; }
        .admin-form { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #eee; margin-bottom: 40px; }
        .admin-form h3 { margin-bottom: 20px; color: var(--primary-color); }
        .stock-level { font-weight: 700; }
        .stock-low { color: #f44336; }
        .stock-good { color: #4caf50; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Manage <span style="color:var(--primary-color)">Inventory</span></h2>
            <a href="admin_dashboard.php" class="btn-secondary" style="border-radius:30px;">Back to Dashboard</a>
        </div>

        <div class="admin-form">
            <h3>Add New Product</h3>
            <form method="POST">
                <input type="hidden" name="add_product" value="1">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="input-group">
                        <label>Product Name</label>
                        <input type="text" name="name" required placeholder="e.g. Cricket Bat">
                    </div>
                    <div class="input-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Football">Football</option>
                            <option value="Tennis">Tennis</option>
                            <option value="Basketball">Basketball</option>
                            <option value="Boxing">Boxing</option>
                            <option value="Cricket">Cricket</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>
                    <div class="input-group">
                        <label>Original Stock</label>
                        <input type="number" name="stock" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Description</label>
                    <textarea name="description" required rows="3"></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width:auto; padding: 12px 40px;">Add Product</button>
            </form>
        </div>

        <table class="admin-table cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td><?php echo $p['category']; ?></td>
                    <td>₹<?php echo number_format($p['price'], 2); ?></td>
                    <td><span class="stock-level <?php echo $p['stock'] < 10 ? 'stock-low' : 'stock-good'; ?>"><?php echo $p['stock']; ?></span></td>
                    <td><a href="manage_products.php?delete=<?php echo $p['id']; ?>" style="color: red; text-decoration: none; font-weight: 600;" onclick="return confirm('Delete this product?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
