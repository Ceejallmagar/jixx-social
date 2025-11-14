<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user = $db->getUserById($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Change Password • Jixx</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        body {
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .change-password-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .change-password-card h1 {
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: 700;
            font-size: 24px;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.06);
            color: var(--text);
            font-size: 16px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px 0;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #1e90ff, #5cc3ff);
            color: #012;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(30,144,255,0.12);
            transition: background 0.3s ease;
        }
        button:hover {
            background: linear-gradient(135deg, #0b6fb3, #3ca9ff);
        }
        .message {
            margin-top: 12px;
            text-align: center;
            font-weight: 600;
        }
        .message.error {
            color: #ff4b4b;
        }
        .message.success {
            color: #4bb543;
        }
    </style>
</head>
<body>
    <div class="change-password-card">
        <h1>Change Password</h1>
        <form id="changePasswordForm">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" name="currentPassword" required minlength="6" />
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" name="newPassword" required minlength="6" />
            <label for="confirmPassword">Confirm New Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6" />
            <button type="submit">Change Password</button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const form = document.getElementById('changePasswordForm');
        const messageDiv = document.getElementById('message');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            messageDiv.textContent = '';
            messageDiv.className = 'message';

            const currentPassword = form.currentPassword.value.trim();
            const newPassword = form.newPassword.value.trim();
            const confirmPassword = form.confirmPassword.value.trim();

            if (newPassword !== confirmPassword) {
                messageDiv.textContent = 'New passwords do not match.';
                messageDiv.classList.add('error');
                return;
            }

            if (newPassword.length < 6) {
                messageDiv.textContent = 'New password must be at least 6 characters.';
                messageDiv.classList.add('error');
                return;
            }

            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                const data = await response.json();
                if (data.success) {
                    messageDiv.textContent = 'Password changed successfully.';
                    messageDiv.classList.add('success');
                    form.reset();
                } else {
                    messageDiv.textContent = data.message || 'Failed to change password.';
                    messageDiv.classList.add('error');
                }
            } catch (error) {
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.classList.add('error');
            }
        });
    </script>
</body>
</html>
