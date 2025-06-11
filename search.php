<?php
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

$search_term = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$results_users = [];
$results_hashtags = [];

if (!empty($search_term)) {
    // Search users
    $results_users = search_users($conn, $search_term);
    
    // Search hashtags
    $results_hashtags = search_hashtags($conn, $search_term);
}
?>

<div class="search-results">
    <h1 class="search-title">Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h1>
    
    <?php if (empty($results_users) && empty($results_hashtags)): ?>
        <p class="no-results">No results found. Try another search term.</p>
    <?php else: ?>
        <?php if (!empty($results_users)): ?>
            <h2 class="search-result-heading">Users</h2>
            <div class="search-result-users">
                <?php foreach ($results_users as $user): ?>
                    <a href="profile.php?username=<?php echo $user['username']; ?>" class="search-result-user">
                        <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="<?php echo $user['username']; ?>" class="search-result-user-pic">
                        <div class="search-result-user-info">
                            <div class="search-result-username"><?php echo $user['username']; ?></div>
                            <div class="search-result-name"><?php echo $user['full_name']; ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($results_hashtags)): ?>
            <h2 class="search-result-heading">Hashtags</h2>
            <div class="search-result-hashtags">
                <?php foreach ($results_hashtags as $hashtag): ?>
                    <a href="hashtag.php?tag=<?php echo $hashtag['hashtag_name']; ?>" class="search-result-hashtag">
                        #<?php echo $hashtag['hashtag_name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>