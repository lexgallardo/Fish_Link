<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>About Us - FishLink</title>
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
            max-width: 800px;
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
        }

        .content-box ul {
            list-style-type: none;
            padding: 0;
            margin-top: 25px;
            font-size: 16px;
        }

        .content-box li {
            margin: 15px 0;
            font-weight: 500;
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
        <h2>Why FishLink?</h2>
        <p>
            FishLink connects consumers and producers through an inventory management system. Whether you're a fisherman or a seafood lover, we bridge the gap with fair pricing and real-time tracking.
        </p>
        <ul>
            <li><strong>Direct Sales:</strong> Sell directly and earn the full value of your catch.</li>
            <li><strong>Inventory Tracking:</strong> Real-time updates on your catch and materials.</li>
            <li><strong>Fair Pricing:</strong> No middlemen, better prices for all.</li>
            <li><strong>Our Commitment:</strong> We support the fishing community with tools to maximize earnings and deliver fresh seafood directly to homes.</li>
        </ul>
    </div>
</div>

</body>
</html>