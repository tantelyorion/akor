<?php
require_once 'includes/header.php';

// Get hashtag from URL
$hashtag = isset($_GET['tag']) ? clean_input($_GET['tag']) : '';

// If no hashtag specified, redirect to home
if (empty($hashtag)) {
    header('Location: index.php');
    exit();
}

// Get hashtag data
$query = "SELECT * FROM hashtags WHERE hashtag_name = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $hashtag);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$hashtag_data = mysqli_fetch_assoc($result);

// Check if hashtag exists
if (!$hashtag_data) {
    header('Location: index.php');
    exit();
}

// Get posts for the hashtag
$posts = get_posts_by_hashtag($conn, $hashtag_data['hashtag_id']);
?>

<div class="hashtag-page">
    <div class="hashtag-header">
        <h1 class="hashtag-title">#<?php echo $hashtag; ?></h1>
        <div class="hashtag-post-count"><?php echo count($posts); ?> posts</div>
    </div>
    
    <div class="profile-grid">
        <?php foreach ($posts as $post): ?>
            <a href="post.php?id=<?php echo $post['post_id']; ?>" class="profile-post">
                <img src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Post" class="profile-post-img">
                <div class="profile-post-overlay">
                    <div class="profile-post-stats">
                        <div class="profile-post-stat">
                            <i class="fas fa-heart profile-post-icon"></i>
                            <?php echo count_likes($conn, $post['post_id']); ?>
                        </div>
                        <div class="profile-post-stat">
                            <i class="fas fa-comment profile-post-icon"></i>
                            <?php echo count_comments($conn, $post['post_id']); ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($posts)): ?>
        <div class="empty-hashtag">
            <p>No posts with this hashtag yet. Be the first to share something!</p>
            <a href="create.php" class="btn btn-primary">Create Post</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>