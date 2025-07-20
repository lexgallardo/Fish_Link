<?php
session_start();

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "FishLink_DB";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg = $error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT * FROM customers WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Insert new customer into the database
            $insert_stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $hashed_password);
            if ($insert_stmt->execute()) {
                $msg = "Signup successful! You can now sign in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Sign Up - FishLink</title>
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
        .sidebar-title {
            width: 100%;
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
        .main {
            margin-left: 80px;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        form {
            background: #cdd9ec;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 10px 10px 30px #b1b9c9, -10px -10px 30px #e9f1ff;
            width: 100%;
            max-width: 400px;
        }
        form h2 {
            text-align: center;
            color: #004466;
            margin-bottom: 25px;
        }
        input {
            width: 100%;
            padding: 12px 18px;
            margin: 10px 0;
            border: none;
            border-radius: 25px;
            background: #cdd9ec;
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            box-shadow: inset 2px 2px 5px #b1b9c9, inset -2px -2px 5px #e9f1ff;
        }
        button {
            width: 100%;
            padding: 12px;
            border-radius: 25px;
            border: none;
            margin-top: 10px;
            font-weight: bold;
            background: #cdd9ec;
            box-shadow: 4px 4px 8px #b1b9c9, -4px -4px 8px #e9f1ff;
            transition: 0.3s;
            cursor: pointer;
        }
        button:hover {
            box-shadow: inset 4px 4px 8px #b1b9c9, inset -4px -4px 8px #e9f1ff;
        }
        .msg {
            color: green;
            text-align: center;
            font-size: 14px;
        }
        .error {
            color: red;
            text-align: center;
            font-size: 14px;
        }
        .link-bottom {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .link-bottom a {
            color: #004466;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">FL</div>
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i>🏠</i><span>Home</span></a>
    <a href="signin_customer.php"><i>🔐</i><span>Sign In</span></a>
    <a href="signup_customer.php" class="active"><i>📝</i><span>Sign Up</span></a>
    <a href="about_us.php"><i>ℹ️</i><span>About Us</span></a>
</div>

<div class="main">
    <form method="post">
        <h2>Customer Sign Up</h2>
        <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Sign Up</button>
        <div class="link-bottom">
            Already have an account? <a href="signin_customer.php">Sign in</a>
        </div>
    </form>
</div>

</body>
</html>
