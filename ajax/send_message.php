<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to send messages']);
    exit();
}

// Check if receiver_id and message are provided
if (!isset($_POST['receiver_id']) || empty($_POST['receiver_id']) || !isset($_POST['message']) || empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Receiver ID and message are required']);
    exit();
}

$receiver_id = (int)$_POST['receiver_id'];
$sender_id = $_SESSION['user_id'];
$message_text = clean_input($_POST['message']);

// Check if receiver exists
$receiver = get_user_by_id($conn, $receiver_id);
if (!$receiver) {
    echo json_encode(['success' => false, 'message' => 'Receiver not found']);
    exit();
}

// Check if user is trying to message themselves
if ($sender_id == $receiver_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot message yourself']);
    exit();
}

// Add message
$query = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iis", $sender_id, $receiver_id, $message_text);

if (mysqli_stmt_execute($stmt)) {
    $message_id = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error sending message']);
}
?>