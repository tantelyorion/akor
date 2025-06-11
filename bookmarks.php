<?php
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Handle remove bookmark action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_bookmark'])) {
    $post_id = (int)$_POST['post_id'];
    if (toggle_bookmark($conn, $_SESSION['user_id'], $post_id)) {
        $_SESSION['message'] = "Post removed from your bookmarks";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error removing bookmark";
        $_SESSION['message_type'] = "error";
    }
    header("Location: bookmarks.php");
    exit();
}

// Get bookmarked posts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$posts = get_bookmarked_posts($conn, $_SESSION['user_id'], $limit, $offset);
?>

<div class="bookmarks-page">
    <h1>Your Saved Posts</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo $_SESSION['message']; ?>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <i class="far fa-bookmark fa-3x"></i>
            <h2>No saved posts yet</h2>
            <p>Save posts you want to revisit by clicking the bookmark icon below any post.</p>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post-grid-item">
                    <a href="post.php?id=<?php echo $post['post_id']; ?>">
                        <img src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Post">
                        <div class="post-overlay">
                            <span><i class="fas fa-heart"></i> <?php echo count_likes($conn, $post['post_id']); ?></span>
                            <span><i class="fas fa-comment"></i> <?php echo count_comments($conn, $post['post_id']); ?></span>
                        </div>
                    </a>
                    <form method="POST" action="bookmarks.php" class="remove-bookmark-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                        <button type="submit" name="remove_bookmark" class="remove-bookmark-btn" title="Remove from bookmarks">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="bookmarks.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
            <?php endif; ?>
            
            <?php if (count($posts) == $limit): ?>
                <a href="bookmarks.php?page=<?php echo $page + 1; ?>" class="btn btn-primary">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Gestion de la suppression des bookmarks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-bookmark-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const postItem = this.closest('.post-grid-item');
            
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
                    // Animation de disparition
                    postItem.style.opacity = '0';
                    postItem.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        postItem.remove();
                        
                        // Si plus de posts, afficher le message empty state
                        if (document.querySelectorAll('.post-grid-item').length === 0) {
                            document.querySelector('.posts-grid').innerHTML = `
                                <div class="empty-state">
                                    <i class="far fa-bookmark fa-3x"></i>
                                    <h2>No saved posts yet</h2>
                                    <p>Save posts you want to revisit by clicking the bookmark icon below any post.</p>
                                </div>
                            `;
                        }
                    }, 300);
                } else {
                    alert('Error removing bookmark');
                }
            });
        });
    });
});

</script>

<?php require_once 'includes/footer.php'; ?>