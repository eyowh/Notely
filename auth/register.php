<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            // getDBConnection() will automatically create database and tables if they don't exist
            $conn = getDBConnection();
            
            // Verify users table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'users'");
            if ($table_check->num_rows === 0) {
                throw new Exception("Tabel users tidak ditemukan. Silakan jalankan install.php terlebih dahulu.");
            }
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error . " (Error code: " . $conn->errno . ")");
            }
            
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username atau email sudah terdaftar!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->close();
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
                
                if ($stmt->execute()) {
                    $success = 'Registrasi berhasil! Silakan login.';
                    $stmt->close();
                    $conn->close();
                    header('Location: login.php?success=1');
                    exit();
                } else {
                    $error = 'Terjadi kesalahan saat registrasi: ' . $stmt->error;
                }
            }
            
            if ($stmt) $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
            if (isset($conn) && $conn) {
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>📝 Notely</h1>
                <p>Buat akun baru</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>
            
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>

