<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();
$group_id = intval($_GET['group_id'] ?? 0);
$action = $_GET['action'] ?? 'create';
$note_id = $_GET['id'] ?? null;

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
$note = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM group_notes WHERE id = ? AND group_id = ?");
        $stmt->bind_param("ii", $id, $group_id);
        $stmt->execute();
        $stmt->close();
        header('Location: groups.php?id=' . $group_id);
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        
        if (empty($error)) {
            // Check if approval_status column exists
            $check_col = $conn->query("SHOW COLUMNS FROM group_notes LIKE 'approval_status'");
            $has_approval = ($check_col && $check_col->num_rows > 0);
            
            // Set approval status: admin langsung approved, member perlu approval
            $approval_status = ($has_approval && $user_role === 'admin') ? 'approved' : ($has_approval ? 'pending' : null);
            
            if ($id > 0) {
                // Update existing note
                $stmt = $conn->prepare("UPDATE group_notes SET title = ?, content = ?, category = ? WHERE id = ? AND group_id = ?");
                $stmt->bind_param("sssii", $title, $content, $category, $id, $group_id);
            } else {
                // Insert new note
                if ($has_approval) {
                    $stmt = $conn->prepare("INSERT INTO group_notes (group_id, user_id, title, content, category, approval_status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissss", $group_id, $user_id, $title, $content, $category, $approval_status);
                } else {
                    $stmt = $conn->prepare("INSERT INTO group_notes (group_id, user_id, title, content, category) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisss", $group_id, $user_id, $title, $content, $category);
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
            $note = [
                'id' => $id,
                'title' => $title,
                'content' => $content,
                'category' => $category
            ];
        }
    }
}

// Get note for editing
if (empty($note) && $note_id) {
    $stmt = $conn->prepare("SELECT * FROM group_notes WHERE id = ? AND group_id = ?");
    $stmt->bind_param("ii", $note_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $note = $result->fetch_assoc();
    $stmt->close();
}

// Get categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM group_notes WHERE group_id = $group_id AND category IS NOT NULL AND category != ''");
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
    <title>Catatan Grup - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notes.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📝 Catatan Grup: <?php echo htmlspecialchars($group['name']); ?></h1>
                <a href="groups.php?id=<?php echo $group_id; ?>" class="btn btn-secondary">Kembali ke Grup</a>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-grid">
                <input type="hidden" name="id" value="<?php echo $note['id'] ?? 0; ?>">
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="title">Judul Catatan</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($note['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="content">Isi Catatan</label>
                    <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($note['content'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Kategori</label>
                    <input type="text" id="category" name="category" list="categories" value="<?php echo htmlspecialchars($note['category'] ?? ''); ?>">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
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
</body>
</html>

