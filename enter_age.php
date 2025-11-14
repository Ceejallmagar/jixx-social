<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dob = $_POST['dob'] ?? null;

    if ($dob) {
        // Basic validation for YYYY-MM-DD format
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if ($d && $d->format('Y-m-d') === $dob) {
            $stmt = $db->pdo->prepare("UPDATE users SET dob = ? WHERE id = ?");
            $stmt->execute([$dob, $user['id']]);
        }
    }
    // Whether age was submitted or not, continue to the next step
    header('Location: suggest_friends.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Your Age - Jixx</title>
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
        .age-card {
            padding: 30px 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            animation: fadeInSlideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        .age-card h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .age-card p {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        .age-card input[type="date"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface-strong);
            color: var(--text);
            font-size: 1rem;
            margin-bottom: 24px;
            color-scheme: dark;
        }
        .age-card .btn {
            display: block;
            width: 100%;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="age-card card">
        <h1>What's your date of birth?</h1>
        <p>This information will not be public. It helps us personalize your experience.</p>
        <form method="POST">
            <input type="date" name="dob" required>
            <button type="submit" class="btn">Save and Continue</button>
            <a href="suggest_friends.php" class="btn" style="background: var(--surface-strong); color: var(--text); margin-top: 12px;">Skip</a>
        </form>
    </div>
</body>
</html>