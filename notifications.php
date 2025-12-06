<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Mark all as read
if (isset($_GET['mark_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header('Location: notifications.php');
    exit();
}

// Get notifications
$notifications = [];
$query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Mark as read when viewing
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notifications.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>🔔 Notifikasi</h1>
                <?php if (!empty($notifications)): ?>
                    <a href="?mark_read=1" class="btn btn-secondary">Tandai Semua Dibaca</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($notifications)): ?>
                <p class="empty-state">Tidak ada notifikasi</p>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                            <h3><?php echo htmlspecialchars($notif['title']); ?></h3>
                            <?php if ($notif['message']): ?>
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <?php endif; ?>
                            <div class="notification-meta">
                                <span><?php echo getTimeAgo($notif['created_at']); ?></span>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-sm btn-primary">Lihat</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

