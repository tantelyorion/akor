<?php
require_once 'includes/header.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post data
$post = get_post_details($conn, $post_id);

// Check if post exists
if (!$post) {
    header('Location: index.php');
    exit();
}

// Get comments for the post
$comments = get_post_comments($conn, $post_id);

// Get like info
$likes_count = count_likes($conn, $post_id);
$has_liked = $is_logged_in ? has_liked_post($conn, $_SESSION['user_id'], $post_id) : false;
?>

<div class="single-post">
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
            <?php if ($is_logged_in): ?>
            <button class="post-action-btn like-btn <?php echo $has_liked ? 'liked' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="<?php echo $has_liked ? 'fas' : 'far'; ?> fa-heart"></i>
            </button>
            <button class="post-action-btn">
                <i class="far fa-comment"></i>
            </button>
            <button class="post-action-btn">
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
            
            <?php endif; ?>
        </div>
        
        <div class="post-likes like-count-<?php echo $post['post_id']; ?>">
            <?php echo $likes_count . ' ' . ($likes_count == 1 ? 'like' : 'likes'); ?>
        </div>
        
        <div class="post-caption">
            <span class="post-caption-username"><?php echo $post['username']; ?></span>
            <span><?php echo $post['caption']; ?></span>
        </div>
        
        <div class="comments-container">
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <img src="uploads/profile_pics/<?php echo $comment['profile_pic']; ?>" alt="<?php echo $comment['username']; ?>" class="comment-user-pic">
                    <div class="comment-content">
                        <div>
                            <span class="comment-username"><?php echo $comment['username']; ?></span>
                            <span class="comment-text"><?php echo $comment['comment']; ?></span>
                        </div>
                        <div class="comment-time"><?php echo format_date($comment['created_at']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($is_logged_in): ?>
        <form class="post-add-comment comment-form" data-post-id="<?php echo $post['post_id']; ?>">
            <input type="text" class="comment-input" placeholder="Add a comment...">
            <button type="submit" class="comment-submit">Post</button>
        </form>
        <?php endif; ?>
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
</script>
<?php require_once 'includes/footer.php'; ?>