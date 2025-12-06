<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get user info
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $full_name, $email, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    
    header('Location: profile.php?success=1');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Password berhasil diubah!";
        } else {
            $error = "Password baru tidak cocok atau terlalu pendek!";
        }
    } else {
        $error = "Password saat ini salah!";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>👤 Profil Saya</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Profil berhasil diperbarui!</div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-grid" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small style="color: var(--text-secondary);">Username tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
            
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <h2>Ubah Password</h2>
                <form method="POST" class="form-grid" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <button type="submit" name="change_password" class="btn btn-primary">Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

