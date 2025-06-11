<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID required']);
    exit();
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

$success = toggle_bookmark($conn, $user_id, $post_id);

if ($success) {
    echo json_encode([
        'success' => true,
        'is_bookmarked' => has_bookmarked_post($conn, $user_id, $post_id)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating bookmark']);
}
?>