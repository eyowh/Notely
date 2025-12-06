<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get statistics
$stats = [
    'notes' => 0,
    'tasks' => 0,
    'tasks_completed' => 0,
    'schedules' => 0,
    'groups' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM notes WHERE user_id = $user_id");
$stats['notes'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id");
$stats['tasks'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'completed'");
$stats['tasks_completed'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE user_id = $user_id AND DATE(start_time) = CURDATE()");
$stats['schedules'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM group_members WHERE user_id = $user_id");
$stats['groups'] = $result->fetch_assoc()['count'];

// Get recent notes
$recent_notes = [];
$result = $conn->query("SELECT * FROM notes WHERE user_id = $user_id ORDER BY updated_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_notes[] = $row;
}

// Get upcoming tasks
$upcoming_tasks = [];
$result = $conn->query("SELECT * FROM tasks WHERE user_id = $user_id AND status != 'completed' AND due_date IS NOT NULL ORDER BY due_date ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $upcoming_tasks[] = $row;
}

// Get today's schedules
$today_schedules = [];
$result = $conn->query("SELECT * FROM schedules WHERE user_id = $user_id AND DATE(start_time) = CURDATE() ORDER BY start_time ASC");
while ($row = $result->fetch_assoc()) {
    $today_schedules[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>! 👋</h1>
            <p>Kelola produktivitas Anda dengan Notely</p>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Catatan</h3>
                <div class="stat-value"><?php echo $stats['notes']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Tugas</h3>
                <div class="stat-value"><?php echo $stats['tasks']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Tugas Selesai</h3>
                <div class="stat-value"><?php echo $stats['tasks_completed']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Jadwal Hari Ini</h3>
                <div class="stat-value"><?php echo $stats['schedules']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Grup</h3>
                <div class="stat-value"><?php echo $stats['groups']; ?></div>
            </div>
        </div>
        
        <div class="dashboard-sections">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📝 Catatan Terbaru</h2>
                    <a href="notes.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                </div>
                <?php if (empty($recent_notes)): ?>
                    <p class="empty-state">Belum ada catatan. <a href="notes.php?action=create">Buat catatan pertama</a></p>
                <?php else: ?>
                    <?php foreach ($recent_notes as $note): ?>
                        <div class="dashboard-item">
                            <h3><a href="notes.php?id=<?php echo $note['id']; ?>" style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($note['title']); ?></a></h3>
                            <p><?php echo htmlspecialchars(substr($note['content'], 0, 100)) . '...'; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>✅ Tugas Mendatang</h2>
                    <a href="tasks.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                </div>
                <?php if (empty($upcoming_tasks)): ?>
                    <p class="empty-state">Tidak ada tugas mendatang. <a href="tasks.php?action=create">Buat tugas baru</a></p>
                <?php else: ?>
                    <?php foreach ($upcoming_tasks as $task): ?>
                        <div class="dashboard-item">
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <?php if ($task['due_date']): ?>
                                <p>⏰ <?php echo formatDateTime($task['due_date']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>📅 Jadwal Hari Ini</h2>
                    <a href="schedules.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                </div>
                <?php if (empty($today_schedules)): ?>
                    <p class="empty-state">Tidak ada jadwal hari ini. <a href="schedules.php?action=create">Buat jadwal</a></p>
                <?php else: ?>
                    <?php foreach ($today_schedules as $schedule): ?>
                        <div class="dashboard-item">
                            <h3><?php echo htmlspecialchars($schedule['title']); ?></h3>
                            <p>
                                <strong><?php echo date('H:i', strtotime($schedule['start_time'])); ?></strong>
                                <?php if ($schedule['end_time']): ?>
                                    - <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                <?php endif; ?>
                                <?php if ($schedule['description']): ?>
                                    <br><?php echo htmlspecialchars($schedule['description']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

