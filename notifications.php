<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

require_login();

// Marquer toutes les notifications comme lues
mark_notifications_as_read($conn, $_SESSION['user_id']);

// Récupérer les notifications avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$notifications = get_user_notifications($conn, $_SESSION['user_id'], $limit, $offset);
$total_notifications = count_user_notifications($conn, $_SESSION['user_id']);
?>

<style>
    .notifications-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 1.5rem;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.notifications-title {
    font-size: 2rem;
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.empty-notifications {
    text-align: center;
    padding: 3rem 1rem;
    color: #666;
}

.empty-notifications p:first-child {
    font-size: 1.2rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.empty-notifications p:last-child {
    font-size: 0.95rem;
    color: #999;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    background-color: #fff;
    border: 1px solid #eee;
}

.notification-item.unread {
    background-color: #f8fafd;
    border-left: 3px solid #4a90e2;
}

.notification-item:hover {
    background-color: #f5f7fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.notification-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 1rem;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.notification-content {
    flex: 1;
}

.notification-user {
    font-weight: 600;
    color: #333;
    text-decoration: none;
}

.notification-user:hover {
    text-decoration: underline;
    color: #4a90e2;
}

.notification-meta {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #777;
}

.notification-time {
    margin-right: 1rem;
}

.notification-post-link {
    color: #4a90e2;
    text-decoration: none;
    font-weight: 500;
}

.notification-post-link:hover {
    text-decoration: underline;
}

.pagination {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

.btn-primary {
    background-color: #4a90e2;
    color: white;
    border: 1px solid #3a80d2;
}

.btn-primary:hover {
    background-color: #3a80d2;
}

/* Style pour mobile */
@media (max-width: 768px) {
    .notifications-container {
        margin: 1rem;
        padding: 1rem;
    }
    
    .notification-avatar {
        width: 40px;
        height: 40px;
    }
    
    .notification-content p {
        font-size: 0.95rem;
    }
}
</style>

<div class="notifications-container">
    <h1 class="notifications-title">Notifications</h1>
    
    <?php if (empty($notifications)): ?>
        <div class="empty-notifications">
            <p>No notifications yet</p>
            <p>When you get notifications, they'll appear here.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                    <a href="profile.php?username=<?php echo $notification['username']; ?>">
                        <img src="uploads/profile_pics/<?php echo $notification['profile_pic']; ?>" 
                             alt="<?php echo $notification['username']; ?>" 
                             class="notification-avatar">
                    </a>
                    
                    <div class="notification-content">
                        <p>
                            <a href="profile.php?username=<?php echo $notification['username']; ?>" 
                               class="notification-user">
                                <?php echo $notification['username']; ?>
                            </a>
                            <?php echo $notification['message']; ?>
                        </p>
                        
                        <div class="notification-meta">
                            <span class="notification-time">
                                <?php echo format_date($notification['created_at']); ?>
                            </span>
                            
                            <?php if ($notification['post_id']): ?>
                                <a href="post.php?id=<?php echo $notification['post_id']; ?>" 
                                   class="notification-post-link">
                                    View post
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
            <?php endif; ?>
            
            <?php if (count($notifications) == $limit): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-primary">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Écouter les nouvelles notifications en temps réel
if (typeof(EventSource) !== "undefined") {
    const eventSource = new EventSource("notifications_sse.php?last_check=<?php echo time(); ?>");
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (data.new_notifications) {
            // Recharger les notifications
            location.reload();
        }
    };
}
</script>

<?php require_once 'includes/footer.php'; ?>