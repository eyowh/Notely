<?php
/**
 * Import Database Script
 * Script untuk import database Notely secara otomatis
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Import Database - Notely</title>
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
        .success { color: green; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #6366f1;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #4f46e5; }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📥 Import Database Notely</h1>";

$action = $_GET['action'] ?? '';

if ($action === 'import') {
    try {
        echo "<div class='info'><strong>Memulai import database...</strong></div>";
        
        // Read SQL file
        $sql_file = __DIR__ . '/database/notely_db.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("File SQL tidak ditemukan: database/notely_db.sql");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        if ($sql_content === false) {
            throw new Exception("Gagal membaca file SQL");
        }
        
        echo "<div class='success'>✅ File SQL berhasil dibaca</div>";
        
        // Connect to MySQL
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        echo "<div class='success'>✅ Terhubung ke MySQL</div>";
        
        // Split SQL into individual queries
        // Remove comments and empty lines
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
        
        // Split by semicolon
        $queries = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($query) {
                return !empty($query) && !preg_match('/^\s*$/s', $query);
            }
        );
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        // Execute each query
        foreach ($queries as $query) {
            if (empty(trim($query))) continue;
            
            // Skip USE statement if database doesn't exist yet
            if (preg_match('/^\s*USE\s+/i', $query)) {
                continue;
            }
            
            if ($conn->query($query)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = [
                    'query' => substr($query, 0, 100) . '...',
                    'error' => $conn->error
                ];
                
                // Don't stop on "table already exists" errors
                if (strpos($conn->error, 'already exists') === false) {
                    // Log other errors but continue
                }
            }
        }
        
        $conn->close();
        
        echo "<div class='success'><strong>✅ Import selesai!</strong></div>";
        echo "<div class='info'>";
        echo "<p>Query berhasil: <strong>$success_count</strong></p>";
        if ($error_count > 0) {
            echo "<p>Query dengan error: <strong>$error_count</strong></p>";
        }
        echo "</div>";
        
        if (!empty($errors)) {
            echo "<div class='warning'><strong>Beberapa error ditemukan (biasanya karena tabel sudah ada):</strong></div>";
            echo "<pre>";
            foreach (array_slice($errors, 0, 5) as $error) {
                echo "Error: " . htmlspecialchars($error['error']) . "\n";
            }
            if (count($errors) > 5) {
                echo "... dan " . (count($errors) - 5) . " error lainnya\n";
            }
            echo "</pre>";
        }
        
        // Verify tables
        echo "<div class='info'><strong>Memverifikasi tabel...</strong></div>";
        $conn = getDBConnection();
        
        $tables = ['users', 'notes', 'tasks', 'schedules', 'groups', 'group_members', 'group_notes', 'group_tasks', 'messages', 'notifications'];
        $found_tables = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $found_tables[] = $table;
                echo "<div class='success'>✅ Tabel '$table' ditemukan</div>";
            } else {
                echo "<div class='error'>❌ Tabel '$table' tidak ditemukan</div>";
            }
        }
        
        $conn->close();
        
        if (count($found_tables) === count($tables)) {
            echo "<div class='success'><strong>✅ Semua tabel berhasil dibuat!</strong></div>";
            echo "<div class='info'>";
            echo "<p><a href='auth/register.php' class='btn'>Daftar Akun Baru</a></p>";
            echo "<p><a href='debug_db.php' class='btn'>Cek Status Database</a></p>";
            echo "</div>";
        } else {
            echo "<div class='warning'>Beberapa tabel tidak ditemukan. Silakan coba import ulang atau gunakan phpMyAdmin.</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'>Pastikan MySQL sudah berjalan di XAMPP.</div>";
    }
} else {
    echo "<div class='info'>";
    echo "<p>Script ini akan mengimport database Notely dari file SQL.</p>";
    echo "<p><strong>File yang akan diimport:</strong> <code>database/notely_db.sql</code></p>";
    echo "</div>";
    
    // Check if SQL file exists
    $sql_file = __DIR__ . '/database/notely_db.sql';
    if (file_exists($sql_file)) {
        echo "<div class='success'>✅ File SQL ditemukan</div>";
        $file_size = filesize($sql_file);
        echo "<div class='info'>Ukuran file: " . number_format($file_size) . " bytes</div>";
    } else {
        echo "<div class='error'>❌ File SQL tidak ditemukan di: database/notely_db.sql</div>";
    }
    
    echo "<div class='warning'>";
    echo "<p><strong>Catatan:</strong></p>";
    echo "<ul>";
    echo "<li>Pastikan MySQL sudah berjalan di XAMPP</li>";
    echo "<li>Jika database sudah ada, tabel akan dibuat dengan IF NOT EXISTS (aman)</li>";
    echo "<li>Jika ada error 'table already exists', itu normal dan bisa diabaikan</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>Pilih metode import:</strong></p>";
    echo "<a href='?action=import' class='btn'>Import via Script PHP</a>";
    echo "<p style='margin-top: 20px;'>Atau gunakan metode manual:</p>";
    echo "<ol>";
    echo "<li>Buka phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Klik tab 'Import'</li>";
    echo "<li>Pilih file: <code>database/notely_db.sql</code></li>";
    echo "<li>Klik 'Go'</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</div></body></html>";
?>

