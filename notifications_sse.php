<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

if (!is_logged_in()) {
    die("data: {\"error\":\"Not logged in\"}\n\n");
}

// Libérer la session pour d'autres requêtes
session_write_close();

$user_id = $_SESSION['user_id'];
$last_check = isset($_GET['last_check']) ? (int)$_GET['last_check'] : time();

// Configurer le temps d'exécution
set_time_limit(0);

while (true) {
    // Vérifier si la connexion client est toujours active
    if (connection_aborted()) {
        exit();
    }
    
    // Vérifier les nouvelles notifications
    $query = "SELECT COUNT(*) as count FROM notifications 
              WHERE user_id = ? AND created_at > FROM_UNIXTIME(?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $last_check);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        echo "data: " . json_encode(['new_notifications' => true, 'count' => $row['count']]) . "\n\n";
        ob_flush();
        flush();
        $last_check = time();
    }
    
    // Envoyer un heartbeat pour maintenir la connexion
    echo ": heartbeat\n\n";
    ob_flush();
    flush();
    
    // Attendre 3 secondes avant la prochaine vérification
    sleep(3);
}
?>