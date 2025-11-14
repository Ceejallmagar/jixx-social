-- SQL to create a messages table with encrypted message storage
CREATE TABLE IF NOT EXISTS encrypted_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message VARBINARY(1024) NOT NULL, -- encrypted message stored as binary
    iv VARBINARY(16) NOT NULL, -- initialization vector for encryption
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
