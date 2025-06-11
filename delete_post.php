<?php
require_once 'includes/header.php';




if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = get_post_details($conn, $post_id);

// Check if post exists and belongs to current user
if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Delete post image file
        $image_path = 'uploads/posts/' . $post['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Delete post from database (CASCADE will handle related data)
        $query = "DELETE FROM posts WHERE post_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = 'Post deleted successfully';
            $_SESSION['message_type'] = 'success';
            header('Location: profile.php?username=' . $current_user['username']);
            exit();
        } else {
            $error = 'An error occurred while deleting the post';
        }
    } else {
        header('Location: post.php?id=' . $post_id);
        exit();
    }
}
?>

<div class="delete-post-page">
    <div class="delete-post-container">
        <h2>Delete Post</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="post-preview">
            <img src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Post being deleted">
        </div>
        
        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
        
        <form method="POST">
            <div class="form-actions">
                <button type="submit" name="confirm_delete" class="btn btn-danger">Delete Permanently</button>
                <a href="post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>