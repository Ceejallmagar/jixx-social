<?php
session_start();
require_once 'database.php';

$message = '';
$db = new Database();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    
    if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
        $message = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } else {
        // Check if email already exists
        if ($db->getUserByEmail($email)) {
            $message = 'An account with this email already exists.';
        } else {
            try {
                if ($db->createUser($email, $password, $firstName, $lastName)) {
                    $message = 'Account created successfully! You can now log in.';
                    // Auto-login after registration
                    $user = $db->getUserByEmail($email);
                    if ($user) {
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name']
                        ];
                        header('Location: welcome.php');
                        exit;
                    } else {
                        $message = 'Account created but login failed. Please try logging in manually.';
                    }
                } else {
                    $message = 'Failed to create account. Please try again.';
                }
            } catch (Exception $e) {
                $message = 'Error creating account: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register • Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <main class="auth">
        <div class="auth__card card">
            <div class="auth__header">
                <div class="logo"></div>
                <div class="brand">Jixx</div>
            </div>
            <?php if ($message !== ''): ?>
            <div class="auth__message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="auth__form" action="register.php">
                <label>
                    <span>First Name</span>
                    <input type="text" name="first_name" placeholder="John" required />
                </label>
                <label>
                    <span>Last Name</span>
                    <input type="text" name="last_name" placeholder="Doe" required />
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" placeholder="you@example.com" required />
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="••••••••" required minlength="6" />
                </label>
                <button type="submit" class="btn btn--primary">Create Account</button>
                <a class="btn btn--ghost" href="login.php">Already have an account?</a>
                <a class="btn btn--ghost" href="index.php">Back to Jixx</a>
            </form>
        </div>
    </main>
</body>
</html>
