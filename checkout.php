<?php
session_start();
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'FishLink_DB';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['customer_id'])) {
    header("Location: signin_customer.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_data = json_decode($_POST['cart_data'], true);
    $payment_method = $_POST['payment_method'];

    if (!empty($cart_data)) {
        foreach ($cart_data as $item) {
            $fish_name = $conn->real_escape_string($item['fishName']);
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];

            $insert = "INSERT INTO orders (user_id, fish_name, quantity, price, status) 
                       VALUES ('$customer_id', '$fish_name', '$quantity', '$price', 'pending')";
            $conn->query($insert);
        }
    }

    header("Location: customer_inventory.php");
    exit();
}
?>
