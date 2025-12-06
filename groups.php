<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle actions
$action = $_GET['action'] ?? 'list';
$group_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO groups (name, description, creator_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $user_id);
            $stmt->execute();
            $new_group_id = $conn->insert_id;
            
            // Add creator as admin
            $stmt2 = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
            $stmt2->bind_param("ii", $new_group_id, $user_id);
            $stmt2->execute();
            
            $stmt->close();
            $stmt2->close();
            header('Location: groups.php?id=' . $new_group_id);
            exit();
        }
    } elseif (isset($_POST['join_group'])) {
        $group_code = sanitize($_POST['group_code'] ?? '');
        // For simplicity, using group ID as code
        $gid = intval($group_code);
        if ($gid > 0) {
            // Check if group exists and user is not already a member
            $check = $conn->query("SELECT id FROM groups WHERE id = $gid");
            if ($check->num_rows > 0) {
                $check2 = $conn->query("SELECT id FROM group_members WHERE group_id = $gid AND user_id = $user_id");
                if ($check2->num_rows === 0) {
                    $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
                    $stmt->bind_param("ii", $gid, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    header('Location: groups.php?id=' . $gid);
                    exit();
                }
            }
        }
    } elseif (isset($_POST['leave_group'])) {
        $gid = intval($_POST['group_id']);
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $gid, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: groups.php');
        exit();
    }
}

// Get user's groups
$groups = [];
$query = "SELECT g.*, gm.role FROM groups g 
          INNER JOIN group_members gm ON g.id = gm.group_id 
          WHERE gm.user_id = $user_id 
          ORDER BY g.created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
}

// Get current group details
$current_group = null;
$group_members = [];
$group_notes = [];
$group_tasks = [];

if ($group_id) {
    // Check if user is member
    $check = $conn->query("SELECT g.*, gm.role FROM groups g 
                          INNER JOIN group_members gm ON g.id = gm.group_id 
                          WHERE g.id = $group_id AND gm.user_id = $user_id");
    if ($check->num_rows > 0) {
        $current_group = $check->fetch_assoc();
        
        // Get members
        $result = $conn->query("SELECT u.id, u.username, u.full_name, gm.role 
                                FROM group_members gm 
                                INNER JOIN users u ON gm.user_id = u.id 
                                WHERE gm.group_id = $group_id");
        while ($row = $result->fetch_assoc()) {
            $group_members[] = $row;
        }
        
        // Check if approval_status column exists
        $has_approval = false;
        $check_col = $conn->query("SHOW COLUMNS FROM group_notes LIKE 'approval_status'");
        if ($check_col && $check_col->num_rows > 0) {
            $has_approval = true;
        }
        
        // Get group notes (only approved; admins see approved+pending but NOT rejected)
        if ($has_approval) {
            if ($current_group['role'] === 'admin') {
                $approval_filter = "AND gn.approval_status != 'rejected'";
            } else {
                $approval_filter = "AND gn.approval_status = 'approved'";
            }
        } else {
            $approval_filter = ""; // If column doesn't exist, show all
        }
        $result = $conn->query("SELECT gn.*, u.username 
                                FROM group_notes gn 
                                INNER JOIN users u ON gn.user_id = u.id 
                                WHERE gn.group_id = $group_id $approval_filter
                                ORDER BY gn.created_at DESC");
        while ($row = $result->fetch_assoc()) {
            $group_notes[] = $row;
        }
        
        // Get group tasks (only approved; admins see approved+pending but NOT rejected)
        if ($has_approval) {
            if ($current_group['role'] === 'admin') {
                $approval_filter = "AND gt.approval_status != 'rejected'";
            } else {
                $approval_filter = "AND gt.approval_status = 'approved'";
            }
        } else {
            $approval_filter = ""; // If column doesn't exist, show all
        }
        $result = $conn->query("SELECT gt.*, u.username, u2.username as assigned_to_name 
                                FROM group_tasks gt 
                                INNER JOIN users u ON gt.user_id = u.id 
                                LEFT JOIN users u2 ON gt.assigned_to = u2.id 
                                WHERE gt.group_id = $group_id $approval_filter
                                ORDER BY gt.created_at DESC");
        while ($row = $result->fetch_assoc()) {
            $group_tasks[] = $row;
        }
        
        // Get group schedules (only approved; admins see approved+pending but NOT rejected)
        if ($has_approval) {
            if ($current_group['role'] === 'admin') {
                $approval_filter = "AND gs.approval_status != 'rejected'";
            } else {
                $approval_filter = "AND gs.approval_status = 'approved'";
            }
        } else {
            $approval_filter = ""; // If column doesn't exist, show all
        }
        $result = $conn->query("SELECT gs.*, u.username 
                                FROM group_schedules gs 
                                INNER JOIN users u ON gs.user_id = u.id 
                                WHERE gs.group_id = $group_id $approval_filter
                                ORDER BY gs.start_time ASC");
        while ($row = $result->fetch_assoc()) {
            $group_schedules[] = $row;
        }
        
        // Get pending requests for admin (only if approval system exists)
        $pending_requests = [];
        if ($current_group['role'] === 'admin' && $has_approval) {
            // Pending tasks
            $result = $conn->query("SELECT 'task' as type, gt.id, gt.title, gt.created_at, u.username, u.full_name 
                                    FROM group_tasks gt 
                                    INNER JOIN users u ON gt.user_id = u.id 
                                    WHERE gt.group_id = $group_id AND gt.approval_status = 'pending'");
            while ($row = $result->fetch_assoc()) {
                $pending_requests[] = $row;
            }
            
            // Pending schedules
            $result = $conn->query("SELECT 'schedule' as type, gs.id, gs.title, gs.created_at, u.username, u.full_name 
                                    FROM group_schedules gs 
                                    INNER JOIN users u ON gs.user_id = u.id 
                                    WHERE gs.group_id = $group_id AND gs.approval_status = 'pending'");
            while ($row = $result->fetch_assoc()) {
                $pending_requests[] = $row;
            }
            
            // Pending notes
            $result = $conn->query("SELECT 'note' as type, gn.id, gn.title, gn.created_at, u.username, u.full_name 
                                    FROM group_notes gn 
                                    INNER JOIN users u ON gn.user_id = u.id 
                                    WHERE gn.group_id = $group_id AND gn.approval_status = 'pending'");
            while ($row = $result->fetch_assoc()) {
                $pending_requests[] = $row;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grup - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/groups.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <?php if ($group_id && $current_group): ?>
            <!-- Group Detail View -->
            <div class="card">
                <div class="card-header">
                    <div style="flex: 1;">
                        <h1>👥 <?php echo htmlspecialchars($current_group['name']); ?></h1>
                        <?php if ($current_group['description']): ?>
                            <p style="color: var(--text-secondary); margin-top: 0.5rem;"><?php echo htmlspecialchars($current_group['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                        <a href="groups.php" class="btn btn-secondary">← Kembali</a>
                        <?php if ($current_group['role'] === 'admin'): ?>
                            <span class="group-member-badge" style="font-size: 0.875rem; padding: 0.5rem 1rem;">Admin</span>
                            <?php if ($has_approval && count($pending_requests) > 0): ?>
                                <a href="group_approval.php?group_id=<?php echo $group_id; ?>" class="btn btn-warning">
                                    ⚠️ <?php echo count($pending_requests); ?> Pending
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$has_approval && $current_group['role'] === 'admin'): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem;">
                        ⚠️ <strong>Perhatian:</strong> Sistem persetujuan belum diaktifkan. 
                        Silakan jalankan <a href="migrate_approval.php" style="color: var(--primary-color); font-weight: bold;">migrate_approval.php</a> untuk mengaktifkan fitur persetujuan admin.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-info" style="margin-top: 1rem;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                
                <div class="group-detail-grid">
                    <!-- Members Section -->
                    <div class="group-section">
                        <div class="group-section-header">
                            <h2>👥 Anggota (<?php echo count($group_members); ?>)</h2>
                        </div>
                        <div class="group-section-content">
                            <?php foreach ($group_members as $member): ?>
                                <div class="group-member-item">
                                    <div class="group-member-info">
                                        <span class="group-member-name"><?php echo htmlspecialchars($member['full_name'] ?: $member['username']); ?></span>
                                        <span class="group-member-username">@<?php echo htmlspecialchars($member['username']); ?></span>
                                    </div>
                                    <?php if ($member['role'] === 'admin'): ?>
                                        <span class="group-member-badge">Admin</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Group Notes Section -->
                    <div class="group-section">
                        <div class="group-section-header">
                            <h2>📝 Catatan Grup</h2>
                            <a href="group_note.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary">+ Catatan</a>
                        </div>
                        <div class="group-section-content">
                            <?php if (empty($group_notes)): ?>
                                <div class="group-empty-state">
                                    <p>Belum ada catatan</p>
                                    <a href="group_note.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">Buat Catatan Pertama</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($group_notes as $note): ?>
                                    <div class="group-note-item">
                                        <div class="item-body">
                                            <h3><a href="group_note.php?group_id=<?php echo $group_id; ?>&id=<?php echo $note['id']; ?>&action=edit"><?php echo htmlspecialchars($note['title']); ?></a></h3>
                                            <div class="group-item-meta">
                                                <span>oleh <strong><?php echo htmlspecialchars($note['username']); ?></strong></span>
                                                <span>•</span>
                                                <span><?php echo getTimeAgo($note['created_at']); ?></span>
                                            </div>
                                            <?php if ($note['content']): ?>
                                                <p class="group-item-description"><?php echo htmlspecialchars(substr($note['content'], 0, 100)) . (strlen($note['content']) > 100 ? '...' : ''); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($current_group['role'] === 'admin' || $note['user_id'] === $user_id): ?>
                                            <div class="item-actions">
                                                <a class="btn btn-sm" href="group_note.php?group_id=<?php echo $group_id; ?>&id=<?php echo $note['id']; ?>&action=edit">Edit</a>
                                                <form method="POST" action="group_note.php?group_id=<?php echo $group_id; ?>" onsubmit="return confirm('Hapus catatan ini?');" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Group Tasks Section -->
                    <div class="group-section">
                        <div class="group-section-header">
                            <h2>✅ Tugas Grup</h2>
                            <a href="group_task.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary">+ Tugas</a>
                        </div>
                        <div class="group-section-content">
                            <?php if (empty($group_tasks)): ?>
                                <div class="group-empty-state">
                                    <p>Belum ada tugas</p>
                                    <a href="group_task.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">Buat Tugas Pertama</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($group_tasks as $task): ?>
                                    <div class="group-task-item">
                                        <div class="item-body">
                                            <h3><a href="group_task.php?group_id=<?php echo $group_id; ?>&id=<?php echo $task['id']; ?>&action=edit"><?php echo htmlspecialchars($task['title']); ?></a></h3>
                                            <div class="group-item-meta">
                                                <span>oleh <strong><?php echo htmlspecialchars($task['username']); ?></strong></span>
                                                <?php if ($task['assigned_to_name']): ?>
                                                    <span>•</span>
                                                    <span>Ditugaskan ke: <strong><?php echo htmlspecialchars($task['assigned_to_name']); ?></strong></span>
                                                <?php endif; ?>
                                                <span>•</span>
                                                <span><?php echo getTimeAgo($task['created_at']); ?></span>
                                            </div>
                                            <?php if ($task['description']): ?>
                                                <p class="group-item-description"><?php echo htmlspecialchars(substr($task['description'], 0, 100)) . (strlen($task['description']) > 100 ? '...' : ''); ?></p>
                                            <?php endif; ?>
                                            <div class="group-task-tags">
                                                <span class="group-task-priority priority-<?php echo $task['priority']; ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                                <?php if ($task['due_date']): ?>
                                                    <span class="group-task-due">
                                                        ⏰ <?php echo formatDateTime($task['due_date']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="group-task-status">
                                                    <?php echo ucfirst($task['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($current_group['role'] === 'admin' || $task['user_id'] === $user_id): ?>
                                            <div class="item-actions">
                                                <a class="btn btn-sm" href="group_task.php?group_id=<?php echo $group_id; ?>&id=<?php echo $task['id']; ?>&action=edit">Edit</a>
                                                <form method="POST" action="group_task.php?group_id=<?php echo $group_id; ?>" onsubmit="return confirm('Hapus tugas ini?');" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Group Schedules Section -->
                    <div class="group-section full-width">
                        <div class="group-section-header">
                            <h2>📅 Jadwal Grup</h2>
                            <a href="group_schedule.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary">+ Jadwal</a>
                        </div>
                        <div class="group-section-content">
                            <?php if (empty($group_schedules)): ?>
                                <div class="group-empty-state">
                                    <p>Belum ada jadwal</p>
                                    <a href="group_schedule.php?group_id=<?php echo $group_id; ?>&action=create" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">Buat Jadwal Pertama</a>
                                </div>
                            <?php else: ?>
                                <?php 
                                $current_date = '';
                                foreach ($group_schedules as $schedule): 
                                    $schedule_date = date('d M Y', strtotime($schedule['start_time']));
                                    if ($schedule_date !== $current_date):
                                        $current_date = $schedule_date;
                                ?>
                                    <h3 class="group-schedule-date-header"><?php echo $current_date; ?></h3>
                                <?php endif; ?>
                                    <div class="group-schedule-item">
                                        <div class="group-schedule-time">
                                            <span><?php echo date('H:i', strtotime($schedule['start_time'])); ?></span>
                                            <?php if ($schedule['end_time']): ?>
                                                <span>-</span>
                                                <span><?php echo date('H:i', strtotime($schedule['end_time'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="group-schedule-content">
                                            <h3><a href="group_schedule.php?group_id=<?php echo $group_id; ?>&id=<?php echo $schedule['id']; ?>&action=edit"><?php echo htmlspecialchars($schedule['title']); ?></a></h3>
                                            <div class="group-item-meta">
                                                <span>oleh <strong><?php echo htmlspecialchars($schedule['username']); ?></strong></span>
                                            </div>
                                            <?php if ($schedule['description']): ?>
                                                <p class="group-item-description"><?php echo htmlspecialchars($schedule['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($current_group['role'] === 'admin' || $schedule['user_id'] === $user_id): ?>
                                            <div class="item-actions">
                                                <a class="btn btn-sm" href="group_schedule.php?group_id=<?php echo $group_id; ?>&id=<?php echo $schedule['id']; ?>&action=edit">Edit</a>
                                                <form method="POST" action="group_schedule.php?group_id=<?php echo $group_id; ?>" onsubmit="return confirm('Hapus jadwal ini?');" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="group-leave-section">
                    <form method="POST" onsubmit="return confirm('Yakin ingin keluar dari grup ini?');">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <button type="submit" name="leave_group" class="btn btn-danger">Keluar dari Grup</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Groups List -->
            <div class="card">
                <div class="card-header">
                    <h1>👥 Grup Kolaboratif</h1>
                    <button onclick="document.getElementById('createGroupModal').classList.add('active')" class="btn btn-primary">+ Buat Grup</button>
                </div>
                
                <!-- Create Group Modal -->
                <div id="createGroupModal" class="create-group-modal" onclick="if(event.target === this) this.classList.remove('active')">
                    <div class="create-group-modal-content" onclick="event.stopPropagation()">
                        <h2>Buat Grup Baru</h2>
                        <form method="POST">
                            <div class="form-group">
                                <label for="name">Nama Grup</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Deskripsi</label>
                                <textarea id="description" name="description" rows="3"></textarea>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="submit" name="create_group" class="btn btn-primary">Buat</button>
                                <button type="button" onclick="document.getElementById('createGroupModal').classList.remove('active')" class="btn btn-secondary">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Join Group -->
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2>Bergabung dengan Grup</h2>
                    </div>
                    <form method="POST" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label for="group_code">Kode Grup (ID Grup)</label>
                            <input type="text" id="group_code" name="group_code" placeholder="Masukkan ID grup" required>
                        </div>
                        <button type="submit" name="join_group" class="btn btn-primary">Bergabung</button>
                    </form>
                </div>
                
                <?php if (empty($groups)): ?>
                    <div class="card" style="margin-top: 2rem; text-align: center; padding: 3rem;">
                        <p style="color: var(--text-secondary); font-size: 1.125rem; margin-bottom: 1rem;">Anda belum bergabung dengan grup manapun.</p>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Buat atau bergabung dengan grup untuk mulai berkolaborasi!</p>
                        <button onclick="document.getElementById('createGroupModal').classList.add('active')" class="btn btn-primary">Buat Grup Pertama</button>
                    </div>
                <?php else: ?>
                    <div class="groups-grid">
                        <?php foreach ($groups as $g): ?>
                            <div class="group-card" onclick="window.location.href='?id=<?php echo $g['id']; ?>'">
                                <h3><?php echo htmlspecialchars($g['name']); ?></h3>
                                <p><?php echo htmlspecialchars($g['description'] ?? 'Tidak ada deskripsi'); ?></p>
                                <div class="group-meta">
                                    <span>ID: <?php echo $g['id']; ?> • Dibuat <?php echo getTimeAgo($g['created_at']); ?></span>
                                    <?php if ($g['role'] === 'admin'): ?>
                                        <span class="group-member-badge">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

