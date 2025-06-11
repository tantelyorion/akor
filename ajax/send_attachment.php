<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_POST['receiver_id']) || empty($_POST['receiver_id']) || !isset($_FILES['attachment'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$receiver_id = (int)$_POST['receiver_id'];
$sender_id = $_SESSION['user_id'];

// Vérifier le dossier
$target_dir = '../uploads/messages/';
if (!file_exists($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Impossible de créer le dossier']);
        exit();
    }
}

// Vérifier l'upload
if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erreur upload: ' . $_FILES['attachment']['error']]);
    exit();
}

// Obtenir l'extension du fichier
$filename = $_FILES['attachment']['name'];
$file_ext = pathinfo($filename, PATHINFO_EXTENSION);
$new_filename = uniqid() . '.' . $file_ext;
$target_file = $target_dir . $new_filename;

// Déterminer le type de message
$mime_type = mime_content_type($_FILES['attachment']['tmp_name']);
$message_type = 'file'; // Par défaut

if (strpos($mime_type, 'image/') === 0) {
    $message_type = 'image';
} elseif (strpos($mime_type, 'video/') === 0) {
    $message_type = 'video';
} elseif (strpos($mime_type, 'audio/') === 0) {
    $message_type = 'audio';
}

// Déplacer le fichier
if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
    // Enregistrer en base de données
    $query = "INSERT INTO messages 
              (sender_id, receiver_id, message_type, file_path, file_name, file_size, file_mime_type) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    $file_size = $_FILES['attachment']['size'];
    
    mysqli_stmt_bind_param(
        $stmt, 
        "iisssis", 
        $sender_id, 
        $receiver_id,
        $message_type,
        $new_filename,
        $filename, // Nom original
        $file_size,
        $mime_type
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $message_id = mysqli_insert_id($conn);
        
        echo json_encode([
            'success' => true,
            'message_id' => $message_id,
            'file_path' => $new_filename,
            'file_name' => $filename,
            'message_type' => $message_type
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
}
?>