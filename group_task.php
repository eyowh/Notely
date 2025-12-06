<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$group_id = intval($_GET['group_id'] ?? 0);
$action = $_GET['action'] ?? 'create';
$task_id = $_GET['id'] ?? null;

// Verify user is member of the group
if ($group_id > 0) {
    $check = $conn->query("SELECT gm.role FROM group_members gm WHERE gm.group_id = $group_id AND gm.user_id = $user_id");
    if ($check->num_rows === 0) {
        header('Location: groups.php');
        exit();
    }
    $user_role = $check->fetch_assoc()['role'];
} else {
    header('Location: groups.php');
    exit();
}

// Get group info
$group = $conn->query("SELECT * FROM groups WHERE id = $group_id")->fetch_assoc();

// Get group members for assignment
$group_members = [];
$result = $conn->query("SELECT u.id, u.username, u.full_name FROM group_members gm 
                       INNER JOIN users u ON gm.user_id = u.id 
                       WHERE gm.group_id = $group_id");
while ($row = $result->fetch_assoc()) {
    $group_members[] = $row;
}

$error = '';
$task = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM group_tasks WHERE id = ? AND group_id = ?");
        $stmt->bind_param("ii", $id, $group_id);
        $stmt->execute();
        $stmt->close();
        header('Location: groups.php?id=' . $group_id);
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        $status = sanitize($_POST['status'] ?? 'pending');
        $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Validasi deadline
        if (!empty($due_date)) {
            $due_timestamp = strtotime($due_date);
            $current_timestamp = time();
            if ($id == 0 && $due_timestamp < $current_timestamp) {
                $error = 'Deadline tidak boleh kurang dari waktu sekarang!';
            }
        }
        
        if (empty($error)) {
            // Check if approval_status column exists
            $check_col = $conn->query("SHOW COLUMNS FROM group_tasks LIKE 'approval_status'");
            $has_approval = ($check_col && $check_col->num_rows > 0);
            
            // Set approval status: admin langsung approved, member perlu approval
            $approval_status = ($has_approval && $user_role === 'admin') ? 'approved' : ($has_approval ? 'pending' : null);
            
            if ($id > 0) {
                // Update existing task
                $stmt = $conn->prepare("UPDATE group_tasks SET title = ?, description = ?, priority = ?, status = ?, assigned_to = ?, due_date = ? WHERE id = ? AND group_id = ?");
                $stmt->bind_param("sssssiii", $title, $description, $priority, $status, $assigned_to, $due_date, $id, $group_id);
            } else {
                // Insert new task
                if ($has_approval) {
                    $stmt = $conn->prepare("INSERT INTO group_tasks (group_id, user_id, title, description, priority, status, assigned_to, due_date, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssssss", $group_id, $user_id, $title, $description, $priority, $status, $assigned_to, $due_date, $approval_status);
                } else {
                    $stmt = $conn->prepare("INSERT INTO group_tasks (group_id, user_id, title, description, priority, status, assigned_to, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissssss", $group_id, $user_id, $title, $description, $priority, $status, $assigned_to, $due_date);
                }
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                if ($has_approval && $approval_status === 'pending') {
                    header('Location: groups.php?id=' . $group_id . '&msg=Menunggu persetujuan admin');
                } else {
                    header('Location: groups.php?id=' . $group_id);
                }
                exit();
            } else {
                $error = 'Terjadi kesalahan: ' . $stmt->error;
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
        
        if (!empty($error)) {
            $task = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'assigned_to' => $assigned_to,
                'due_date' => $due_date
            ];
        }
    }
}

// Get task for editing
if (empty($task) && $task_id) {
    $stmt = $conn->prepare("SELECT * FROM group_tasks WHERE id = ? AND group_id = ?");
    $stmt->bind_param("ii", $task_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Grup - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tasks.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>✅ Tugas Grup: <?php echo htmlspecialchars($group['name']); ?></h1>
                <a href="groups.php?id=<?php echo $group_id; ?>" class="btn btn-secondary">Kembali ke Grup</a>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-grid" id="groupTaskForm">
                <input type="hidden" name="id" value="<?php echo $task['id'] ?? 0; ?>">
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="title">Judul Tugas</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="priority">Prioritas</label>
                    <select id="priority" name="priority" required>
                        <option value="low" <?php echo ($task['priority'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Rendah</option>
                        <option value="medium" <?php echo ($task['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Sedang</option>
                        <option value="high" <?php echo ($task['priority'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>Tinggi</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" <?php echo ($task['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo ($task['status'] ?? 'pending') === 'in_progress' ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                        <option value="completed" <?php echo ($task['status'] ?? 'pending') === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="assigned_to">Ditugaskan ke (Opsional)</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Tidak ada</option>
                        <?php foreach ($group_members as $member): ?>
                            <option value="<?php echo $member['id']; ?>" <?php echo (isset($task['assigned_to']) && $task['assigned_to'] == $member['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['full_name'] ?: $member['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Deadline</label>
                    <?php 
                        $isEdit = isset($task['id']) && $task['id'] > 0;
                        $minAttr = $isEdit ? '' : 'min="' . date('Y-m-d\TH:i') . '"';
                        $dataAttr = $isEdit ? 'data-is-edit="true"' : '';
                    ?>
                    <input type="datetime-local" id="due_date" name="due_date" value="<?php echo isset($task['due_date']) && $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>" <?php echo $minAttr . ' ' . $dataAttr; ?>>
                    <small id="due_date_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Deadline tidak boleh kurang dari waktu sekarang!</small>
                </div>
                
                <div style="grid-column: 1 / -1; display: flex; gap: 1rem;">
                    <button type="submit" name="save" class="btn btn-primary">Simpan</button>
                    <a href="groups.php?id=<?php echo $group_id; ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tasks.js"></script>
</body>
</html>

