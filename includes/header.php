<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$is_logged_in = is_logged_in();

// Get current user data if logged in
$current_user = null;
if ($is_logged_in) {
    $current_user = get_user_by_id($conn, $_SESSION['user_id']);
    
    // Get unread message count
    $unread_message_count = get_unread_message_count($conn, $_SESSION['user_id']);
    
    // Get unread notifications count
    $unread_notifications_count = count_unread_notifications($conn, $_SESSION['user_id']);
}

// Déterminer le thème actuel
$current_theme = 'light';
if ($is_logged_in) {
    $settings = get_user_settings($conn, $_SESSION['user_id']);
    $current_theme = $settings['theme'] ?? 'light';
} elseif (isset($_SESSION['theme'])) {
    $current_theme = $_SESSION['theme'];
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKOR</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="assets/icon.png" type="image/x-icon">
</head>
<style>
    #logoh {
        width: auto;
        height: 30px;
    }

    /* Badges de notification */
.notification-badge, .message-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff2e4d;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-link {
    position: relative;
    padding: 8px;
}

/* Animation pour les nouvelles notifications */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.new-notification {
    animation: pulse 0.5s ease 2;
}

/* Style pour le thème sombre */
.dark-theme .notification-badge,
.dark-theme .message-badge {
    background: #ff4d6d;
}
</style>
<body>
    <header class="header <?php echo $current_theme; ?>-theme">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/logo.png" id="logoh">
                    </a>
                </div>
                
                <?php if ($is_logged_in): ?>
                <div class="search-container">
                    <form action="search.php" method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Search" class="search-input">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="nav-links">
                <?php if ($is_logged_in): ?>
                    <a href="index.php" class="nav-link"><i class="fas fa-home"></i></a>
                    <a href="explore.php" class="nav-link"><i class="fas fa-compass"></i></a>
                    <a href="create.php" class="nav-link"><i class="fas fa-plus-square"></i></a>
                    
                    <!-- Ajoutez ce lien pour les notifications -->
                    <a href="notifications.php" class="nav-link">
                        <i class="far fa-bell"></i>
                        <?php if ($unread_notifications_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="messages.php" class="nav-link">
                        <i class="fas fa-paper-plane"></i>
                        <?php if ($unread_message_count > 0): ?>
                            <span class="message-badge"><?php echo $unread_message_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="bookmarks.php" class="nav-link"><i class="far fa-bookmark"></i></a>
                    <a href="profile.php?username=<?php echo $current_user['username']; ?>" class="nav-link profile-link">
                        <img src="uploads/profile_pics/<?php echo $current_user['profile_pic']; ?>" alt="Profile" class="profile-pic-small">
                    </a>
                    <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i></a>
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Log In</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
            </nav>
        </div>
    </header>
    <main class="main-content <?php echo $current_theme; ?>-theme">
        <div class="container">