<?php
require_once 'includes/header.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$post = get_post_details($conn, $post_id);

if (!$post) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = clean_input($_POST['reason']);
    $description = clean_input($_POST['description'] ?? '');
    
    if (empty($reason)) {
        $error = 'Please select a reason for reporting';
    } elseif (has_reported_post($conn, $_SESSION['user_id'], $post_id)) {
        $error = 'You have already reported this post';
    } else {
        if (report_post($conn, $post_id, $_SESSION['user_id'], $reason, $description)) {
            $success = 'Thank you for your report. We will review it shortly.';
        } else {
            $error = 'An error occurred while submitting your report';
        }
    }
}
?>

<div class="report-page">
    <div class="report-container">
        <h2>Report Post</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <div class="post-preview">
                <img src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Post being reported">
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="reason">Reason for reporting:</label>
                    <select name="reason" id="reason" required>
                        <option value="">Select a reason</option>
                        <?php foreach (get_report_reasons() as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Additional details (optional):</label>
                    <textarea name="description" id="description" rows="4"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                    <a href="post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>