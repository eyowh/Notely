<?php
$unread_notifications = 0;
if (isLoggedIn()) {
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    $unread_notifications = $result->fetch_assoc()['count'];
    $conn->close();
}
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>index.php">📝 Notely</a>
        </div>
        
        <nav class="main-nav">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>notes.php" class="nav-link">Catatan</a>
            <a href="<?php echo BASE_URL; ?>tasks.php" class="nav-link">Tugas</a>
            <a href="<?php echo BASE_URL; ?>schedules.php" class="nav-link">Jadwal</a>
            <a href="<?php echo BASE_URL; ?>groups.php" class="nav-link">Grup</a>
            <a href="<?php echo BASE_URL; ?>chat.php" class="nav-link">Chat</a>
        </nav>
        
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>notifications.php" class="notification-icon">
                🔔
                <?php if ($unread_notifications > 0): ?>
                    <span class="badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a>
            
            <div class="user-menu">
                <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="dropdown">
                    <a href="<?php echo BASE_URL; ?>profile.php">Profil</a>
                    <a href="<?php echo BASE_URL; ?>auth/logout.php">Keluar</a>
                </div>
            </div>
        </div>
        
        <button class="mobile-menu-toggle">☰</button>
    </div>
</header>

