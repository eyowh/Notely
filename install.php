<?php
/**
 * Installation script for Notely
 * Run this file once to initialize the database
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalasi Notely</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #6366f1; }
        .success { color: #10b981; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        a { color: #6366f1; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📝 Instalasi Notely</h1>";

try {
    echo "<div class='info'>Memulai instalasi database...</div>";
    
    // Initialize database
    initDatabase();
    
    echo "<div class='success'>✅ Database berhasil diinisialisasi!</div>";
    echo "<div class='success'>✅ Semua tabel berhasil dibuat!</div>";
    
    // Verify tables
    $conn = getDBConnection();
    $tables = ['users', 'notes', 'tasks', 'schedules', 'groups', 'group_members', 'group_notes', 'group_tasks', 'messages', 'notifications'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            $all_tables_exist = false;
            echo "<div class='error'>❌ Tabel '$table' tidak ditemukan!</div>";
        } else {
            echo "<div class='success'>✅ Tabel '$table' berhasil dibuat</div>";
        }
    }
    
    $conn->close();
    
    if ($all_tables_exist) {
        echo "<div class='success'><strong>Instalasi selesai!</strong></div>";
        echo "<p><a href='auth/register.php'>Klik di sini untuk mendaftar akun baru</a></p>";
        echo "<p><a href='auth/login.php'>Atau login jika sudah punya akun</a></p>";
    } else {
        echo "<div class='error'><strong>Ada masalah dengan instalasi. Silakan periksa error di atas.</strong></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Pastikan MySQL sudah berjalan di XAMPP.</div>";
}

echo "</div></body></html>";
?>

