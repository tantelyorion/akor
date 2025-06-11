<?php
// Common functions for the Instagram clone

// Clean and validate input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect if already logged in
function redirect_if_logged_in() {
    if (is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

// Get user data by ID
function get_user_by_id($conn, $user_id) {
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Get user data by username
function get_user_by_username($conn, $username) {
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Format date to a more readable format
function format_date($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("F j, Y", $timestamp);
    }
}

// Count user's posts
function count_user_posts($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM posts WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Count user's followers
function count_followers($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM follows WHERE following_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Count users being followed
function count_following($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM follows WHERE follower_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Check if user follows another user
function is_following($conn, $follower_id, $following_id) {
    $query = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $follower_id, $following_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Check if user has liked a post
function has_liked_post($conn, $user_id, $post_id) {
    $query = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Count likes on a post
function count_likes($conn, $post_id) {
    $query = "SELECT COUNT(*) as count FROM likes WHERE post_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Count comments on a post
function count_comments($conn, $post_id) {
    $query = "SELECT COUNT(*) as count FROM comments WHERE post_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Upload image and return path
function upload_image($file, $target_dir) {
    // Créer le dossier s'il n'existe pas
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Types autorisés
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov'
    ];
    
    if (!array_key_exists($mime_type, $allowed_types)) {
        return ["success" => false, "message" => "Type de fichier non autorisé"];
    }
    
    $extension = $allowed_types[$mime_type];
    
    // Vérifier la taille du fichier (10MB max)
    if ($file["size"] > 10000000) {
        return ["success" => false, "message" => "Le fichier est trop volumineux (max 10MB)"];
    }
    
    // Générer un nom de fichier unique
    $filename = uniqid() . "." . $extension;
    $target_file = $target_dir . $filename;
    
    // Déplacer le fichier uploadé
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "file_path" => $filename, "is_video" => strpos($mime_type, 'video/') === 0];
    } else {
        return ["success" => false, "message" => "Erreur lors de l'upload du fichier"];
    }
}

// Extract hashtags from caption
function extract_hashtags($caption) {
    preg_match_all('/#(\w+)/', $caption, $matches);
    return $matches[1];
}

// Save hashtags and associate with post
function save_hashtags($conn, $post_id, $caption) {
    $hashtags = extract_hashtags($caption);
    
    foreach ($hashtags as $tag) {
        // Check if hashtag exists
        $query = "SELECT hashtag_id FROM hashtags WHERE hashtag_name = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $tag);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $hashtag_id = $row['hashtag_id'];
        } else {
            // Create new hashtag
            $query = "INSERT INTO hashtags (hashtag_name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $tag);
            mysqli_stmt_execute($stmt);
            $hashtag_id = mysqli_insert_id($conn);
        }
        
        // Associate hashtag with post
        $query = "INSERT INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $hashtag_id);
        mysqli_stmt_execute($stmt);
    }
}

// Get feed posts (posts from users being followed and own posts)
function get_feed_posts($conn, $user_id, $limit = 10, $offset = 0) {
    $query = "SELECT p.*, u.username, u.profile_pic 
              FROM posts p 
              JOIN users u ON p.user_id = u.user_id
              WHERE p.user_id = ? 
              OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiii", $user_id, $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Get profile posts
function get_profile_posts($conn, $user_id, $limit = 12, $offset = 0) {
    $query = "SELECT * FROM posts 
              WHERE user_id = ? 
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Get post comments
function get_post_comments($conn, $post_id) {
    $query = "SELECT c.*, u.username, u.profile_pic 
              FROM comments c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.post_id = ?
              ORDER BY c.created_at ASC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $comments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    
    return $comments;
}

// Get single post with details
function get_post_details($conn, $post_id) {
    $query = "SELECT p.*, u.username, u.profile_pic 
              FROM posts p 
              JOIN users u ON p.user_id = u.user_id
              WHERE p.post_id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Search users
function search_users($conn, $search_term) {
    $search_term = "%$search_term%";
    $query = "SELECT * FROM users WHERE username LIKE ? OR full_name LIKE ? LIMIT 20";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    return $users;
}

// Search hashtags
function search_hashtags($conn, $search_term) {
    $search_term = "%$search_term%";
    $query = "SELECT * FROM hashtags WHERE hashtag_name LIKE ? LIMIT 20";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $hashtags = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $hashtags[] = $row;
    }
    
    return $hashtags;
}

// Get posts by hashtag
function get_posts_by_hashtag($conn, $hashtag_id, $limit = 12, $offset = 0) {
    $query = "SELECT p.*, u.username, u.profile_pic 
              FROM posts p 
              JOIN users u ON p.user_id = u.user_id
              JOIN post_hashtags ph ON p.post_id = ph.post_id
              WHERE ph.hashtag_id = ?
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $hashtag_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Get conversations list
function get_conversations($conn, $user_id) {
    $query = "SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.username, 
                u.profile_pic,
                (SELECT message_text FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id) 
                 OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id) 
                 OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = other_user_id AND receiver_id = ? AND is_read = 0) as unread_count
              FROM messages m
              JOIN users u ON 
                CASE 
                    WHEN m.sender_id = ? THEN u.user_id = m.receiver_id
                    ELSE u.user_id = m.sender_id
                END
              WHERE m.sender_id = ? OR m.receiver_id = ?
              ORDER BY last_message_time DESC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiiiiiiii", 
        $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $conversations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Vérification que les champs requis existent
        if (!isset($row['username'])) {
            $user = get_user_by_id($conn, $row['other_user_id']);
            if ($user) {
                $row['username'] = $user['username'];
                $row['profile_pic'] = $user['profile_pic'];
            } else {
                continue; // Skip les utilisateurs inexistants
            }
        }
        $conversations[] = $row;
    }
    
    return $conversations;
}

// Get conversation messages
function get_conversation_messages($conn, $user_id, $other_user_id) {
    $query = "SELECT m.*, u.username, u.profile_pic
              FROM messages m 
              JOIN users u ON m.sender_id = u.user_id
              WHERE (m.sender_id = ? AND m.receiver_id = ?)
              OR (m.sender_id = ? AND m.receiver_id = ?)
              ORDER BY m.created_at ASC";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiii", $user_id, $other_user_id, $other_user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    
    // Mark messages as read
    $update_query = "UPDATE messages 
                    SET is_read = 1 
                    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $other_user_id, $user_id);
    mysqli_stmt_execute($update_stmt);
    
    return $messages;
}

// Get unread message count
function get_unread_message_count($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Check if user has bookmarked a post
function has_bookmarked_post($conn, $user_id, $post_id) {
    $query = "SELECT * FROM bookmarks WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Toggle bookmark for a post
function toggle_bookmark($conn, $user_id, $post_id) {
    if (has_bookmarked_post($conn, $user_id, $post_id)) {
        // Remove bookmark
        $query = "DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?";
    } else {
        // Add bookmark
        $query = "INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    return mysqli_stmt_execute($stmt);
}

// Get user's bookmarked posts
function get_bookmarked_posts($conn, $user_id, $limit = 12, $offset = 0) {
    $query = "SELECT p.*, u.username, u.profile_pic 
              FROM posts p 
              JOIN users u ON p.user_id = u.user_id
              JOIN bookmarks b ON p.post_id = b.post_id
              WHERE b.user_id = ?
              ORDER BY b.created_at DESC
              LIMIT ? OFFSET ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $posts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Report a post
function report_post($conn, $post_id, $user_id, $reason, $description = null) {
    $query = "INSERT INTO reports (post_id, user_id, reason, description) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiss", $post_id, $user_id, $reason, $description);
    return mysqli_stmt_execute($stmt);
}

// Check if user has reported a post
function has_reported_post($conn, $user_id, $post_id) {
    $query = "SELECT * FROM reports WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Get report reasons for select options
function get_report_reasons() {
    return [
        'spam' => 'Spam or misleading',
        'inappropriate' => 'Inappropriate content',
        'violence' => 'Violence or harmful behavior',
        'hate_speech' => 'Hate speech or symbols',
        'other' => 'Other reason'
    ];
}


// Récupérer les paramètres utilisateur
function get_user_settings($conn, $user_id) {
    $query = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        // Créer des paramètres par défaut si inexistants
        $defaults = [
            'user_id' => $user_id,
            'private_account' => false,
            'notification_comments' => true,
            'notification_likes' => true,
            'notification_follows' => true,
            'notification_mentions' => true,
            'language' => 'fr',
            'theme' => 'light'
        ];
        create_user_settings($conn, $defaults);
        return $defaults;
    }
}

// Créer les paramètres utilisateur
function create_user_settings($conn, $settings) {
    $query = "INSERT INTO user_settings (
                user_id, private_account, notification_comments, 
                notification_likes, notification_follows, 
                notification_mentions, language, theme
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param(
        $stmt, 
        "iiiiiiss", 
        $settings['user_id'],
        $settings['private_account'],
        $settings['notification_comments'],
        $settings['notification_likes'],
        $settings['notification_follows'],
        $settings['notification_mentions'],
        $settings['language'],
        $settings['theme']
    );
    return mysqli_stmt_execute($stmt);
}

// Mettre à jour les paramètres utilisateur
function update_user_settings($conn, $user_id, $settings) {
    $query = "UPDATE user_settings SET 
                private_account = ?,
                notification_comments = ?,
                notification_likes = ?,
                notification_follows = ?,
                notification_mentions = ?,
                language = ?,
                theme = ?
              WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param(
        $stmt, 
        "iiiiissi", 
        $settings['private_account'],
        $settings['notification_comments'],
        $settings['notification_likes'],
        $settings['notification_follows'],
        $settings['notification_mentions'],
        $settings['language'],
        $settings['theme'],
        $user_id
    );
    return mysqli_stmt_execute($stmt);
}

// Mettre à jour les informations du profil utilisateur
function update_user_profile($conn, $user_id, $data) {
    $query = "UPDATE users SET 
                username = ?,
                full_name = ?,
                bio = ?,
                updated_at = CURRENT_TIMESTAMP
              WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param(
        $stmt, 
        "sssi", 
        $data['username'],
        $data['full_name'],
        $data['bio'],
        $user_id
    );
    return mysqli_stmt_execute($stmt);
}

// Changer la photo de profil
function update_profile_picture($conn, $user_id, $new_filename) {
    // Récupérer l'ancienne photo
    $query = "SELECT profile_pic FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
    if ($row['profile_pic'] !== 'default_profile.jpg') {
        $old_file = 'uploads/profile_pics/' . $row['profile_pic'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    
    // Mettre à jour la base de données
    $query = "UPDATE users SET profile_pic = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_filename, $user_id);
    return mysqli_stmt_execute($stmt);
}

// Changer le mot de passe
function update_password($conn, $user_id, $current_password, $new_password) {
    // Vérifier l'ancien mot de passe
    $query = "SELECT password FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if (!password_verify($current_password, $row['password'])) {
        return false;
    }
    
    // Mettre à jour avec le nouveau mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $query = "UPDATE users SET password = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
    return mysqli_stmt_execute($stmt);
}

// Fonctions pour les notifications

// Créer une notification
// Modifier la fonction create_notification pour mieux gérer les types
function create_notification($conn, $user_id, $actor_id, $type, $message, $post_id = null, $comment_id = null) {
    // Vérifier que l'utilisateur ne se notifie pas lui-même
    if ($user_id == $actor_id) {
        return false;
    }

    // Vérifier les paramètres de notification
    $settings = get_user_settings($conn, $user_id);
    $notification_enabled = true;
    
    switch($type) {
        case 'follow': $notification_enabled = $settings['notification_follows']; break;
        case 'like': $notification_enabled = $settings['notification_likes']; break;
        case 'comment': $notification_enabled = $settings['notification_comments']; break;
        case 'mention': $notification_enabled = $settings['notification_mentions']; break;
    }
    
    if (!$notification_enabled) {
        return false;
    }
    
    $query = "INSERT INTO notifications (user_id, actor_id, post_id, comment_id, type, message) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiiiss", $user_id, $actor_id, $post_id, $comment_id, $type, $message);
    return mysqli_stmt_execute($stmt);
}

// Ajouter une fonction pour récupérer les dernières notifications
function get_recent_notifications($conn, $user_id, $limit = 5) {
    $query = "SELECT n.*, u.username, u.profile_pic 
              FROM notifications n
              JOIN users u ON n.actor_id = u.user_id
              WHERE n.user_id = ?
              ORDER BY n.created_at DESC
              LIMIT ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}



// Compter les notifications non lues
function count_unread_notifications($conn, $user_id) {
    $query = "SELECT COUNT(*) as count FROM notifications 
              WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Marquer les notifications comme lues
function mark_notifications_as_read($conn, $user_id, $notification_ids = []) {
    if (empty($notification_ids)) {
        $query = "UPDATE notifications SET is_read = 1 
                  WHERE user_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        $ids = implode(',', array_map('intval', $notification_ids));
        $query = "UPDATE notifications SET is_read = 1 
                  WHERE user_id = ? AND notification_id IN ($ids)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    
    return mysqli_stmt_execute($stmt);
}

// Fonction pour extraire les mentions (@username) d'un texte
function extract_mentions($text) {
    preg_match_all('/@(\w+)/', $text, $matches);
    return $matches[1];
}

// Envoyer des notifications pour les mentions
function send_mention_notifications($conn, $text, $actor_id, $post_id = null, $comment_id = null) {
    $mentions = extract_mentions($text);
    
    foreach ($mentions as $username) {
        $user = get_user_by_username($conn, $username);
        if ($user && $user['user_id'] != $actor_id) {
            $message = "Vous avez été mentionné dans un commentaire";
            create_notification($conn, $user['user_id'], $actor_id, 'mention', $message, $post_id, $comment_id);
        }
    }
}

// Envoyer une notification administrative
function send_admin_notification($conn, $user_id, $message) {
    // L'ID 1 est supposé être l'administrateur, ou vous pouvez avoir une table admin
    $admin_id = 1;
    
    return create_notification($conn, $user_id, $admin_id, 'admin_message', $message);
}


/**
 * Get user notifications with pagination
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of notifications per page
 * @param int $offset Offset for pagination
 * @return array Array of notifications
 */
function get_user_notifications($conn, $user_id, $limit = 20, $offset = 0) {
    $query = "SELECT n.*, u.username, u.profile_pic, p.image_url as post_image
              FROM notifications n
              JOIN users u ON n.actor_id = u.user_id
              LEFT JOIN posts p ON n.post_id = p.post_id
              WHERE n.user_id = ?
              ORDER BY n.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Count user notifications (read or unread)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param bool $read_only If true, counts only read notifications
 * @return int Number of notifications
 */
function count_user_notifications($conn, $user_id, $read_only = false) {
    $query = "SELECT COUNT(*) as count FROM notifications 
              WHERE user_id = ?";
    
    if ($read_only) {
        $query .= " AND is_read = 1";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'];
}


// Fonction pour envoyer un message avec pièce jointe
function send_message_with_attachment($conn, $sender_id, $receiver_id, $file, $message_type = 'text', $text = '') {
    // Vérifier que le destinataire existe
    $receiver = get_user_by_id($conn, $receiver_id);
    if (!$receiver) {
        return ['success' => false, 'message' => 'Destinataire non trouvé'];
    }
    
    // Déterminer le répertoire de destination
    $target_dir = 'uploads/messages/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Uploader le fichier
    $upload_result = upload_image($file, $target_dir);
    if (!$upload_result['success']) {
        return $upload_result;
    }
    
    // Enregistrer le message dans la base de données
    $query = "INSERT INTO messages 
              (sender_id, receiver_id, message_text, message_type, file_path, file_size, file_mime_type) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    $file_size = $file['size'];
    $mime_type = mime_content_type($target_dir . $upload_result['file_path']);
    
    mysqli_stmt_bind_param(
        $stmt, 
        "iisssis", 
        $sender_id, 
        $receiver_id, 
        $text,
        $message_type,
        $upload_result['file_path'],
        $file_size,
        $mime_type
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $message_id = mysqli_insert_id($conn);
        
        // Créer une notification pour le destinataire
        $sender = get_user_by_id($conn, $sender_id);
        $message = "Vous avez reçu un nouveau message";
        create_notification($conn, $receiver_id, $sender_id, 'message', $message);
        
        return [
            'success' => true,
            'message_id' => $message_id,
            'file_path' => $upload_result['file_path']
        ];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message'];
    }
}

// Fonction pour enregistrer un message vocal temporaire
function save_temp_voice_message($conn, $user_id, $file_path) {
    $query = "INSERT INTO voice_messages_temp (user_id, file_path) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $file_path);
    
    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'temp_id' => mysqli_insert_id($conn)];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement temporaire'];
    }
}

// Fonction pour convertir un message vocal temporaire en message permanent
function convert_temp_to_permanent_message($conn, $temp_id, $sender_id, $receiver_id) {
    // Récupérer le message temporaire
    $query = "SELECT * FROM voice_messages_temp WHERE temp_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $temp_id, $sender_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $temp_message = mysqli_fetch_assoc($result);
    
    if (!$temp_message) {
        return ['success' => false, 'message' => 'Message temporaire non trouvé'];
    }
    
    // Déplacer le fichier vers le dossier permanent
    $temp_path = 'uploads/temp/' . $temp_message['file_path'];
    $perm_path = 'uploads/messages/' . $temp_message['file_path'];
    
    if (!rename($temp_path, $perm_path)) {
        return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
    }
    
    // Enregistrer le message permanent
    $query = "INSERT INTO messages 
              (sender_id, receiver_id, message_type, file_path) 
              VALUES (?, ?, 'audio', ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iis", $sender_id, $receiver_id, $temp_message['file_path']);
    
    if (mysqli_stmt_execute($stmt)) {
        $message_id = mysqli_insert_id($conn);
        
        // Supprimer l'entrée temporaire
        $query = "DELETE FROM voice_messages_temp WHERE temp_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $temp_id);
        mysqli_stmt_execute($stmt);
        
        return ['success' => true, 'message_id' => $message_id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message'];
    }
}


function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>