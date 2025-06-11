<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$unread_count = count_unread_notifications($conn, $user_id);

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count
]);
?>