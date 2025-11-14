<?php
// Debug database connection
echo "<h2>Database Debug Information</h2>";

// Test basic MySQL connection
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ MySQL connection successful<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'jixx_app'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'jixx_app' exists<br>";
        
        // Connect to the database
        $pdo->exec("USE jixx_app");
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tables found: " . implode(", ", $tables) . "<br>";
        
        // Check users table
        if (in_array('users', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch()['count'];
            echo "👥 Users in database: " . $count . "<br>";
            
            if ($count > 0) {
                $stmt = $pdo->query("SELECT id, email, first_name, last_name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                $users = $stmt->fetchAll();
                echo "<h3>Recent Users:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Created</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . $user['id'] . "</td>";
                    echo "<td>" . $user['email'] . "</td>";
                    echo "<td>" . $user['first_name'] . " " . $user['last_name'] . "</td>";
                    echo "<td>" . $user['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } else {
        echo "❌ Database 'jixx_app' does not exist<br>";
        echo "Creating database...<br>";
        
        $pdo->exec("CREATE DATABASE jixx_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database 'jixx_app' created<br>";
        
        $pdo->exec("USE jixx_app");
        echo "✅ Connected to jixx_app database<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='register.php'>Go to Registration</a> | <a href='login.php'>Go to Login</a>";
?>
