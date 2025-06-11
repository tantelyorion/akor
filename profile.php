<?php
require_once 'includes/header.php';

// Get username from URL
$username = isset($_GET['username']) ? clean_input($_GET['username']) : '';

// If no username specified, use the logged in user's username
if (empty($username) && $is_logged_in) {
    $username = $_SESSION['username'];
}

// Get user data
$user = get_user_by_username($conn, $username);

// Check if user exists
if (!$user) {
    header('Location: index.php');
    exit();
}

// Get profile data
$user_id = $user['user_id'];
$posts_count = count_user_posts($conn, $user_id);
$followers_count = count_followers($conn, $user_id);
$following_count = count_following($conn, $user_id);

// Check if current user is following this user
$is_following = false;
if ($is_logged_in && $user_id != $_SESSION['user_id']) {
    $is_following = is_following($conn, $_SESSION['user_id'], $user_id);
}

// Get posts for the profile
$posts = get_profile_posts($conn, $user_id);
?>

<div class="profile">
    <div class="profile-header">
        <div class="profile-pic-container">
            <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="<?php echo $user['username']; ?>" class="profile-pic">
        </div>
        
        <div class="profile-info">
            <div class="profile-username">
                <?php echo $user['username']; ?>
                
                <?php if ($is_logged_in): ?>
                    <?php if ($user_id == $_SESSION['user_id']): ?>
                        <a href="edit_profile.php" class="btn btn-secondary btn-sm">Edit Profile</a>
                    <?php else: ?>
                        <button class="btn <?php echo $is_following ? 'btn-secondary following' : 'btn-primary'; ?> follow-btn" data-username="<?php echo $user['username']; ?>">
                            <?php echo $is_following ? 'Following' : 'Follow'; ?>
                        </button>
                        <a href="messages.php?user=<?php echo $user['username']; ?>" class="btn btn-secondary btn-sm">Message</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="profile-stats">
                <div class="profile-stat">
                    <span class="profile-stat-count"><?php echo $posts_count; ?></span> posts
                </div>
                <div class="profile-stat">
                    <span class="profile-stat-count follower-count"><?php echo $followers_count; ?></span> followers
                </div>
                <div class="profile-stat">
                    <span class="profile-stat-count"><?php echo $following_count; ?></span> following
                </div>
            </div>
            
            <div class="profile-bio">
                <div class="profile-name"><?php echo $user['full_name']; ?></div>
                <p><?php echo $user['bio']; ?></p>
            </div>
        </div>
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
</div>

<?php require_once 'includes/footer.php'; ?>