<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - FishLink</title>
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
            overflow: hidden;
        }

        .sidebar a i {
            font-size: 20px;
            margin-right: 5px;
            min-width: 10px;
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
            color: #002233;
        }

        .sidebar a.active i,
        .sidebar a.active span {
            color: #002233;
        }

        .main {
            margin-left: 80px;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
        }

        .content-box {
            background: #cdd9ec;
            padding: 60px;
            border-radius: 40px;
            max-width: 700px;
            box-shadow: 12px 12px 30px #b1b9c9, -12px -12px 30px #e9f1ff;
        }

        h2 {
            font-size: 36px;
            color: #004466;
            margin-bottom: 20px;
        }

        .content-box p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .start-btn a button {
            padding: 15px 40px;
            border-radius: 30px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            background: #cdd9ec;
            box-shadow: 6px 6px 12px #b1b9c9, -6px -6px 12px #e9f1ff;
            cursor: pointer;
            transition: 0.3s;
        }

        .start-btn a button:hover {
            box-shadow: inset 6px 6px 12px #b1b9c9, inset -6px -6px 12px #e9f1ff;
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
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i>🏠</i><span>Dashboard</span></a>
    <a href="signin.php" class="<?= $current_page == 'signin.php' ? 'active' : '' ?>"><i>🔐</i><span>Sign In</span></a>
    <a href="signup.php" class="<?= $current_page == 'signup.php' ? 'active' : '' ?>"><i>📝</i><span>Sign Up</span></a>
    <a href="about_us.php" class="<?= $current_page == 'about_us.php' ? 'active' : '' ?>"><i>ℹ️</i><span>About Us</span></a>
</div>

<div class="main">
    <div class="content-box">
        <h2>Welcome to FishLink!</h2>
        <p>
            FishLink offers inventory services to our fellow fishermen, allowing seamless monitoring of stocks and catches. Our app helps eliminate middlemen and lets you sell for full value.
        </p>
        <div class="start-btn">
            <a href="signup_customer.php">
                <button>Get Started</button>
            </a>
        </div>
    </div>
</div>

</body>
</html>