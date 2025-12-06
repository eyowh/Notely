<?php
/**
 * Reset database - HAPUS SEMUA DATA DAN BUAT ULANG
 * HATI-HATI: Script ini akan menghapus semua data!
 */
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Reset Database</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .btn:hover { background: #dc2626; }
    </style>
</head>
<body>
    <h1>⚠️ Reset Database Notely</h1>";

$action = $_GET['action'] ?? '';

if ($action === 'reset' && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        echo "<div class='warning'><strong>Memulai reset database...</strong></div>";
        
        // Connect without database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Drop database
        echo "<div class='info'>Menghapus database '" . DB_NAME . "'...</div>";
        $conn->query("DROP DATABASE IF EXISTS " . DB_NAME);
        echo "<div class='success'>✅ Database dihapus</div>";
        
        // Create database
        echo "<div class='info'>Membuat database baru...</div>";
        $sql = "CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->query($sql);
        echo "<div class='success'>✅ Database dibuat</div>";
        
        $conn->close();
        
        // Now initialize tables using getDBConnection which handles everything
        echo "<div class='info'>Membuat semua tabel...</div>";
        
        // Use getDBConnection which will create all tables automatically
        $conn = getDBConnection();
        echo "<div class='success'>✅ Koneksi database berhasil</div>";
        
        // Verify tables were created
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            $table_count = $result->num_rows;
            echo "<div class='success'><strong>✅ Reset selesai! $table_count tabel berhasil dibuat.</strong></div>";
            
            // List all tables
            echo "<div class='info'><strong>Tabel yang dibuat:</strong><ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul></div>";
            
            // Verify users table specifically
            $users_check = $conn->query("DESCRIBE users");
            if ($users_check && $users_check->num_rows > 0) {
                echo "<div class='success'>✅ Tabel 'users' berhasil dibuat dan dapat diakses</div>";
            } else {
                echo "<div class='error'>❌ Tabel 'users' tidak dapat diakses</div>";
            }
        } else {
            echo "<div class='error'>❌ Error memverifikasi tabel: " . $conn->error . "</div>";
        }
        
        echo "<div class='info'>";
        echo "<p><a href='auth/register.php'>Klik di sini untuk mendaftar</a></p>";
        echo "<p><a href='debug_db.php'>Atau cek status database</a></p>";
        echo "</div>";
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='warning'><strong>PERINGATAN!</strong></div>";
    echo "<div class='warning'>";
    echo "<p>Script ini akan:</p>";
    echo "<ul>";
    echo "<li>Menghapus database '" . DB_NAME . "' beserta semua datanya</li>";
    echo "<li>Membuat database baru</li>";
    echo "<li>Membuat semua tabel dari awal</li>";
    echo "</ul>";
    echo "<p><strong>Semua data akan hilang!</strong></p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p>Jika Anda yakin, klik tombol di bawah:</p>";
    echo "<a href='?action=reset&confirm=yes' class='btn'>RESET DATABASE</a>";
    echo "<p style='margin-top: 20px;'><a href='debug_db.php'>Kembali ke Debug</a></p>";
    echo "</div>";
}

echo "</body></html>";
?>

