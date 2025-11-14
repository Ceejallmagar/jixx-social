<?php
// Manual database setup script
echo "<h2>Setting up Jixx Database</h2>";

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connected to MySQL<br>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS jixx_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'jixx_app' created/verified<br>";
    
    // Use the database
    $pdo->exec("USE jixx_app");
    echo "✅ Connected to jixx_app database<br>";
    
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Users table created/verified<br>";

    // Add missing columns if they don't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS hobbies TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS social_links JSON DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS age INT(3) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en'");
        $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) UNIQUE NULL");
        echo "✅ Users table columns updated<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Columns may already exist or unsupported: " . $e->getMessage() . "<br>";
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
    echo "✅ Posts table created/verified<br>";
    
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
    echo "✅ Friendships table created/verified<br>";
    
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
    echo "✅ Likes table created/verified<br>";

    // Create comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Comments table created/verified<br>";

    // Add missing columns if they don't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE comments ADD COLUMN IF NOT EXISTS parent_id INT NULL");
        $pdo->exec("ALTER TABLE comments ADD COLUMN IF NOT EXISTS media_url VARCHAR(500) NULL");
        echo "✅ Comments table columns updated<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Comments columns may already exist or unsupported: " . $e->getMessage() . "<br>";
    }

    // Create saved_posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS saved_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_save (user_id, post_id)
        )
    ");
    echo "✅ Saved posts table created/verified<br>";
    
    // Create auth_tokens table for "Remember Me" functionality
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
    echo "✅ Auth tokens table created/verified<br>";

    // Add sample data if no users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        // Insert sample users
        $pdo->exec("
            INSERT INTO users (email, password, first_name, last_name) VALUES
            ('alex@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'Alex', 'Johnson'),
            ('sara@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'Sara', 'Lee')
        ");
        echo "✅ Sample users added<br>";
        
        // Insert sample posts
        $pdo->exec("
            INSERT INTO posts (user_id, content, media_url) VALUES
            (1, 'First day on Jixx! Loving this sleek gradient vibe.', 'https://images.unsplash.com/photo-1520975916090-3105956dac38?q=80&w=1200&auto=format&fit=crop'),
            (2, 'Black and white can be so dramatic. What do you think of this theme?', NULL)
        ");
        echo "✅ Sample posts added<br>";
    } else {
        echo "ℹ️ Database already has " . $userCount . " users<br>";
    }
    
    echo "<br><h3>✅ Database setup complete!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='register.php'>Register a new account</a></li>";
    echo "<li><a href='login.php'>Login with existing account</a></li>";
    echo "<li><a href='debug_db.php'>Check database status</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<p>Make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>You can access phpMyAdmin at <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a></li>";
    echo "</ul>";
}
?>
