<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$group_id = intval($_GET['group_id'] ?? 0);
$action = $_GET['action'] ?? 'create';
$schedule_id = $_GET['id'] ?? null;

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

$error = '';
$schedule = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM group_schedules WHERE id = ? AND group_id = ?");
        $stmt->bind_param("ii", $id, $group_id);
        $stmt->execute();
        $stmt->close();
        header('Location: groups.php?id=' . $group_id);
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $reminder_before = intval($_POST['reminder_before'] ?? 15);
        
        // Validasi: start_time tidak boleh kurang dari waktu sekarang (hanya untuk jadwal baru)
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
            // Check if approval_status column exists
            $check_col = $conn->query("SHOW COLUMNS FROM group_schedules LIKE 'approval_status'");
            $has_approval = ($check_col && $check_col->num_rows > 0);
            
            // Set approval status: admin langsung approved, member perlu approval
            $approval_status = ($has_approval && $user_role === 'admin') ? 'approved' : ($has_approval ? 'pending' : null);
            
            if ($id > 0) {
                // Update existing schedule
                $stmt = $conn->prepare("UPDATE group_schedules SET title = ?, description = ?, start_time = ?, end_time = ?, reminder_before = ? WHERE id = ? AND group_id = ?");
                $stmt->bind_param("sssssii", $title, $description, $start_time, $end_time, $reminder_before, $id, $group_id);
            } else {
                // Insert new schedule
                if ($has_approval) {
                    $stmt = $conn->prepare("INSERT INTO group_schedules (group_id, user_id, title, description, start_time, end_time, reminder_before, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissssis", $group_id, $user_id, $title, $description, $start_time, $end_time, $reminder_before, $approval_status);
                } else {
                    $stmt = $conn->prepare("INSERT INTO group_schedules (group_id, user_id, title, description, start_time, end_time, reminder_before) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissssi", $group_id, $user_id, $title, $description, $start_time, $end_time, $reminder_before);
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
            $schedule = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'reminder_before' => $reminder_before
            ];
        }
    }
}

// Get schedule for editing
if (empty($schedule) && $schedule_id) {
    $stmt = $conn->prepare("SELECT * FROM group_schedules WHERE id = ? AND group_id = ?");
    $stmt->bind_param("ii", $schedule_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Grup - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/schedules.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📅 Jadwal Grup: <?php echo htmlspecialchars($group['name']); ?></h1>
                <a href="groups.php?id=<?php echo $group_id; ?>" class="btn btn-secondary">Kembali ke Grup</a>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-grid" id="groupScheduleForm">
                <input type="hidden" name="id" value="<?php echo $schedule['id'] ?? 0; ?>">
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="title">Judul Jadwal</label>
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
                    <input type="datetime-local" id="start_time" name="start_time" value="<?php echo isset($schedule['start_time']) && $schedule['start_time'] ? date('Y-m-d\TH:i', strtotime($schedule['start_time'])) : ''; ?>" <?php echo $minAttr . ' ' . $dataAttr; ?> required>
                    <small id="start_time_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Waktu mulai tidak boleh kurang dari waktu sekarang!</small>
                </div>
                
                <div class="form-group">
                    <label for="end_time">Waktu Selesai (Opsional)</label>
                    <input type="datetime-local" id="end_time" name="end_time" value="<?php echo isset($schedule['end_time']) && $schedule['end_time'] ? date('Y-m-d\TH:i', strtotime($schedule['end_time'])) : ''; ?>" min="">
                    <small id="end_time_error" style="color: var(--danger-color); display: none; margin-top: 0.25rem;">Waktu selesai tidak boleh kurang dari waktu mulai!</small>
                </div>
                
                <div class="form-group">
                    <label for="reminder_before">Pengingat (menit sebelum)</label>
                    <input type="number" id="reminder_before" name="reminder_before" value="<?php echo $schedule['reminder_before'] ?? 15; ?>" min="0" required>
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
    <script src="<?php echo BASE_URL; ?>assets/js/schedules.js"></script>
</body>
</html>

