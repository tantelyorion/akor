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
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit();
}

// Vérifier les données reçues
if (!isset($_POST['username']) || empty($_POST['username'])) {
    echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur manquant']);
    exit();
}

$username = clean_input($_POST['username']);
$follower_id = $_SESSION['user_id'];

// Récupérer l'utilisateur à suivre
$user = get_user_by_username($conn, $username);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit();
}

$following_id = $user['user_id'];

// Vérifier qu'on ne se suit pas soi-même
if ($follower_id == $following_id) {
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas vous suivre vous-même']);
    exit();
}

// Vérifier si le follow existe déjà
$is_following = is_following($conn, $follower_id, $following_id);

if ($is_following) {
    // Supprimer le follow (unfollow)
    $query = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
    $action = 'unfollow';
} else {
    // Ajouter le follow
    $query = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
    $action = 'follow';
}

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $follower_id, $following_id);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    // Envoyer une notification seulement pour un nouveau follow
    if ($action === 'follow') {
        $message = "a commencé à vous suivre";
        if (!create_notification($conn, $following_id, $follower_id, 'follow', $message)) {
            error_log("Échec de la création de la notification de follow");
        }
    }
    
    // Compter les followers
    $followers_count = count_followers($conn, $following_id);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'action' => $action,
        'followers_count' => $followers_count,
        'is_following' => ($action === 'follow')
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . mysqli_error($conn)
    ]);
}
?>