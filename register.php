<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $age = (int)$_POST['age'];
    $address = trim($_POST['address']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, gender, age, address, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $phone, $gender, $age, $address, $password]);
        
        echo "<script>alert('Registration successful!'); window.location.href='login.html';</script>";
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Username or Email already exists.'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
