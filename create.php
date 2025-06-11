<?php
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

$error = '';
$success = '';

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $caption = clean_input($_POST['caption']);
    
    // Check if image is uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please select an image to upload";
    } else {
        // Upload image
        $upload_dir = "uploads/posts/";
        $upload_result = upload_image($_FILES['image'], $upload_dir);
        
        if ($upload_result['success']) {
            // Save post to database
            $image_url = $upload_result['file_path'];
            $user_id = $_SESSION['user_id'];
            
            $query = "INSERT INTO posts (user_id, image_url, caption) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $image_url, $caption);
            
            if (mysqli_stmt_execute($stmt)) {
                $post_id = mysqli_insert_id($conn);
                
                // Extract and save hashtags if present
                if (!empty($caption)) {
                    save_hashtags($conn, $post_id, $caption);
                }
                
                $success = "Post created successfully!";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        } else {
            $error = $upload_result['message'];
        }
    }
}
?>

<div class="create-post-container">
    <h1 class="create-post-title">Create New Post</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <div class="text-center">
            <a href="index.php" class="btn btn-primary">View Feed</a>
            <a href="profile.php" class="btn btn-secondary">View Profile</a>
        </div>
    <?php else: ?>
    
    <form method="post" action="" enctype="multipart/form-data">
        <div class="create-post-preview">
            <div class="create-post-placeholder">
                <i class="far fa-image fa-3x"></i>
                <p>Select an image to preview</p>
            </div>
        </div>
        
        <div class="form-group">
            <label for="image" class="form-label">Upload Image</label>
            <input type="file" name="image" id="image" class="form-input" accept="image/*">
        </div>
        
        <div class="form-group create-post-caption">
            <label for="caption" class="form-label">Caption</label>
            <textarea name="caption" id="caption" class="caption-input" placeholder="Write a caption... #hashtags"></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-full">Share</button>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>