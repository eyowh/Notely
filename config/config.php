<?php
// Application Configuration
session_start();

// Base URL
define('BASE_URL', 'http://localhost/Notely/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once __DIR__ . '/database.php';

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari yang lalu';
    
    return formatDate($datetime);
}
?>

