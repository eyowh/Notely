<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Check task reminders
$tasks = $conn->query("SELECT id, title, due_date FROM tasks 
                      WHERE user_id = $user_id 
                      AND status != 'completed' 
                      AND due_date IS NOT NULL 
                      AND reminder_sent = 0 
                      AND due_date <= DATE_ADD(NOW(), INTERVAL 1 HOUR)");

$hasReminders = false;

while ($task = $tasks->fetch_assoc()) {
    // Create notification
    $title = "⏰ Deadline Mendekat: " . $task['title'];
    $message = "Tugas ini akan segera melewati deadline!";
    $link = BASE_URL . "tasks.php";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'reminder', ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $link);
    $stmt->execute();
    $stmt->close();
    
    // Mark reminder as sent
    $conn->query("UPDATE tasks SET reminder_sent = 1 WHERE id = " . $task['id']);
    
    $hasReminders = true;
}

// Check schedule reminders
$schedules = $conn->query("SELECT id, title, start_time, reminder_before FROM schedules 
                          WHERE user_id = $user_id 
                          AND reminder_sent = 0 
                          AND start_time <= DATE_ADD(NOW(), INTERVAL reminder_before MINUTE)
                          AND start_time > NOW()");

while ($schedule = $schedules->fetch_assoc()) {
    // Create notification
    $title = "📅 Pengingat Jadwal: " . $schedule['title'];
    $message = "Jadwal Anda akan dimulai dalam " . $schedule['reminder_before'] . " menit!";
    $link = BASE_URL . "schedules.php";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'reminder', ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $link);
    $stmt->execute();
    $stmt->close();
    
    // Mark reminder as sent
    $conn->query("UPDATE schedules SET reminder_sent = 1 WHERE id = " . $schedule['id']);
    
    $hasReminders = true;
}

$conn->close();

echo json_encode(['hasReminders' => $hasReminders]);
?>

