<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$group_id = intval($_GET['group_id'] ?? 0);

// Verify user is admin of the group
if ($group_id > 0) {
    $check = $conn->query("SELECT g.*, gm.role FROM groups g 
                          INNER JOIN group_members gm ON g.id = gm.group_id 
                          WHERE g.id = $group_id AND gm.user_id = $user_id AND gm.role = 'admin'");
    if ($check->num_rows === 0) {
        header('Location: groups.php');
        exit();
    }
    $group = $check->fetch_assoc();
} else {
    header('Location: groups.php');
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
    $type = $_POST['type'] ?? ''; // 'task', 'schedule', or 'note'
    $id = intval($_POST['id'] ?? 0);
    
    if ($action && $type && $id > 0) {
        $approval_status = ($action === 'approve') ? 'approved' : 'rejected';
        $table = '';
        
        switch ($type) {
            case 'task':
                $table = 'group_tasks';
                break;
            case 'schedule':
                $table = 'group_schedules';
                break;
            case 'note':
                $table = 'group_notes';
                break;
        }
        
        if ($table) {
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE $table SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND group_id = ?");
                $stmt->bind_param("siii", $approval_status, $user_id, $id, $group_id);
            } else {
                $stmt = $conn->prepare("UPDATE $table SET approval_status = ? WHERE id = ? AND group_id = ?");
                $stmt->bind_param("sii", $approval_status, $id, $group_id);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: group_approval.php?group_id=' . $group_id . '&msg=' . ($action === 'approve' ? 'Disetujui' : 'Ditolak'));
                exit();
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}

// Get pending requests
$pending_tasks = [];
$pending_schedules = [];
$pending_notes = [];

// Pending tasks
$result = $conn->query("SELECT gt.*, u.username, u.full_name 
                        FROM group_tasks gt 
                        INNER JOIN users u ON gt.user_id = u.id 
                        WHERE gt.group_id = $group_id AND gt.approval_status = 'pending'
                        ORDER BY gt.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $pending_tasks[] = $row;
}

// Pending schedules
$result = $conn->query("SELECT gs.*, u.username, u.full_name 
                        FROM group_schedules gs 
                        INNER JOIN users u ON gs.user_id = u.id 
                        WHERE gs.group_id = $group_id AND gs.approval_status = 'pending'
                        ORDER BY gs.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $pending_schedules[] = $row;
}

// Pending notes
$result = $conn->query("SELECT gn.*, u.username, u.full_name 
                        FROM group_notes gn 
                        INNER JOIN users u ON gn.user_id = u.id 
                        WHERE gn.group_id = $group_id AND gn.approval_status = 'pending'
                        ORDER BY gn.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $pending_notes[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Grup - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/approval.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>✅ Persetujuan: <?php echo htmlspecialchars($group['name']); ?></h1>
                <a href="groups.php?id=<?php echo $group_id; ?>" class="btn btn-secondary">Kembali ke Grup</a>
            </div>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success" style="margin-top: 1rem;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>
            
            <?php if (empty($pending_tasks) && empty($pending_schedules) && empty($pending_notes)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <p style="color: var(--text-secondary); font-size: 1.1rem;">Tidak ada permintaan yang menunggu persetujuan</p>
                </div>
            <?php else: ?>
                <!-- Pending Tasks -->
                <?php if (!empty($pending_tasks)): ?>
                    <div style="margin-top: 2rem;">
                        <h2>📋 Tugas yang Menunggu Persetujuan (<?php echo count($pending_tasks); ?>)</h2>
                        <div style="margin-top: 1rem;">
                            <?php foreach ($pending_tasks as $task): ?>
                                <div style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 1rem; background: var(--bg-color);">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($task['title']); ?></h3>
                                            <p style="font-size: 0.875rem; color: var(--text-secondary);">
                                                oleh <strong><?php echo htmlspecialchars($task['full_name'] ?: $task['username']); ?></strong> • 
                                                <?php echo getTimeAgo($task['created_at']); ?>
                                            </p>
                                            <?php if ($task['description']): ?>
                                                <p style="margin-top: 0.75rem; color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                            <?php endif; ?>
                                            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <span class="btn btn-sm priority-<?php echo $task['priority']; ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                                <?php if ($task['due_date']): ?>
                                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                                        Deadline: <?php echo formatDateTime($task['due_date']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Setujui tugas ini?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="type" value="task">
                                                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-success">✓ Setujui</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak tugas ini?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="type" value="task">
                                                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-danger">✗ Tolak</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Pending Schedules -->
                <?php if (!empty($pending_schedules)): ?>
                    <div style="margin-top: 2rem;">
                        <h2>📅 Jadwal yang Menunggu Persetujuan (<?php echo count($pending_schedules); ?>)</h2>
                        <div style="margin-top: 1rem;">
                            <?php foreach ($pending_schedules as $schedule): ?>
                                <div style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 1rem; background: var(--bg-color);">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($schedule['title']); ?></h3>
                                            <p style="font-size: 0.875rem; color: var(--text-secondary);">
                                                oleh <strong><?php echo htmlspecialchars($schedule['full_name'] ?: $schedule['username']); ?></strong> • 
                                                <?php echo getTimeAgo($schedule['created_at']); ?>
                                            </p>
                                            <p style="margin-top: 0.75rem; color: var(--text-secondary);">
                                                <strong>Waktu:</strong> <?php echo formatDateTime($schedule['start_time']); ?>
                                                <?php if ($schedule['end_time']): ?>
                                                    - <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($schedule['description']): ?>
                                                <p style="margin-top: 0.75rem; color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($schedule['description'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Setujui jadwal ini?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="type" value="schedule">
                                                <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                                <button type="submit" class="btn btn-success">✓ Setujui</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak jadwal ini?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="type" value="schedule">
                                                <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                                <button type="submit" class="btn btn-danger">✗ Tolak</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Pending Notes -->
                <?php if (!empty($pending_notes)): ?>
                    <div style="margin-top: 2rem;">
                        <h2>📝 Catatan yang Menunggu Persetujuan (<?php echo count($pending_notes); ?>)</h2>
                        <div style="margin-top: 1rem;">
                            <?php foreach ($pending_notes as $note): ?>
                                <div style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 1rem; background: var(--bg-color);">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($note['title']); ?></h3>
                                            <p style="font-size: 0.875rem; color: var(--text-secondary);">
                                                oleh <strong><?php echo htmlspecialchars($note['full_name'] ?: $note['username']); ?></strong> • 
                                                <?php echo getTimeAgo($note['created_at']); ?>
                                            </p>
                                            <?php if ($note['content']): ?>
                                                <div style="margin-top: 0.75rem; padding: 1rem; background: white; border-radius: 0.5rem; color: var(--text-secondary);">
                                                    <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($note['category']): ?>
                                                <span style="display: inline-block; margin-top: 0.75rem; padding: 0.25rem 0.75rem; background: var(--primary-color); color: white; border-radius: 0.25rem; font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($note['category']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Setujui catatan ini?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="type" value="note">
                                                <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                                <button type="submit" class="btn btn-success">✓ Setujui</button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak catatan ini?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="type" value="note">
                                                <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                                <button type="submit" class="btn btn-danger">✗ Tolak</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

