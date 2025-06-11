<?php
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Get feed posts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$posts = get_feed_posts($conn, $_SESSION['user_id'], $limit, $offset);
?>

<div class="feed">
    <?php if (empty($posts)): ?>
        <div class="empty-feed">
            <h2>Welcome to Instagram Clone</h2>
            <p>Follow users to see their posts in your feed or create your first post!</p>
            <div class="empty-feed-actions">
                <a href="explore.php" class="btn btn-primary">Explore Users</a>
                <a href="create.php" class="btn btn-secondary">Create Post</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-header">
                    <a href="profile.php?username=<?php echo $post['username']; ?>">
                        <img src="uploads/profile_pics/<?php echo $post['profile_pic']; ?>" alt="<?php echo $post['username']; ?>" class="post-user-pic">
                    </a>
                    <div>
                        <a href="profile.php?username=<?php echo $post['username']; ?>" class="post-username"><?php echo $post['username']; ?></a>
                        <span class="post-time"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </div>
                
                <div class="post-image-container">
                    <img src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Post" class="post-image" data-post-id="<?php echo $post['post_id']; ?>">
                </div>
                
                <div class="post-actions">
                    <button class="post-action-btn like-btn <?php echo has_liked_post($conn, $_SESSION['user_id'], $post['post_id']) ? 'liked' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                        <i class="<?php echo has_liked_post($conn, $_SESSION['user_id'], $post['post_id']) ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                    <a href="post.php?id=<?php echo $post['post_id']; ?>" class="post-action-btn">
                        <i class="far fa-comment"></i>
                    </a>
                    <button class="post-action-btn share-btn" data-post-id="<?php echo $post['post_id']; ?>">
                        <i class="far fa-paper-plane"></i>
                    </button>
                    
                    <button class="post-action-btn bookmark-btn <?php echo has_bookmarked_post($conn, $_SESSION['user_id'], $post['post_id']) ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                        <i class="<?php echo has_bookmarked_post($conn, $_SESSION['user_id'], $post['post_id']) ? 'fas' : 'far'; ?> fa-bookmark"></i>
                    </button>

                    <?php if ($is_logged_in && $post['user_id'] != $_SESSION['user_id']): ?>
                        <a href="report.php?post_id=<?php echo $post['post_id']; ?>" class="post-action-btn" title="Report">
                            <i class="fas fa-flag"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($is_logged_in && $post['user_id'] == $_SESSION['user_id']): ?>
                        <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="post-action-btn" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($is_logged_in && $post['user_id'] == $_SESSION['user_id']): ?>
                        <a href="delete_post.php?id=<?php echo $post['post_id']; ?>" class="post-action-btn" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="post-likes like-count-<?php echo $post['post_id']; ?>">
                    <?php 
                    $likes_count = count_likes($conn, $post['post_id']);
                    echo $likes_count . ' ' . ($likes_count == 1 ? 'like' : 'likes'); 
                    ?>
                </div>
                
                <div class="post-caption">
                    <span class="post-caption-username"><?php echo $post['username']; ?></span>
                    <span><?php echo $post['caption']; ?></span>
                </div>
                
                <?php 
                $comments_count = count_comments($conn, $post['post_id']);
                if ($comments_count > 0): 
                ?>
                <a href="post.php?id=<?php echo $post['post_id']; ?>" class="post-comments-link comment-count-<?php echo $post['post_id']; ?>">
                    View all <?php echo $comments_count; ?> comments
                </a>
                <?php endif; ?>
                
                <form class="post-add-comment comment-form" data-post-id="<?php echo $post['post_id']; ?>">
                    <input type="text" class="comment-input" placeholder="Add a comment...">
                    <button type="submit" class="comment-submit">Post</button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
            <?php endif; ?>
            
            <?php if (count($posts) == $limit): ?>
                <a href="index.php?page=<?php echo $page + 1; ?>" class="btn btn-primary">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Ajoutez ceci avant le footer -->
<div id="shareModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Partager cette publication</h3>
        <input type="text" id="shareUrl" readonly>
        <button id="copyLinkBtn">Copier le lien</button>
        <!-- Vous pourriez ajouter des options de partage vers d'autres plateformes ici -->
    </div>
</div>
<script>
    // Gestion des bookmarks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.bookmark-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const icon = this.querySelector('i');
            
            fetch('ajax/toggle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('bookmarked');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                }
            });
        });
    });
});


// Gestion du partage avec modal
document.querySelectorAll('.share-btn').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.getAttribute('data-post-id');
        const postUrl = `${window.location.origin}/post.php?id=${postId}`;
        
        const modal = document.getElementById('shareModal');
        const shareUrlInput = document.getElementById('shareUrl');
        const copyBtn = document.getElementById('copyLinkBtn');
        
        shareUrlInput.value = postUrl;
        modal.style.display = 'block';
        
        copyBtn.onclick = function() {
            navigator.clipboard.writeText(postUrl).then(() => {
                copyBtn.textContent = 'Copié!';
                setTimeout(() => {
                    copyBtn.textContent = 'Copier le lien';
                    modal.style.display = 'none';
                }, 1500);
            });
        };
    });
});

// Fermer le modal
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('shareModal').style.display = 'none';
});
</script>
<?php require_once 'includes/footer.php'; ?>