<?php
/**
 * Debug script to check database status
 */
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Debug Database</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Debug Database Notely</h1>";

try {
    // Test connection
    echo "<div class='info'><strong>1. Testing Connection...</strong></div>";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        echo "<div class='error'>❌ Connection failed: " . $conn->connect_error . "</div>";
        exit;
    }
    echo "<div class='success'>✅ Connected to MySQL</div>";
    $conn->close();
    
    // Test database
    echo "<div class='info'><strong>2. Testing Database...</strong></div>";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<div class='error'>❌ Database '" . DB_NAME . "' tidak ditemukan</div>";
        echo "<div class='info'>Mencoba membuat database...</div>";
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Database berhasil dibuat</div>";
        } else {
            echo "<div class='error'>❌ Gagal membuat database: " . $conn->error . "</div>";
        }
        $conn->close();
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } else {
        echo "<div class='success'>✅ Database '" . DB_NAME . "' ditemukan</div>";
    }
    
    // Check tables
    echo "<div class='info'><strong>3. Checking Tables...</strong></div>";
    $tables = ['users', 'notes', 'tasks', 'schedules', 'groups', 'group_members', 'group_notes', 'group_tasks', 'messages', 'notifications'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div class='success'>✅ Tabel '$table' ada</div>";
            
            // Check structure
            $desc = $conn->query("DESCRIBE $table");
            echo "<details><summary>Struktur tabel $table</summary><pre>";
            while ($row = $desc->fetch_assoc()) {
                echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . "\n";
            }
            echo "</pre></details>";
        } else {
            echo "<div class='error'>❌ Tabel '$table' TIDAK ADA</div>";
        }
    }
    
    // Try to get connection using getDBConnection
    echo "<div class='info'><strong>4. Testing getDBConnection()...</strong></div>";
    $conn2 = getDBConnection();
    echo "<div class='success'>✅ getDBConnection() berhasil</div>";
    
    // Check users table again
    $result = $conn2->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Tabel 'users' sekarang ada setelah getDBConnection()</div>";
        
        // Try a simple query
        $test_query = $conn2->query("SELECT COUNT(*) as count FROM users");
        if ($test_query) {
            $row = $test_query->fetch_assoc();
            echo "<div class='success'>✅ Query test berhasil. Jumlah user: " . $row['count'] . "</div>";
        } else {
            echo "<div class='error'>❌ Query test gagal: " . $conn2->error . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Tabel 'users' masih tidak ada setelah getDBConnection()</div>";
    }
    
    $conn2->close();
    
    echo "<div class='info'><strong>5. Rekomendasi:</strong></div>";
    echo "<div class='info'>";
    echo "<p>1. Jika ada tabel yang tidak ada, coba akses: <a href='install.php'>install.php</a></p>";
    echo "<p>2. Atau coba akses: <a href='auth/register.php'>register.php</a> (akan membuat tabel otomatis)</p>";
    echo "<p>3. Jika masih error, hapus database dan buat ulang</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>

