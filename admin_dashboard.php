<?php
session_start();

// DB connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'FishLink_DB';
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

// Delete user (GET action)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Get user details for logging
    $user_result = $conn->query("SELECT * FROM users WHERE id = $id AND role = 'fisherman'");
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $username = $conn->real_escape_string($user_data['username']);

        // Start transaction for data integrity
        $conn->begin_transaction();
        
        try {
            // Option 1: Delete related records first (Complete deletion)
            // Delete from fish_inventory first
            $conn->query("DELETE FROM fish_inventory WHERE user_id = $id");
            
            // Delete from other related tables if they exist
            // Add more DELETE statements here for other tables that reference users.id
            // Example:
            // $conn->query("DELETE FROM orders WHERE user_id = $id");
            // $conn->query("DELETE FROM user_reviews WHERE user_id = $id");
            
            // Finally delete the user
            $conn->query("DELETE FROM users WHERE id = $id AND role = 'fisherman'");

            // Log the deletion
            $admin_id = $_SESSION['user_id'];
            $log_action = "Deleted user '$username' and all related data";
            $conn->query("INSERT INTO admin_logs (admin_id, action) VALUES ($admin_id, '$log_action')");
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "Error deleting user: " . $e->getMessage();
        }
    }

    header("Location: admin_dashboard.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Count fishermen
$count_result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'fisherman'");
$total_users = $count_result->fetch_assoc()['total'];

// Count orders
$total_sales_result = $conn->query("SELECT COUNT(*) AS completed FROM orders WHERE status = 'completed'");
$pending_orders_result = $conn->query("SELECT COUNT(*) AS pending FROM orders WHERE status = 'pending'");
$total_sales = $total_sales_result->fetch_assoc()['completed'];
$pending_orders = $pending_orders_result->fetch_assoc()['pending'];

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_sql = $search ? "WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%'" : '';
$user_query = "SELECT * FROM users $filter_sql ORDER BY id DESC";
$users_result = $conn->query($user_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - FishLink</title>
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
            display: flex;
            color: #333;
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
        }

        .stat-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-box {
            flex: 1;
            background: #cdd9ec;
            padding: 30px;
            border-radius: 30px;
            box-shadow: 8px 8px 16px #b1b9c9, -8px -8px 16px #e9f1ff;
            text-align: center;
            font-size: 20px;
            color: #004466;
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
            margin: 2px;
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

        .search-box {
            margin: 20px 0;
            text-align: right;
        }

        .search-box input[type="text"] {
            padding: 10px;
            border-radius: 20px;
            border: none;
            width: 250px;
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
        }

        h2 {
            color: #004466;
            margin-top: 40px;
        }

        .sidebar-title {
            width: 100%;
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
    <div class="stat-cards">
        <div class="stats-box">
            <h3>Total Fishermen</h3>
            <p><?= $total_users ?></p>
        </div>
        <div class="stats-box">
            <h3>Total Sales</h3>
            <p><?= $total_sales ?></p>
        </div>
        <div class="stats-box">
            <h3>Pending Orders</h3>
            <p><?= $pending_orders ?></p>
        </div>
    </div>

    <div class="search-box">
        <form method="GET" action="admin_dashboard.php">
            <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <h2>Registered Users</h2>
    <table>
        <tr>
            <th>First</th>
            <th>Last</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $users_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($user['first_name']) ?></td>
                <td><?= htmlspecialchars($user['last_name']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <a href="#"><button class="action-btn">Edit</button></a>
                    <?php if ($user['role'] !== 'admin'): ?>
                        <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user? This will also delete all their fish inventory and related data.');">
                            <button class="action-btn">Delete</button>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>