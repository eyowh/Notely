<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'notely_db');

// Create database connection and ensure tables exist
function getDBConnection() {
    // First connect without database to create it if needed
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->query($sql);
    $conn->close();
    
    // Now connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // ALWAYS ensure tables exist - use simple approach
    // Just try to create tables (IF NOT EXISTS makes it safe)
    ensureTablesExist($conn);
    
    return $conn;
}

// Ensure all tables exist
function ensureTablesExist($conn) {
    // Simple check: try to describe users table
    // If it fails, table doesn't exist
    $test = @$conn->query("DESCRIBE users");
    
    if ($test === false) {
        // Table doesn't exist, create all tables
        createAllTables($conn);
    } else {
        // Table exists, we're good
        $test->free();
    }
}

// Create all tables
function createAllTables($conn) {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Create users table first (no dependencies)
    $users_sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        avatar VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create users table
    $result = $conn->query($users_sql);
    if (!$result) {
        // Re-enable foreign key checks before dying
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $error_msg = "Error creating users table: " . $conn->error . " (Error code: " . $conn->errno . ")";
        error_log($error_msg);
        die($error_msg);
    }
    
    // Verify users table was created
    $verify = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$verify || $verify->num_rows === 0) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        die("CRITICAL: Users table creation failed verification. Please check MySQL permissions and logs.");
    }
    if ($verify) $verify->free();
    
    // Create other tables
    $tables = [
        // Notes table
        "CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            category VARCHAR(50),
            color VARCHAR(7) DEFAULT '#ffffff',
            is_pinned BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tasks table
        "CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            category VARCHAR(50),
            status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
            due_date DATETIME,
            reminder_sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Schedules table
        "CREATE TABLE IF NOT EXISTS schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            category VARCHAR(50),
            reminder_before INT DEFAULT 15,
            reminder_sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Groups table
        "CREATE TABLE IF NOT EXISTS groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            creator_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Group members table
        "CREATE TABLE IF NOT EXISTS group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('admin', 'member') DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_member (group_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Group notes table
        "CREATE TABLE IF NOT EXISTS group_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            category VARCHAR(50),
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by INT,
            approved_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Group tasks table
        "CREATE TABLE IF NOT EXISTS group_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
            assigned_to INT,
            due_date DATETIME,
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by INT,
            approved_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Group schedules table
        "CREATE TABLE IF NOT EXISTS group_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            reminder_before INT DEFAULT 15,
            reminder_sent BOOLEAN DEFAULT FALSE,
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by INT,
            approved_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Messages table
        "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT,
            group_id INT,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            link VARCHAR(255),
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $table_name => $sql) {
        // Extract table name from SQL for better error messages
        if (is_numeric($table_name)) {
            // If array key is numeric, try to extract table name from SQL
            if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $sql, $matches)) {
                $table_name = $matches[1];
            } else {
                $table_name = 'unknown';
            }
        }
        
        if (!$conn->query($sql)) {
            $error_msg = "Error creating table '$table_name': " . $conn->error . " (Error code: " . $conn->errno . ")";
            error_log($error_msg);
            // Don't die, just log - other tables might still be created
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verify users table was created
    $verify = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$verify || $verify->num_rows === 0) {
        die("CRITICAL: Users table was not created successfully. Please check MySQL error logs.");
    }
    if ($verify) $verify->free();
}

// Initialize database and tables (for manual initialization)
function initDatabase() {
    $conn = getDBConnection();
    // Tables are already created by getDBConnection()
    // This function is kept for compatibility
}
?>
