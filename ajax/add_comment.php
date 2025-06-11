<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Activer le reporting d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour commenter']);
    exit();
}

// Vérifier les données reçues
if (!isset($_POST['post_id']) || empty($_POST['post_id']) || !isset($_POST['comment']) || empty(trim($_POST['comment']))) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];
$comment = clean_input($_POST['comment']);

// Vérifier que le post existe
$post = get_post_details($conn, $post_id);
if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Publication introuvable']);
    exit();
}

// Ajouter le commentaire
$insert_query = "INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment);

if (mysqli_stmt_execute($stmt)) {
    $comment_id = mysqli_insert_id($conn);
    $user = get_user_by_id($conn, $user_id);
    
    // Envoyer une notification au propriétaire du post (sauf s'il commente son propre post)
    if ($post['user_id'] != $user_id) {
        $message = "a commenté votre publication: " . substr($comment, 0, 50);
        if (!create_notification($conn, $post['user_id'], $user_id, 'comment', $message, $post_id, $comment_id)) {
            error_log("Échec de la création de la notification de commentaire");
        }
    }
    
    // Gérer les mentions
    preg_match_all('/@(\w+)/', $comment, $matches);
    foreach ($matches[1] as $mentioned_username) {
        $mentioned_user = get_user_by_username($conn, $mentioned_username);
        if ($mentioned_user && $mentioned_user['user_id'] != $user_id) {
            $mention_message = "vous a mentionné dans un commentaire";
            create_notification($conn, $mentioned_user['user_id'], $user_id, 'mention', $mention_message, $post_id, $comment_id);
        }
    }
    
    // Compter les commentaires
    $comments_count = count_comments($conn, $post_id);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'comment' => [
            'comment_id' => $comment_id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s')
        ],
        'user' => [
            'username' => $user['username'],
            'profile_pic' => $user['profile_pic']
        ],
        'comments_count' => $comments_count
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du commentaire: ' . mysqli_error($conn)
    ]);
}
?>