<?php
session_start();
require_once 'database.php';

$message = '';
$db = new Database();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Check for remember me cookie
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $user = $db->verifyRememberMeToken($token);
    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ];
        header('Location: index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($email === '' || $password === '') {
        $message = 'Please enter both email and password.';
    } else {
        $user = $db->verifyPassword($email, $password);
        if ($user) {
            // Set session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ];

            // Handle "Remember Me"
            if (isset($_POST['remember_me'])) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $token = $selector . ':' . $validator;
                $hashedValidator = hash('sha256', $validator);
                $expires = new DateTime('+30 days');

                $db->createRememberMeToken($user['id'], $selector, $hashedValidator, $expires->format('Y-m-d H:i:s'));

                // Set cookie
                setcookie('remember_me', $token, $expires->getTimestamp(), '/', '', false, true); // Set secure to true in production with HTTPS
            }

            // Redirect to index
            header('Location: index.php');
            exit;
        } else {
            $message = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login • Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            <form method="POST" class="auth__form" action="login.php">
                <label>
                    <span>Email</span>
                    <input type="email" name="email" placeholder="you@example.com" required />
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="••••••••" required />
                </label>
                <label class="auth__remember">
                    <input type="checkbox" name="remember_me" />
                    <span>Remember me</span>
                </label>
            <button type="submit" class="btn btn--primary">Sign in</button>
            <a class="btn btn--ghost" href="register.php">Create Account</a>
            <a class="btn btn--ghost" href="index.php">Back to Jixx</a>
        </form>
        <div class="auth__divider">
            <span>or</span>
        </div>
        <div id="g_id_onload"
            data-client_id="18539381861-kpg6am590kaakqj22fd57g4j8tdd5f28.apps.googleusercontent.com"
            data-login_uri="http://localhost/jix_app/google_login.php"
            data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
            data-type="standard"
            data-shape="rectangular"
            data-theme="outline"
            data-text="sign_in_with"
            data-size="large"
            data-logo_alignment="left">
        </div>
    </div>
</main>
<script>
    function handleCredentialResponse(response) {
        // Send the ID token to the server for verification
        fetch('google_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id_token: response.credential }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                alert('Login failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during login.');
        });
    }
</script>
</body>
</html>
