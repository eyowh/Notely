<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle actions
$action = $_GET['action'] ?? 'list';
$schedule_id = $_GET['id'] ?? null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: schedules.php');
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $category = sanitize($_POST['category'] ?? '');
        $reminder_before = intval($_POST['reminder_before'] ?? 15);
        
        $error = '';
        
        // Validasi: start_time tidak boleh kurang dari waktu sekarang (hanya untuk jadwal baru)
        // Untuk edit, biarkan user edit jadwal yang sudah lewat
        if (empty($error) && $id == 0 && !empty($start_time)) {
            $start_timestamp = strtotime($start_time);
            $current_timestamp = time();
            
            if ($start_timestamp < $current_timestamp) {
                $error = 'Waktu mulai tidak boleh kurang dari waktu sekarang!';
            }
        }
        
        // Validasi: end_time tidak boleh kurang dari start_time
        if (empty($error) && !empty($end_time) && !empty($start_time)) {
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            
            if ($end_timestamp < $start_timestamp) {
                $error = 'Waktu selesai tidak boleh kurang dari waktu mulai!';
            }
        }
        
        if (empty($error)) {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE schedules SET title = ?, description = ?, start_time = ?, end_time = ?, category = ?, reminder_before = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssssiii", $title, $description, $start_time, $end_time, $category, $reminder_before, $id, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO schedules (user_id, title, description, start_time, end_time, category, reminder_before) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $user_id, $title, $description, $start_time, $end_time, $category, $reminder_before);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: schedules.php');
                exit();
            } else {
                $error = 'Terjadi kesalahan saat menyimpan jadwal: ' . $stmt->error;
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
        // If there's an error, stay on the form page to show the error
        // Set action to create/edit so form is shown and preserve form data
        if (!empty($error)) {
            if ($id > 0) {
                $action = 'edit';
                $schedule_id = $id;
            } else {
                $action = 'create';
            }
            // Preserve form data in schedule array
            $schedule = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'category' => $category,
                'reminder_before' => $reminder_before
            ];
        }
    }
}

// Get schedule for editing
if (empty($schedule)) {
    $schedule = null;
}
if ($schedule_id && $action === 'edit' && empty($schedule)) {
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $schedule_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();
}

// Get all schedules
$schedules = [];
$query = "SELECT * FROM schedules WHERE user_id = $user_id ORDER BY start_time ASC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM schedules WHERE user_id = $user_id AND category IS NOT NULL AND category != ''");
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
    <title>Jadwal - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/schedules.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📅 Jadwal Kegiatan</h1>
                <a href="?action=create" class="btn btn-primary">+ Jadwal Baru</a>
            </div>
            
            <?php if ($action === 'create' || $action === 'edit'): ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="form-grid" id="scheduleForm">
                    <input type="hidden" name="id" value="<?php echo $schedule['id'] ?? 0; ?>">
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="title">Judul Kegiatan</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($schedule['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($schedule['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time">Waktu Mulai</label>
                        <?php 
                            $isEdit = isset($schedule['id']) && $schedule['id'] > 0;
                            $minAttr = $isEdit ? '' : 'min="' . date('Y-m-d\TH:i') . '"';
                            $dataAttr = $isEdit ? 'data-is-edit="true"' : '';
                        ?>
                        <input type="datetime-local" id="start_time" name="start_time" value="<?php echo $schedule['start_time'] ? date('Y-m-d\TH:i', strtotime($schedule['start_time'])) : ''; ?>" <?php echo $minAttr . ' ' . $dataAttr; ?> required>
                        <small id="start_time_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Waktu mulai tidak boleh kurang dari waktu sekarang!</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">Waktu Selesai (Opsional)</label>
                        <input type="datetime-local" id="end_time" name="end_time" value="<?php echo $schedule['end_time'] ? date('Y-m-d\TH:i', strtotime($schedule['end_time'])) : ''; ?>" min="">
                        <small id="end_time_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Waktu selesai tidak boleh kurang dari waktu mulai!</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <input type="text" id="category" name="category" list="categories" value="<?php echo htmlspecialchars($schedule['category'] ?? ''); ?>">
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="reminder_before">Pengingat (menit sebelum)</label>
                        <input type="number" id="reminder_before" name="reminder_before" value="<?php echo $schedule['reminder_before'] ?? 15; ?>" min="0" required>
                    </div>
                    
                    <div style="grid-column: 1 / -1; display: flex; gap: 1rem;">
                        <button type="submit" name="save" class="btn btn-primary">Simpan</button>
                        <a href="schedules.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <?php if (empty($schedules)): ?>
                    <p class="empty-state">Belum ada jadwal. <a href="?action=create">Buat jadwal pertama</a></p>
                <?php else: ?>
                    <div class="schedules-list">
                        <?php 
                        $current_date = '';
                        foreach ($schedules as $s): 
                            $schedule_date = date('d M Y', strtotime($s['start_time']));
                            if ($schedule_date !== $current_date):
                                $current_date = $schedule_date;
                        ?>
                            <h2 style="margin: 2rem 0 1rem 0; color: var(--primary-color);"><?php echo $current_date; ?></h2>
                        <?php endif; ?>
                            <div class="schedule-item">
                                <div class="schedule-time">
                                    <strong><?php echo date('H:i', strtotime($s['start_time'])); ?></strong>
                                    <?php if ($s['end_time']): ?>
                                        - <?php echo date('H:i', strtotime($s['end_time'])); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="schedule-content" style="flex: 1;">
                                    <h3><?php echo htmlspecialchars($s['title']); ?></h3>
                                    <?php if ($s['description']): ?>
                                        <p style="color: var(--text-secondary); margin: 0.5rem 0;"><?php echo htmlspecialchars($s['description']); ?></p>
                                    <?php endif; ?>
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                        <?php if ($s['category']): ?>
                                            <span style="font-size: 0.875rem; color: var(--text-secondary);">📁 <?php echo htmlspecialchars($s['category']); ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                            🔔 Pengingat: <?php echo $s['reminder_before']; ?> menit sebelum
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="?id=<?php echo $s['id']; ?>&action=edit" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
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
    <script src="<?php echo BASE_URL; ?>assets/js/schedules.js"></script>
</body>
</html>

