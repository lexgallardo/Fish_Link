<?php
session_start();

// Direct DB connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'FishLink_DB';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Delete fish entry and log
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Get user details for logging
    $user_result = $conn->query("SELECT * FROM users WHERE id = $id AND role = 'fisherman'");
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $username = $conn->real_escape_string($user_data['username']);

        // Delete the user
        $conn->query("DELETE FROM users WHERE id = $id AND role = 'fisherman'");

        // Log the deletion
        $admin_id = $_SESSION['user_id'];
        $log_action = "Deleted user '$username'";
        $log_action_escaped = $conn->real_escape_string($log_action);
        $conn->query("INSERT INTO admin_logs (admin_id, action) VALUES ($admin_id, '$log_action_escaped')");
    }

    header("Location: admin_dashboard.php");
    exit();
}

// FILTER logic
$fish_name = isset($_GET['fish_name']) ? $conn->real_escape_string($_GET['fish_name']) : '';
$username = isset($_GET['username']) ? $conn->real_escape_string($_GET['username']) : '';
$date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';

$conditions = [];

if (!empty($fish_name)) $conditions[] = "fi.fish_name LIKE '%$fish_name%'";
if (!empty($username)) $conditions[] = "u.username LIKE '%$username%'";
if (!empty($date)) $conditions[] = "DATE(fi.date_added) = '$date'";

$where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

$query = "
    SELECT fi.id, fi.fish_name, fi.quantity, fi.date_added, u.username 
    FROM fish_inventory fi
    JOIN users u ON fi.user_id = u.id
    $where
    ORDER BY fi.date_added DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fish Inventory - Admin</title>
    <link rel="icon" href="fav.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
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
            font-size: 22px;
            font-weight: bold;
            color: #004466;
            margin-left: 22px;
            margin-bottom: 20px;
        }

        .sidebar a {
            width: 85%;
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
            margin-right: 10px;
            color: #004466;
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
        }

        h2 {
            color: #004466;
            margin-bottom: 20px;
        }

        form input[type="text"],
        form input[type="date"] {
            padding: 10px 14px;
            border-radius: 20px;
            border: none;
            outline: none;
            font-size: 14px;
            margin-right: 8px;
            background: #cdd9ec;
            box-shadow: inset 3px 3px 6px #b1b9c9, inset -3px -3px 6px #e9f1ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #cdd9ec;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 10px 10px 20px #b1b9c9, -10px -10px 20px #e9f1ff;
        }

        th, td {
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #e0eaff;
            color: #004466;
        }

        tr:nth-child(even) {
            background: #e7f0fa;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 20px;
            background: #cdd9ec;
            box-shadow: 4px 4px 8px #b1b9c9, -4px -4px 8px #e9f1ff;
            cursor: pointer;
            transition: 0.2s;
        }

        .action-btn:hover {
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">FL</div>

    <a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">
    <i>🛠️</i><span>Admin Panel</span>
    </a>

    <a href="admin_fish.php" class="<?= $current_page == 'admin_fish.php' ? 'active' : '' ?>">
        <i>🐟</i><span>Manage Fish</span>
    </a>

    <a href="admin_logs.php" class="<?= $current_page == 'admin_logs.php' ? 'active' : '' ?>">
        <i>📜</i><span>Activity Logs</span>
    </a>

    <a href="logout.php">
        <i>🚪</i><span>Log Out</span>
    </a>
</div>

<div class="main">
    <h2>All Fish Added by Fishermen</h2>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="fish_name" placeholder="Fish name..." value="<?= isset($_GET['fish_name']) ? htmlspecialchars($_GET['fish_name']) : '' ?>">
        <input type="text" name="username" placeholder="Username..." value="<?= isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '' ?>">
        <input type="date" name="date" value="<?= isset($_GET['date']) ? $_GET['date'] : '' ?>">
        <button type="submit" class="action-btn">🔍 Filter</button>
        <a href="admin_fish.php" class="action-btn" style="text-decoration: none; margin-left: 10px;">🔄 Reset</a>
    </form>

    <table>
        <tr>
            <th>Fish Name</th>
            <th>Quantity</th>
            <th>Added By</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php while ($fish = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($fish['fish_name']) ?></td>
                <td><?= $fish['quantity'] ?></td>
                <td><?= htmlspecialchars($fish['username']) ?></td>
                <td><?= $fish['date_added'] ?></td>
                <td>
                    <a href="?delete=<?= $fish['id'] ?>" onclick="return confirm('Are you sure to delete this fish entry?');">
                        <button class="action-btn">🗑️ Delete</button>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
