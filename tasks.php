<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle actions
$action = $_GET['action'] ?? 'list';
$task_id = $_GET['id'] ?? null;
$filter = $_GET['filter'] ?? 'all';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: tasks.php');
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        $category = sanitize($_POST['category'] ?? '');
        $status = sanitize($_POST['status'] ?? 'pending');
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        
        // Validasi: deadline tidak boleh kurang dari waktu sekarang (hanya untuk tugas baru)
        if (empty($error) && $id == 0 && !empty($due_date)) {
            $due_timestamp = strtotime($due_date);
            $current_timestamp = time();
            
            if ($due_timestamp < $current_timestamp) {
                $error = 'Deadline tidak boleh kurang dari waktu sekarang!';
            }
        }
        
        if (empty($error)) {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, category = ?, status = ?, due_date = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssssssii", $title, $description, $priority, $category, $status, $due_date, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, priority, category, status, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $user_id, $title, $description, $priority, $category, $status, $due_date);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: tasks.php');
                exit();
            } else {
                $error = 'Terjadi kesalahan saat menyimpan tugas: ' . $stmt->error;
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
        
        // If there's an error, stay on the form page to show the error
        if (!empty($error)) {
            if ($id > 0) {
                $action = 'edit';
                $task_id = $id;
            } else {
                $action = 'create';
            }
            // Preserve form data
            $task = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'category' => $category,
                'status' => $status,
                'due_date' => $due_date
            ];
        }
    }
}

// Get task for editing (only if not already set from error handling)
if (!isset($task) || empty($task)) {
    $task = null;
    if ($task_id && $action === 'edit') {
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get all tasks
$tasks = [];
$query = "SELECT * FROM tasks WHERE user_id = $user_id";
if ($filter === 'pending') {
    $query .= " AND status = 'pending'";
} elseif ($filter === 'completed') {
    $query .= " AND status = 'completed'";
} elseif ($filter === 'high') {
    $query .= " AND priority = 'high'";
}
$query .= " ORDER BY 
    CASE priority 
        WHEN 'high' THEN 1 
        WHEN 'medium' THEN 2 
        WHEN 'low' THEN 3 
    END,
    due_date ASC,
    created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM tasks WHERE user_id = $user_id AND category IS NOT NULL AND category != ''");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tasks.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>✅ Daftar Tugas</h1>
                <a href="?action=create" class="btn btn-primary">+ Tugas Baru</a>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">Semua</a>
                <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Selesai</a>
                <a href="?filter=high" class="btn <?php echo $filter === 'high' ? 'btn-primary' : 'btn-secondary'; ?>">Prioritas Tinggi</a>
            </div>
            
            <?php if ($action === 'create' || $action === 'edit'): ?>
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="form-grid" id="taskForm">
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
                        <label for="category">Kategori</label>
                        <input type="text" id="category" name="category" list="categories" value="<?php echo htmlspecialchars($task['category'] ?? ''); ?>">
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
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
                        <label for="due_date">Deadline</label>
                        <?php 
                            $isEdit = isset($task['id']) && $task['id'] > 0;
                            $minAttr = $isEdit ? '' : 'min="' . date('Y-m-d\TH:i') . '"';
                            $dataAttr = $isEdit ? 'data-is-edit="true"' : '';
                        ?>
                        <input type="datetime-local" id="due_date" name="due_date" value="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>" <?php echo $minAttr . ' ' . $dataAttr; ?>>
                        <small id="due_date_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Deadline tidak boleh kurang dari waktu sekarang!</small>
                    </div>
                    
                    <div style="grid-column: 1 / -1; display: flex; gap: 1rem;">
                        <button type="submit" name="save" class="btn btn-primary">Simpan</button>
                        <a href="tasks.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <?php if (empty($tasks)): ?>
                    <p class="empty-state">Belum ada tugas. <a href="?action=create">Buat tugas pertama</a></p>
                <?php else: ?>
                    <div class="tasks-list">
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-item priority-<?php echo $t['priority']; ?>">
                                <div class="task-checkbox">
                                    <input type="checkbox" data-task-id="<?php echo $t['id']; ?>" <?php echo $t['status'] === 'completed' ? 'checked' : ''; ?>>
                                </div>
                                <div class="task-content" style="flex: 1;">
                                    <h3><?php echo htmlspecialchars($t['title']); ?></h3>
                                    <?php if ($t['description']): ?>
                                        <p style="color: var(--text-secondary); margin: 0.5rem 0;"><?php echo htmlspecialchars($t['description']); ?></p>
                                    <?php endif; ?>
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                        <?php if ($t['category']): ?>
                                            <span style="font-size: 0.875rem; color: var(--text-secondary);">📁 <?php echo htmlspecialchars($t['category']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($t['due_date']): ?>
                                            <span class="task-due">⏰ <?php echo formatDateTime($t['due_date']); ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                            Status: <?php 
                                                echo $t['status'] === 'pending' ? '⏳ Pending' : 
                                                    ($t['status'] === 'in_progress' ? '🔄 Sedang Dikerjakan' : '✅ Selesai'); 
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="?id=<?php echo $t['id']; ?>&action=edit" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <script src="<?php echo BASE_URL; ?>assets/js/tasks.js"></script>
    <?php endif; ?>
</body>
</html>

