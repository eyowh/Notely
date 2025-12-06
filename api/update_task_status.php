<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$conn = getDBConnection();
$user_id = getCurrentUserId();

$task_id = intval($_POST['task_id'] ?? 0);
$status = sanitize($_POST['status'] ?? 'pending');

if ($task_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit();
}

$stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $status, $task_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update task']);
}

$stmt->close();
$conn->close();
?>

