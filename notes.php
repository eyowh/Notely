<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Handle actions
$action = $_GET['action'] ?? 'list';
$note_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: notes.php');
        exit();
    } elseif (isset($_POST['save'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $color = sanitize($_POST['color'] ?? '#ffffff');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, category = ?, color = ?, is_pinned = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssssiii", $title, $content, $category, $color, $is_pinned, $id, $user_id);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, category, color, is_pinned) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $user_id, $title, $content, $category, $color, $is_pinned);
        }
        
        $stmt->execute();
        $stmt->close();
        header('Location: notes.php');
        exit();
    }
}

// Get note for editing
$note = null;
if ($note_id && $action === 'edit') {
    $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $note = $result->fetch_assoc();
    $stmt->close();
}

// Get all notes
$notes = [];
$query = "SELECT * FROM notes WHERE user_id = $user_id ORDER BY is_pinned DESC, updated_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM notes WHERE user_id = $user_id AND category IS NOT NULL AND category != ''");
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
    <title>Catatan - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/notes.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📝 Catatan Saya</h1>
                <a href="?action=create" class="btn btn-primary">+ Catatan Baru</a>
            </div>
            
            <?php if ($action === 'create' || $action === 'edit'): ?>
                <form method="POST" action="" class="form-grid">
                    <input type="hidden" name="id" value="<?php echo $note['id'] ?? 0; ?>">
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="title">Judul</label>
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
                    
                    <div class="form-group">
                        <label for="color">Warna</label>
                        <input type="color" id="color" name="color" value="<?php echo htmlspecialchars($note['color'] ?? '#ffffff'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_pinned" <?php echo ($note['is_pinned'] ?? 0) ? 'checked' : ''; ?>>
                            Pin catatan
                        </label>
                    </div>
                    
                    <div style="grid-column: 1 / -1; display: flex; gap: 1rem;">
                        <button type="submit" name="save" class="btn btn-primary">Simpan</button>
                        <a href="notes.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            <?php else: ?>
                <?php if (empty($notes)): ?>
                    <p class="empty-state">Belum ada catatan. <a href="?action=create">Buat catatan pertama</a></p>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach ($notes as $n): ?>
                            <div class="note-card" style="background: <?php echo htmlspecialchars($n['color']); ?>;">
                                <?php if ($n['is_pinned']): ?>
                                    <span style="position: absolute; top: 1rem; right: 1rem; font-size: 1.25rem;">📌</span>
                                <?php endif; ?>
                                
                                <h3>
                                    <a href="?id=<?php echo $n['id']; ?>&action=edit">
                                        <?php echo htmlspecialchars($n['title']); ?>
                                    </a>
                                </h3>
                                
                                <p style="margin-bottom: 1rem; color: var(--text-secondary); flex: 1;">
                                    <?php echo htmlspecialchars(substr($n['content'], 0, 150)); ?>
                                    <?php echo strlen($n['content']) > 150 ? '...' : ''; ?>
                                </p>
                                
                                <?php if ($n['category']): ?>
                                    <span style="display: inline-block; background: rgba(0,0,0,0.08); padding: 0.375rem 0.875rem; border-radius: 1rem; font-size: 0.875rem; margin-bottom: 0.75rem; font-weight: 500;">
                                        <?php echo htmlspecialchars($n['category']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div style="display: flex; gap: 0.5rem; margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <a href="?id=<?php echo $n['id']; ?>&action=edit" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" style="display: inline; flex: 1;" onsubmit="return confirm('Yakin ingin menghapus catatan ini?');">
                                        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger" style="width: 100%;">Hapus</button>
                                    </form>
                                </div>
                                
                                <p style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.75rem; text-align: right;">
                                    <?php echo getTimeAgo($n['updated_at']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

