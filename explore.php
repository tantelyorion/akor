<?php
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

// Get recent posts from users the current user is not following
$query = "SELECT p.*, u.username, u.profile_pic 
          FROM posts p 
          JOIN users u ON p.user_id = u.user_id
          WHERE p.user_id != ? 
          AND p.user_id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
          ORDER BY p.created_at DESC
          LIMIT 18";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$posts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}
?>

<div class="explore">
    <h1 class="explore-title">Explore</h1>
    <br>
    <div class="explore-grid">
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
        <div class="empty-explore">
            <p>No posts to explore yet. Be the first to share something!</p>
            <br>
            <a href="create.php" class="btn btn-primary">Create Post</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>