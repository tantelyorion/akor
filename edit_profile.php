<?php
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

$error = '';
$success = '';

// Get current user data
$user = get_user_by_id($conn, $_SESSION['user_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $bio = clean_input($_POST['bio']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if username already exists (if changed)
    if ($username != $user['username']) {
        $check_username = "SELECT * FROM users WHERE username = ? AND user_id != ?";
        $stmt = mysqli_prepare($conn, $check_username);
        mysqli_stmt_bind_param($stmt, "si", $username, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username already exists";
        }
    }
    
    // Check if email already exists (if changed)
    if (empty($error) && $email != $user['email']) {
        $check_email = "SELECT * FROM users WHERE email = ? AND user_id != ?";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already exists";
        }
    }
    
    // Process password change if requested
    if (empty($error) && !empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $error = "New password cannot be empty";
        } elseif ($new_password != $confirm_password) {
            $error = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters";
        }
    }
    
    // Upload profile picture if provided
    $profile_pic = $user['profile_pic'];
    if (empty($error) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_dir = "uploads/profile_pics/";
        $upload_result = upload_image($_FILES['profile_pic'], $upload_dir);
        
        if ($upload_result['success']) {
            $profile_pic = $upload_result['file_path'];
        } else {
            $error = $upload_result['message'];
        }
    }
    
    // Update user data if no errors
    if (empty($error)) {
        // Start with basic update
        $query = "UPDATE users SET username = ?, email = ?, full_name = ?, bio = ?, profile_pic = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $full_name, $bio, $profile_pic, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update password if needed
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
            }
            
            // Update session username if changed
            if ($username != $_SESSION['username']) {
                $_SESSION['username'] = $username;
            }
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $user = get_user_by_id($conn, $_SESSION['user_id']);
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<div class="form-container">
    <h1 class="form-title">Edit Profile</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" action="" enctype="multipart/form-data">
        <div class="profile-edit-pic">
            <img src="uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="Profile Picture" class="edit-profile-pic">
            <div class="form-group">
                <label for="profile_pic" class="form-label">Change Profile Picture</label>
                <input type="file" name="profile_pic" id="profile_pic" class="form-input" accept="image/*">
            </div>
        </div>
        
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-input" value="<?php echo $user['username']; ?>">
        </div>
        
        <div class="form-group">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-input" value="<?php echo $user['full_name']; ?>">
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-input" value="<?php echo $user['email']; ?>">
        </div>
        
        <div class="form-group">
            <label for="bio" class="form-label">Bio</label>
            <textarea name="bio" id="bio" class="form-input" rows="4"><?php echo $user['bio']; ?></textarea>
        </div>
        
        <h2 class="form-subtitle">Change Password</h2>
        
        <div class="form-group">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" name="current_password" id="current_password" class="form-input">
        </div>
        
        <div class="form-group">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" name="new_password" id="new_password" class="form-input">
        </div>
        
        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-input">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>