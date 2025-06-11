<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Debug
error_log("Début du traitement du message vocal");

if (!is_logged_in()) {
    error_log("Utilisateur non connecté");
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (!isset($_FILES['voice_message']) || $_FILES['voice_message']['error'] !== UPLOAD_ERR_OK) {
    error_log("Erreur upload: " . ($_FILES['voice_message']['error'] ?? 'Fichier manquant'));
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
    exit();
}

// Vérification du dossier
$upload_dir = '../uploads/messages/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        error_log("Impossible de créer le dossier: $upload_dir");
        echo json_encode(['success' => false, 'message' => 'Erreur système']);
        exit();
    }
}

// Vérification du type MIME
$tmp_file = $_FILES['voice_message']['tmp_name'];
$mime_type = mime_content_type($tmp_file);

if (strpos($mime_type, 'audio/') !== 0) {
    error_log("Type MIME invalide: $mime_type");
    echo json_encode(['success' => false, 'message' => 'Type de fichier non supporté']);
    exit();
}

// Génération du nom de fichier
$ext = pathinfo($_FILES['voice_message']['name'], PATHINFO_EXTENSION) ?: 'wav';
$filename = uniqid() . '.' . $ext;
$target_path = $upload_dir . $filename;

// Déplacement du fichier
if (!move_uploaded_file($tmp_file, $target_path)) {
    error_log("Échec du déplacement vers: $target_path");
    echo json_encode(['success' => false, 'message' => 'Erreur système']);
    exit();
}

// Enregistrement en base
$query = "INSERT INTO messages 
          (sender_id, receiver_id, message_type, file_path, file_size, file_mime_type) 
          VALUES (?, ?, 'audio', ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);

$file_size = filesize($target_path);
$receiver_id = (int)$_POST['receiver_id'];

mysqli_stmt_bind_param($stmt, "iisss", 
    $_SESSION['user_id'], 
    $receiver_id,
    $filename,
    $file_size,
    $mime_type
);

if (mysqli_stmt_execute($stmt)) {
    $message_id = mysqli_insert_id($conn);
    error_log("Message vocal enregistré (ID: $message_id)");
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'file_path' => $filename,
        'file_mime_type' => $mime_type
    ]);
} else {
    error_log("Erreur SQL: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
?>