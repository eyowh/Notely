<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = getCurrentUserId();

$chat_type = $_GET['type'] ?? '';
$target_id = intval($_GET['id'] ?? 0);

$messages = [];

if ($chat_type === 'direct' && $target_id) {
    $result = $conn->query("SELECT m.*, u.username, u.full_name 
                            FROM messages m 
                            INNER JOIN users u ON m.sender_id = u.id 
                            WHERE ((m.sender_id = $user_id AND m.receiver_id = $target_id) 
                                   OR (m.sender_id = $target_id AND m.receiver_id = $user_id))
                            AND m.group_id IS NULL 
                            ORDER BY m.created_at ASC");
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
} elseif ($chat_type === 'group' && $target_id) {
    $result = $conn->query("SELECT m.*, u.username, u.full_name 
                            FROM messages m 
                            INNER JOIN users u ON m.sender_id = u.id 
                            WHERE m.group_id = $target_id 
                            ORDER BY m.created_at ASC");
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

$conn->close();

echo json_encode(['messages' => $messages]);
?>

