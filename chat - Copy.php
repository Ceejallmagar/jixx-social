<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$userId = $_SESSION['user']['id'];
$friends = $db->getFriends($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chat - Jixx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
</head>
<body>
    <style>
        /* The styles for the chat page are now primarily handled by styles.css.
           This inline style block is kept for any page-specific overrides
           or adjustments needed in the future. The responsive behavior has been improved. */
        @media (max-width: 780px) {
            .chat-page-container {
                flex-direction: column;
                height: calc(100vh - 64px); /* Full height minus topbar */
                padding: 0;
                gap: 0;
            }
            .chat-page-container .friends-list {
                width: 100%;
                max-height: 200px; /* Limit height on mobile */
                border-radius: 0;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
            .chat-page-container .chat-area {
                height: auto;
                flex-grow: 1;
                border-radius: 0;
            }
            .chat-header {
                border-radius: 0;
            }
        }
    </style>
    <?php include 'header.php'; ?>
    <div class="chat-page-container">
        <div class="friends-list" id="friendsList">
            <div class="friends-list-header">Friends</div>
            <?php foreach ($friends as $friend): 
                $friendAvatar = $friend['avatar'] ? htmlspecialchars($friend['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($friend['first_name'] . ' ' . $friend['last_name']) . '&background=0D8ABC&color=fff&size=40';
            ?>
            <div class="friend-item" data-friend-id="<?php echo $friend['id']; ?>">
                <img src="<?php echo $friendAvatar; ?>" alt="Avatar" class="friend-avatar" />
                <span><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="chat-area card">
            <div class="chat-header" id="chatHeader"><h3>Select a friend to chat</h3></div>
            <div class="chat-messages" id="messages"></div>
            <form id="chatForm" class="chat-input-form" style="display:none; padding: 16px;">
                <button type="button" id="emojiBtn" class="emoji-btn">😊</button>
                <input type="text" id="messageInput" placeholder="Type a message..." required />
                <button type="submit">Send</button>
                <emoji-picker id="emojiPicker"></emoji-picker>
            </form>
        </div>
    </div>

    <script>
        const friendsList = document.getElementById('friendsList');
        const chatHeader = document.getElementById('chatHeader');
        const messagesDiv = document.getElementById('messages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const emojiBtn = document.getElementById('emojiBtn');
        const emojiPicker = document.getElementById('emojiPicker');

        let selectedFriendId = null;
        let pollInterval = null;

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function loadMessages() {
            if (!selectedFriendId) return;
            fetch('fetch_messages.php?user_id=' + selectedFriendId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messagesDiv.innerHTML = '';
                        data.messages.forEach(msg => {
                            const isSent = msg.sender_id === <?php echo $userId; ?>;
                            const div = document.createElement('div');
                            div.classList.add('message');
                            div.classList.add(isSent ? 'sent' : 'received');

                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'message-content';
                            contentDiv.textContent = msg.message;
                            div.appendChild(contentDiv);

                            if (isSent) {
                                const statusDiv = document.createElement('div');
                                statusDiv.className = 'message-status';
                                if (msg.is_seen) {
                                    statusDiv.textContent = 'Seen';
                                } else {
                                    statusDiv.textContent = 'Sent';
                                }
                                div.appendChild(statusDiv);
                            }
                            messagesDiv.appendChild(div);
                        });
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    }
                });
        }

        friendsList.querySelectorAll('.friend-item').forEach(item => {
            item.addEventListener('click', () => {
                if (pollInterval) clearInterval(pollInterval);
                friendsList.querySelectorAll('.friend-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                selectedFriendId = item.dataset.friendId;
                chatHeader.textContent = item.textContent.trim();
                chatForm.style.display = 'flex';
                loadMessages();
                pollInterval = setInterval(loadMessages, 3000);
            });
        });

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message || !selectedFriendId) return;
            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'receiver_id=' + encodeURIComponent(selectedFriendId) + '&message=' + encodeURIComponent(message)
            }).then(res => res.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages();
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            }).catch(() => alert('Failed to send message'));
        });

        emojiBtn.addEventListener('click', () => {
            emojiPicker.style.display = emojiPicker.style.display === 'block' ? 'none' : 'block';
        });

        emojiPicker.addEventListener('emoji-click', event => {
            messageInput.value += event.detail.emoji.unicode;
            emojiPicker.style.display = 'none';
        });
    </script>
</body>
</html>
