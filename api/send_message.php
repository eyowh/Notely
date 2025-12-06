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

$chat_type = $_POST['chat_type'] ?? '';
$target_id = intval($_POST['target_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');

if (empty($message) || $target_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

if ($chat_type === 'direct') {
    // Direct message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $target_id, $message);
    
    // Create notification
    $target_user = $conn->query("SELECT username FROM users WHERE id = $target_id")->fetch_assoc();
    $notif_title = "Pesan baru dari " . ($_SESSION['full_name'] ?: $_SESSION['username']);
    $notif_message = substr($message, 0, 100);
    $notif_link = BASE_URL . "chat.php?type=direct&id=" . $user_id;
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'message', ?, ?, ?)");
    $notif_stmt->bind_param("isss", $target_id, $notif_title, $notif_message, $notif_link);
    $notif_stmt->execute();
    $notif_stmt->close();
} elseif ($chat_type === 'group') {
    // Group message
    // Check if user is member
    $check = $conn->query("SELECT id FROM group_members WHERE group_id = $target_id AND user_id = $user_id");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, group_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $target_id, $message);
        
        // Create notifications for all group members except sender
        $members = $conn->query("SELECT user_id FROM group_members WHERE group_id = $target_id AND user_id != $user_id");
        $group_name = $conn->query("SELECT name FROM groups WHERE id = $target_id")->fetch_assoc()['name'];
        
        while ($member = $members->fetch_assoc()) {
            $notif_title = "Pesan baru di grup " . $group_name;
            $notif_message = ($_SESSION['full_name'] ?: $_SESSION['username']) . ": " . substr($message, 0, 80);
            $notif_link = BASE_URL . "chat.php?type=group&id=" . $target_id;
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'message', ?, ?, ?)");
            $notif_stmt->bind_param("isss", $member['user_id'], $notif_title, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Not a member of this group']);
        $conn->close();
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid chat type']);
    $conn->close();
    exit();
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?>

