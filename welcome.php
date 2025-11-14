<?php
session_start();

// If the user is not logged in, they shouldn't be on this page.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Jixx!</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--background);
            color: var(--text);
        }
        .welcome-card {
            text-align: center;
            padding: 40px;
            max-width: 550px;
            animation: fadeInSlideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        .welcome-card h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .welcome-card p {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 32px;
        }
        .welcome-card .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .welcome-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="welcome-card card">
        <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        <p>We're so glad to have you join the Jixx community. Let's get you started with a few quick steps.</p>
        <div>
            <a href="enter_age.php" class="btn">Get Started</a>
        </div>
    </div>
</body>
</html>