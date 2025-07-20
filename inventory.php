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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fisherman') {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$current_page = basename($_SERVER['PHP_SELF']);

$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fish_name'], $_POST['quantity'], $_POST['price'])) {
    $fish_name = $conn->real_escape_string($_POST['fish_name']);
    $quantity = (int) $_POST['quantity'];
    $price = (float) $_POST['price'];
    $image_path = NULL;

    if (isset($_FILES['fish_image']) && $_FILES['fish_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['fish_image']['tmp_name'];
        $file_name = $_FILES['fish_image']['name'];
        $file_size = $_FILES['fish_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        $max_file_size = 5 * 1024 * 1024;

        if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
            $new_file_name = uniqid('fish_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp_name, $destination)) {
                $image_path = $conn->real_escape_string($destination);
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file type or size. Allowed: JPG, JPEG, PNG, GIF (max 5MB).";
        }
    }

    $stmt = $conn->prepare("INSERT INTO fish_inventory (user_id, fish_name, quantity, price, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $user_id, $fish_name, $quantity, $price, $image_path);
    if ($stmt->execute()) {
        $action = $conn->real_escape_string("Fisherman '$username' added '$fish_name' (Qty: $quantity, Price: ₱$price)");
        $conn->query("INSERT INTO activity_log (user_id, action) VALUES ($user_id, '$action')");
    } else {
        echo "Error adding fish: " . $stmt->error;
    }
    $stmt->close();
    header("Location: inventory.php");
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $fish_id = (int) $_GET['delete'];
    $res = $conn->query("SELECT fish_name, quantity, image_path FROM fish_inventory WHERE id = $fish_id AND user_id = $user_id");
    if ($res && $res->num_rows > 0) {
        $fish = $res->fetch_assoc();
        $fish_name = $conn->real_escape_string($fish['fish_name']);
        $quantity = (int) $fish['quantity'];
        $old_image_path = $fish['image_path'];
        $conn->query("DELETE FROM fish_inventory WHERE id = $fish_id AND user_id = $user_id");
        if ($old_image_path && file_exists($old_image_path)) {
            unlink($old_image_path);
        }
        $action = $conn->real_escape_string("Fisherman '$username' deleted '$fish_name' (Qty: $quantity)");
        $conn->query("INSERT INTO activity_log (user_id, action) VALUES ($user_id, '$action')");
    }
    header("Location: inventory.php");
    exit();
}

$fish_result = $conn->query("
    SELECT f.*, u.username AS fisherman_name 
    FROM fish_inventory f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.user_id = $user_id 
    ORDER BY f.date_added DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fisherman's Inventory</title>
    <link rel="icon" href="fav.png" type="image/x-icon"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { margin: 0; background: #cdd9ec; color: #333; display: flex; }

        .sidebar {
            width: 80px; background: #cdd9ec; height: 100vh;
            transition: width 0.3s; overflow: hidden; position: fixed;
            display: flex; flex-direction: column; align-items: flex-start;
            padding-top: 20px;
            box-shadow: 8px 8px 16px #b1b9c9, -8px -8px 16px #e9f1ff;
            border-radius: 0 30px 30px 0;
        }

        .sidebar:hover { width: 230px; }

        .sidebar-title {
            width: 100%; text-align: center; font-weight: bold;
            padding: 10px; font-size: 20px; color: #004466;
        }

        .sidebar a {
            width: 80%; height: 60px; margin: 8px 7px; padding: 0 20px;
            background: #cdd9ec; border-radius: 50px;
            display: flex; align-items: center; text-decoration: none;
            transition: 0.3s;
            box-shadow: 6px 6px 12px #b1b9c9, -6px -6px 12px #e9f1ff;
        }

        .sidebar a i { font-size: 20px; margin-right: 5px; }
        .sidebar a span {
            color: #004466; font-size: 15px; font-weight: 500;
            white-space: nowrap; opacity: 0; transition: opacity 0.3s ease;
        }

        .sidebar:hover a span { opacity: 1; }
        .sidebar a.active {
            background: #cdd9ec;
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
            font-weight: bold;
        }

        .sidebar a.active i, .sidebar a.active span { color: #002233; }

        .main { margin-left: 80px; flex: 1; padding: 40px; }

        h2 { color: #004466; margin-bottom: 20px; }

        form {
            margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 10px;
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="file"] {
            padding: 10px 14px; border-radius: 20px; border: none;
            width: calc(50% - 5px);
            background: #cdd9ec;
            box-shadow: inset 3px 3px 6px #b1b9c9, inset -3px -3px 6px #e9f1ff;
        }

        form input[type="file"] { padding-top: 8px; cursor: pointer; }

        form button {
            padding: 10px 18px; border-radius: 20px; border: none;
            background: #004466; color: white; cursor: pointer;
            font-weight: bold; width: 100%;
        }

        table {
            width: 100%; border-collapse: collapse; background: #cdd9ec;
            border-radius: 20px; overflow: hidden;
            box-shadow: 8px 8px 16px #b1b9c9, -8px -8px 16px #e9f1ff;
        }

        th, td {
            padding: 14px; text-align: left; font-size: 14px;
        }

        th { background: #e0eaff; color: #004466; }

        tr:nth-child(even) { background: #e7f0fa; }

        .delete-btn {
            background: #cdd9ec; color: #333; padding: 6px 12px;
            border: none; border-radius: 20px; cursor: pointer;
            box-shadow: 4px 4px 8px #b1b9c9, -4px -4px 8px #e9f1ff;
        }

        .delete-btn:hover {
            box-shadow: inset 3px 3px 6px #b1b9c9, inset -3px -3px 6px #e9f1ff;
        }

        .fish-image {
            width: 50px; height: 50px; object-fit: cover;
            border-radius: 5px; vertical-align: middle; margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">FL</div>

    <a href="inventory.php" class="<?= $current_page == 'inventory.php' && !isset($_GET['sales']) ? 'active' : '' ?>">
        <i>🐟</i><span>Inventory</span>
    </a>

    <a href="?sales=1" class="<?= isset($_GET['sales']) ? 'active' : '' ?>">
        <i>💰</i><span>Sales</span>
    </a>

    <a href="logout.php">
        <i>🚪</i><span>Log Out</span>
    </a>
</div>

<div class="main">
    <?php if (isset($_GET['sales'])): 
    $sales_query = "
        SELECT o.*, u.username 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.fish_name IN (
            SELECT fish_name FROM fish_inventory WHERE user_id = $user_id
        )
        ORDER BY o.order_date DESC
    ";
    $sales_result = $conn->query($sales_query);
    $total_sales = 0;
?>
    <h2>Sales Summary</h2>
    <table>
        <tr>
            <th>Buyer</th>
            <th>Fish Name</th>
            <th>Quantity</th>
            <th>Price (₱)</th>
            <th>Subtotal (₱)</th>
        </tr>
        <?php while ($order = $sales_result->fetch_assoc()): 
            $subtotal = $order['price'] * $order['quantity'];
            $total_sales += $subtotal;
        ?>
            <tr>
                <td><?= htmlspecialchars($order['username']) ?></td>
                <td><?= htmlspecialchars($order['fish_name']) ?></td>
                <td><?= $order['quantity'] ?></td>
                <td>₱<?= number_format($order['price'], 2) ?></td>
                <td>₱<?= number_format($subtotal, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <th colspan="4">Total Revenue:</th>
            <th style="color: green;">₱<?= number_format($total_sales, 2) ?></th>
        </tr>
    </table>

    <?php else: ?>
        <h2>My Fish Inventory</h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="fish_name" placeholder="Fish name" required>
            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="number" name="price" placeholder="Price (₱)" required>
            <input type="file" name="fish_image" accept="image/*">
            <button type="submit">Add Fish</button>
        </form>

        <table>
            <tr>
                <th>Fish Name</th>
                <th>Quantity</th>
                <th>Price (₱)</th>
                <th>Image</th>
                <th>Date Added</th>
                <th>Action</th>
            </tr>
            <?php while ($fish = $fish_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($fish['fish_name']) ?></td>
                    <td><?= $fish['quantity'] ?></td>
                    <td>₱<?= number_format($fish['price'], 2) ?></td>
                    <td>
                        <?php if ($fish['image_path']): ?>
                            <img src="<?= htmlspecialchars($fish['image_path']) ?>" alt="<?= htmlspecialchars($fish['fish_name']) ?>" class="fish-image">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td><?= $fish['date_added'] ?></td>
                    <td>
                        <a href="?delete=<?= $fish['id'] ?>" onclick="return confirm('Are you sure you want to delete this?');">
                            <button class="delete-btn">🗑️ Delete</button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
