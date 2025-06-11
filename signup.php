<?php
require_once 'includes/header.php';

// Redirect if already logged in
redirect_if_logged_in();

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $full_name = clean_input($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username exists
        $check_username = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $check_username);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username already exists";
        } else {
            // Check if email exists
            $check_email = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $check_email);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error = "Email already exists";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (username, email, full_name, password) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $full_name, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<div class="form-container">
    <h1 class="form-title">Instagram Clone</h1>
    <p class="text-center">Sign up to see photos and videos from your friends.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
    
    <form method="post" action="">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" class="form-input" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="text" name="full_name" placeholder="Full Name" class="form-input" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="text" name="username" placeholder="Username" class="form-input" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" class="form-input">
        </div>
        
        <div class="form-group">
            <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-input">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-full">Sign Up</button>
        </div>
    </form>
    
    <?php endif; ?>
    
    <div class="form-footer">
        Have an account? <a href="login.php">Log In</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>