<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'jixx_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database tables
function initializeDatabase() {
    try {
        // First connect without database name to create it
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Verify we're connected to the right database
        $stmt = $pdo->query("SELECT DATABASE()");
        $currentDb = $stmt->fetchColumn();
        if ($currentDb !== DB_NAME) {
            throw new Exception("Failed to connect to database: " . DB_NAME);
        }
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                email_verified BOOLEAN DEFAULT FALSE,
                email_verification_token VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                avatar VARCHAR(255) DEFAULT NULL,
                bio TEXT DEFAULT NULL,
                hobbies TEXT DEFAULT NULL,
                social_links JSON DEFAULT NULL,
                dob DATE DEFAULT NULL,
                age INT(3) DEFAULT NULL,
                language VARCHAR(10) DEFAULT 'en',
                google_id VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Add missing columns to existing users table
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS hobbies TEXT DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS social_links JSON DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS age INT(3) DEFAULT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en'");
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL");
            // Set existing users as verified
            $pdo->exec("UPDATE users SET email_verified = TRUE WHERE email_verified IS NULL OR email_verified = FALSE");
        } catch (PDOException $e) {
            // Ignore if columns already exist or unsupported
        }

        // Create posts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                media_url VARCHAR(500) DEFAULT NULL,
                privacy ENUM('public', 'friends', 'private') DEFAULT 'public',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Create friendships table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS friendships (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user1_id INT NOT NULL,
                user2_id INT NOT NULL,
                status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_friendship (user1_id, user2_id)
            )
        ");
        
        // Create likes table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_like (user_id, post_id)
            )
        ");
        
        // Create auth_tokens table for "Remember Me"
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                selector VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX (selector)
            )
        ");

        
        return true;
    } catch (PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Initialize database on first load
initializeDatabase();
?>
