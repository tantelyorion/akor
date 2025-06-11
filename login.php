<?php
require_once 'includes/header.php';

// Redirect if already logged in
redirect_if_logged_in();

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Check if username exists
        $user = get_user_by_username($conn, $username);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to home page
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<div class="form-container">
    <h1 class="form-title">Instagram Clone</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <input type="text" name="username" placeholder="Username" class="form-input" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" class="form-input">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-full">Log In</button>
        </div>
    </form>
    
    <div class="form-footer">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>