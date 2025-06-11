<?php
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

// Get conversations
$conversations = get_conversations($conn, $_SESSION['user_id']);

// Get specific user to message if provided
$other_user_id = 0;
$other_user = null;
$messages = [];

if (isset($_GET['user'])) {
    $username = clean_input($_GET['user']);
    $other_user = get_user_by_username($conn, $username);
    
    if ($other_user) {
        $other_user_id = $other_user['user_id'];
        $messages = get_conversation_messages($conn, $_SESSION['user_id'], $other_user_id);
    }
} elseif (!empty($conversations)) {
    // If no specific user requested, show first conversation
    $other_user_id = $conversations[0]['other_user_id'];
    $other_user = get_user_by_id($conn, $other_user_id);
    $messages = get_conversation_messages($conn, $_SESSION['user_id'], $other_user_id);
}
?>

<div class="messages-container">
<div class="conversations-list">
    <br>
    <div class="conversations-header">
        <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    </div>
    <br>
    <?php foreach ($conversations as $conv): 
        // Double vérification de l'existence de l'utilisateur
        $other_user = get_user_by_id($conn, $conv['other_user_id']);
        if (!$other_user) continue;
        
        // Vérification des champs requis
        $username = $other_user['username'] ?? 'Utilisateur inconnu';
        $profile_pic = $other_user['profile_pic'] ?? 'default_profile.jpg';
        $last_message = $conv['last_message'] ?? '';
    ?>
        <a href="messages.php?user=<?php echo htmlspecialchars($username); ?>" 
           class="conversation <?php echo $conv['other_user_id'] == $other_user_id ? 'active' : ''; ?>">
            <img src="uploads/profile_pics/<?php echo htmlspecialchars($profile_pic); ?>" 
                 alt="<?php echo htmlspecialchars($username); ?>" class="conversation-pic">
            <div class="conversation-info">
                <div class="conversation-username"><?php echo htmlspecialchars($username); ?></div>
                <div class="conversation-last-message"><?php echo htmlspecialchars($last_message); ?></div>
            </div>
            <?php if (($conv['unread_count'] ?? 0) > 0): ?>
                <div class="conversation-unread"></div>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
    
    <div class="chat-container" data-receiver-id="<?php echo $other_user_id; ?>">
        <?php if ($other_user): ?>
            <div class="chat-header">
                <img src="uploads/profile_pics/<?php echo $other_user['profile_pic']; ?>" alt="<?php echo $other_user['username']; ?>" class="chat-header-pic">
                <div class="chat-header-username"><?php echo $other_user['username']; ?></div>
            </div>
            
            <div class="chat-messages">
    <?php foreach ($messages as $message): ?>
        <?php 
        // Correction de l'erreur "username"
        $sender = get_user_by_id($conn, $message['sender_id']);
        $username = $sender ? $sender['username'] : 'Utilisateur inconnu';
        ?>
        
        <div class="chat-message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received'; ?>">
            <?php if ($message['message_type'] === 'text'): ?>
                <div class="message-text"><?php echo htmlspecialchars($message['message_text']); ?></div>
            
            <?php elseif ($message['message_type'] === 'image'): ?>
                <div class="message-attachment">
                    <img src="uploads/messages/<?php echo htmlspecialchars($message['file_path']); ?>" alt="Image envoyée">
                </div>
            
            <?php elseif ($message['message_type'] === 'video'): ?>
                <div class="message-attachment">
                    <video controls>
                        <source src="uploads/messages/<?php echo htmlspecialchars($message['file_path']); ?>" type="<?php echo htmlspecialchars($message['file_mime_type']); ?>">
                    </video>
                </div>
            
            <?php elseif ($message['message_type'] === 'audio'): ?>
                <div class="message-attachment">
                    <audio controls>
                        <source src="uploads/messages/<?php echo htmlspecialchars($message['file_path']); ?>" type="<?php echo htmlspecialchars($message['file_mime_type']); ?>">
                    </audio>
                </div>
            
            <?php else: // Pour tous les autres types de fichiers ?>
                <div class="message-attachment file-attachment">
                    <a href="uploads/messages/<?php echo htmlspecialchars($message['file_path']); ?>" download="<?php echo htmlspecialchars($message['file_name']); ?>">
                        <i class="fas fa-file-download"></i>
                        Télécharger <?php echo htmlspecialchars($message['file_name']); ?>
                        (<?php echo formatFileSize($message['file_size']); ?>)
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="message-time"><?php echo format_date($message['created_at']); ?></div>
        </div>
    <?php endforeach; ?>
</div>
            
            <form class="chat-input-container chat-form" data-receiver-id="<?php echo $other_user_id; ?>">
                <div class="message-options">
                    <button type="button" class="attachment-btn" title="Ajouter une pièce jointe">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="message-attachment" multiple accept="image/*,video/*" style="display: none;">
                    </button>
                    <button type="button" class="voice-message-btn" title="Message vocal">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                <input type="text" class="chat-input" placeholder="Message...">
                <button type="submit" class="chat-send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <div id="voice-message-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Enregistrement vocal</h3>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="voice-recorder">
                            <div class="recording-status">
                                <div class="recording-indicator"></div>
                                <span>Enregistrement en cours...</span>
                            </div>
                            <div class="recording-timer">00:00</div>
                            <div class="recording-controls">
                                <button class="stop-recording">Arrêter</button>
                                <button class="cancel-recording">Annuler</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="empty-chat">
                <p>Select a conversation or search for a user to start messaging.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>