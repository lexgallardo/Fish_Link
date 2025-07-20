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
$username = $_SESSION['username']; 

$fish_query = "
    SELECT f.*, u.username AS fisherman_name 
    FROM fish_inventory f 
    JOIN users u ON f.user_id = u.id 
    ORDER BY f.date_added DESC
";
$fish_result = $conn->query($fish_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $cartItems = json_decode(stripslashes($_POST['cart']), true);
    $paymentMethod = $_POST['payment_method'];

    if (!empty($cartItems)) {
        foreach ($cartItems as $item) {
            $fishName = $item['fishName'];
            $quantityOrdered = (int)$item['quantity'];
            $price = (float)$item['price'];

            // Extract just the fish name if it's in the format "FishName - \u20b1Price"
            $cleanFishName = trim(explode(' - ', $fishName)[0]);

            // 1. Insert to orders table
            $stmt = $conn->prepare("INSERT INTO orders (user_id, fish_name, quantity, price, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isid", $customer_id, $cleanFishName, $quantityOrdered, $price);
            $stmt->execute();
            $stmt->close();

            // 2. Update fish_inventory: subtract ordered quantity
            $updateStmt = $conn->prepare("UPDATE fish_inventory SET quantity = quantity - ? WHERE fish_name = ? AND quantity >= ?");
            $updateStmt->bind_param("isi", $quantityOrdered, $cleanFishName, $quantityOrdered);
            $updateStmt->execute();

            // 3. Log if update didn't go through
            if ($updateStmt->affected_rows === 0) {
                error_log("\u274c Quantity not updated for $cleanFishName — maybe not enough stock or name mismatch?");
            }

            $updateStmt->close();
        }
    }

    echo "<script>alert('Order placed successfully!'); window.location.href='customer_inventory.php';</script>";
    exit();
}
?>

<!-- The rest of the HTML and frontend logic remains unchanged -->


<!DOCTYPE html>
<html>
<head>
    <title>Available Fish - FishLink</title>
    <link rel="icon" href="fav.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            margin: 0;
            background: #cdd9ec;
            color: #333;
            display: flex;
        }

        .sidebar {
            width: 80px;
            background: #cdd9ec;
            height: 100vh;
            transition: width 0.3s;
            overflow: hidden;
            position: fixed;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-top: 20px;
            box-shadow: 8px 8px 16px #b1b9c9, -8px -8px 16px #e9f1ff;
            border-radius: 0 30px 30px 0;
        }

        .sidebar:hover {
            width: 230px;
        }

        .sidebar-title {
            width: 100%;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            font-size: 20px;
            color: #004466;
        }

        .sidebar a {
            width: 80%;
            height: 60px;
            margin: 8px 7px;
            padding: 0 20px;
            background: #cdd9ec;
            border-radius: 50px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: 0.3s;
            box-shadow: 6px 6px 12px #b1b9c9, -6px -6px 12px #e9f1ff;
        }

        .sidebar a i {
            font-size: 20px;
            margin-right: 5px;
        }

        .sidebar a span {
            color: #004466;
            font-size: 15px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar:hover a span {
            opacity: 1;
        }

        .sidebar a.active {
            background: #cdd9ec;
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
            font-weight: bold;
        }

        .sidebar a.active i,
        .sidebar a.active span {
            color: #002233;
        }

        .main {
            margin-left: 80px; 
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center; 
        }

        h2 {
            color: #004466;
            margin-bottom: 20px;
        }

        .fish-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center; 
        }

        .fish-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 200px;
            text-align: center;
            transition: transform 0.3s; 
            cursor: pointer; 
        }

        .fish-box:hover {
            transform: scale(1.05);
        }

        .fish-image {
            width: 100%;
            height: auto;
            border-radius: 5px;
            object-fit: cover; 
        }

        .fish-name {
            font-weight: bold;
            margin: 10px 0;
        }

        .fish-price {
            color: #333;
        }

        .fish-fisherman {
            color: #555;
            font-size: 14px;
        }

        .fish-date {
            color: #777;
            font-size: 12px;
        }

        .available-quantity {
            color: #007BFF;
            font-weight: bold;
        }

        .cart-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px; 
            border: 1px solid #888;
            width: 500px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .cart-items {
            margin-top: 20px;
            max-height: 300px; 
            overflow-y: auto; 
        }

       
        .add-to-cart-button {
            padding: 15px 30px; 
            border-radius: 20px;
            border: none;
            background: #004466;
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px; 
            transition: background 0.3s, transform 0.3s;
        }

        .add-to-cart-button:hover {
            background: #003355; 
            transform: scale(1.1); 
        }

        .checkout-button {
            padding: 15px 30px; 
            border-radius: 20px;
            border: none;
            background: #28a745; 
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px; 
            transition: background 0.3s, transform 0.3s; 
            margin-top: 20px; 
        }

        .checkout-button:hover {
            background: #218838; 
            transform: scale(1.1); 
        }

        .payment-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 35px;
            transition: transform 0.2s;
        }

        .payment-button img {
            width: 70px; 
            height: 50px; 
            transition: transform 0.3s; 
        }

        .payment-button:hover img {
            transform: scale(1.1); 
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">FL</div>

    <a href="customer_inventory.php" class="<?= $current_page == 'customer_inventory.php' ? 'active' : '' ?>">
        <i>🐟</i><span>Available Fish</span>
    </a>

    <a href="logout.php" onclick="logout()">
        <i>🚪</i><span>Log Out</span>
    </a>
</div>

<div class="main">
    <h2>Available Fish</h2>

    <div class="cart-icon" onclick="openCart()">
        🛒 Cart
    </div>

    <div class="fish-container">
        <?php while ($fish = $fish_result->fetch_assoc()): ?>
            <div class="fish-box" onclick="openModal('<?= htmlspecialchars($fish['fish_name']) ?>', <?= $fish['price'] ?>, <?= $fish['quantity'] ?>, '<?= htmlspecialchars($fish['image_path']) ?>')">
                <?php if ($fish['image_path']): ?>
                    <img src="<?= htmlspecialchars($fish['image_path']) ?>" alt="<?= htmlspecialchars($fish['fish_name']) ?>" class="fish-image">
                <?php else: ?>
                    <img src="placeholder.jpg" alt="No Image" class="fish-image"> 
                <?php endif; ?>
                <div class="fish-name"><?= htmlspecialchars($fish['fish_name']) ?></div>
                <div class="fish-price">Price: ₱<?= number_format($fish['price'], 2) ?></div>
                <div class="fish-fisherman">Fisherman: <?= htmlspecialchars($fish['fisherman_name']) ?></div>
                <div class="fish-date">Date Added: <?= htmlspecialchars($fish['date_added']) ?></div>
                <div class="available-quantity">Available: <?= $fish['quantity'] ?></div> 
            </div>
        <?php endwhile; ?>
    </div>
</div>


<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add to Cart</h2>
        <p id="fish-name"></p>
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" min="1" value="1">
        <button class="add-to-cart-button" onclick="addToCart()">Add to Cart</button> 
    </div>
</div>

<div id="cartModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCart()">&times;</span>
        <h2>Cart Items</h2>
        <div id="cart-items" class="cart-items"></div>
        <div class="payment-method">
            <p>Select Payment Method:</p>
            <button class="payment-button" onclick="selectPayment('gcash')">
                <img src="gcash.png" alt="GCash">
            </button>
            <button class="payment-button" onclick="selectPayment('credit_card')">
                <img src="credit_card.webp" alt="Credit Card">
            </button>
            <button class="payment-button" onclick="selectPayment('cash_on_delivery')">
                <img src="cash_on_delivery.jpg" alt="Cash on Delivery">
            </button>
        </div>
        <p id="selected-payment" style="margin-top: 10px;"></p>
        <p id="total-amount" style="font-weight: bold; margin-top: 10px;"></p> 
        <button class="checkout-button" onclick="checkout()">Checkout</button> 
    </div>
</div>

<script>
    let cart = [];
    let selectedPaymentMethod = '';

    function openModal(fishName, price, availableQuantity, imagePath) {
        document.getElementById("fish-name").innerText = fishName + " - ₱" + price.toFixed(2);
        document.getElementById("quantity").max = availableQuantity; 
        document.getElementById("myModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("myModal").style.display = "none";
    }

    function addToCart() {
        const quantity = parseInt(document.getElementById("quantity").value);
        const fishName = document.getElementById("fish-name").innerText;
        const price = parseFloat(document.getElementById("fish-name").innerText.split(" - ₱")[1]);
        const availableQuantity = parseInt(document.getElementById("quantity").max); 
       
        if (quantity > availableQuantity) {
            alert(`You cannot add more than ${availableQuantity} of ${fishName} to the cart.`);
            return; 
        }

       
        cart.push({ fishName, quantity, price });
        alert("Added " + quantity + " of " + fishName + " to cart.");
        closeModal();
    }

    function openCart() {
        const cartItemsDiv = document.getElementById("cart-items");
        cartItemsDiv.innerHTML = ""; 

        if (cart.length === 0) {
            cartItemsDiv.innerHTML = "<p>Your cart is empty.</p>";
            document.getElementById("total-amount").innerText = ""; 
        } else {
            let total = 0;
            cart.forEach(item => {
                cartItemsDiv.innerHTML += `<p>${item.quantity} x ${item.fishName} - ₱${(item.price * item.quantity).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>`;
                total += item.price * item.quantity; 
            });
            document.getElementById("total-amount").innerText = "Total: ₱" + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); // Display total amount
        }

        document.getElementById("cartModal").style.display = "block";
    }

    function closeCart() {
        document.getElementById("cartModal").style.display = "none";
    }

    function selectPayment(method) {
        selectedPaymentMethod = method;
        document.getElementById("selected-payment").innerText = "Selected Payment: " + method.charAt(0).toUpperCase() + method.slice(1).replace(/_/g, ' ');
    }

        function checkout() {
        if (cart.length === 0) {
            alert("Your cart is empty. Please add items to your cart before checking out.");
            return;
        }
        if (!selectedPaymentMethod) {
            alert("Please select a payment method before checking out.");
            return;
        }

        // Create form
        const form = document.createElement("form");
        form.method = "POST";
        form.style.display = "none";

        const cartInput = document.createElement("input");
        cartInput.name = "cart";
        cartInput.value = JSON.stringify(cart);
        form.appendChild(cartInput);

        const methodInput = document.createElement("input");
        methodInput.name = "payment_method";
        methodInput.value = selectedPaymentMethod;
        form.appendChild(methodInput);

        const flagInput = document.createElement("input");
        flagInput.name = "checkout";
        flagInput.value = "1";
        form.appendChild(flagInput);

        document.body.appendChild(form);
        form.submit();
    }


    function logout() {
        
        window.location.href = "signin_customer.php";
    }
</script>

</body>
</html>
