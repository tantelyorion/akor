<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Activer le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour aimer une publication'
    ]);
    exit();
}

// Vérifier que le post_id est fourni et valide
if (!isset($_POST['post_id']) || !filter_var($_POST['post_id'], FILTER_VALIDATE_INT)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de publication invalide'
    ]);
    exit();
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Vérifier que la publication existe
$post = get_post_details($conn, $post_id);
if (!$post) {
    echo json_encode([
        'success' => false,
        'message' => 'Publication introuvable'
    ]);
    exit();
}

// Vérifier si l'utilisateur a déjà liké la publication
$already_liked = has_liked_post($conn, $user_id, $post_id);

if ($already_liked) {
    // Supprimer le like
    $query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
    $action = 'unlike';
} else {
    // Ajouter le like
    $query = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
    $action = 'like';
}

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    // Envoyer une notification seulement pour un nouveau like (et pas sur son propre post)
    if ($action === 'like' && $post['user_id'] != $user_id) {
        $message = "a aimé votre publication";
        if (!create_notification($conn, $post['user_id'], $user_id, 'like', $message, $post_id)) {
            error_log("Échec de la création de la notification de like");
        }
    }
    
    // Compter les likes
    $likes_count = count_likes($conn, $post_id);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count,
        'is_liked' => ($action === 'like')
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . mysqli_error($conn),
        'action' => $action
    ]);
}
?>