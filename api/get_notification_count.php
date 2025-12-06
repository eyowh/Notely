<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = getCurrentUserId();

$result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
$count = $result->fetch_assoc()['count'];

$conn->close();

echo json_encode(['count' => intval($count)]);
?>

