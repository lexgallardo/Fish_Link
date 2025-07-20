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

// Admin only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch logs
$query = "
    SELECT a.*, u.username 
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.timestamp DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs - Admin</title>
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
            font-family: 'Poppins', sans-serif;
            color: #004466;
            display: flex;
        }

        .sidebar {
            width: 80px;
            background: #cdd9ec;
            height: 100vh;
            transition: width 0.3s;
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
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            color: #004466;
            background: #cdd9ec;
            box-shadow: 6px 6px 12px #b1b9c9, -6px -6px 12px #e9f1ff;
        }

        .sidebar a.active {
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
        }

        .sidebar a span {
            white-space: nowrap;
            margin-left: 10px;
            opacity: 0;
            transition: 0.3s;
        }

        .sidebar:hover a span {
            opacity: 1;
        }

        .main {
            margin-left: 80px;
            padding: 40px;
            flex: 1;
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
            padding: 12px 15px;
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

        h2 {
            color: #004466;
            margin-bottom: 20px;
        }

        .sidebar-title {
            width: 100%;
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-title">FL</div>
    <a href="admin_dashboard.php"><i>🛠️</i><span>Admin Panel</span></a>
    <a href="admin_fish.php"><i>🐟</i><span>Manage Fish</span></a>
    <a href="admin_logs.php" class="<?= $current_page == 'admin_logs.php' ? 'active' : '' ?>"><i>📄</i><span>Activity Logs</span></a>
    <a href="logout.php"><i>🚪</i><span>Log Out</span></a>
</div>

<div class="main">
    <h2>Activity Log</h2>
    <table>
        <tr>
            <th>User</th>
            <th>Action</th>
            <th>Table</th>
            <th>Target ID</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= htmlspecialchars($row['target_table']) ?></td>
                <td><?= $row['target_id'] ?></td>
                <td><?= $row['timestamp'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>