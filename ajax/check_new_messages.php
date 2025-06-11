<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to check messages']);
    exit();
}

// Check if receiver_id is provided
if (!isset($_GET['receiver_id']) || empty($_GET['receiver_id'])) {
    echo json_encode(['success' => false, 'message' => 'Receiver ID is required']);
    exit();
}

$receiver_id = (int)$_GET['receiver_id'];
$user_id = $_SESSION['user_id'];

// Get unread messages from this user
$query = "SELECT * FROM messages 
          WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
          ORDER BY created_at ASC";
          
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $receiver_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['formatted_time'] = format_date($row['created_at']);
    $messages[] = $row;
}

// Mark messages as read
if (!empty($messages)) {
    $update_query = "UPDATE messages 
                    SET is_read = 1 
                    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $receiver_id, $user_id);
    mysqli_stmt_execute($update_stmt);
}

echo json_encode([
    'success' => true,
    'new_messages' => $messages
]);
?>