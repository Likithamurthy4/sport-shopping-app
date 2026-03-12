<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];

    try {
        // Check if item already exists in user's cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $new_quantity = $existing['quantity'] + 1;
            $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $existing['id']]);
        } else {
            $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $insert_stmt->execute([$user_id, $product_id]);
        }

        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
