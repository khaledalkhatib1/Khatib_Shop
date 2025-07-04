<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']) ? true : false;
        
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif (!$terms) {
            $error = 'You must agree to the Terms and Conditions.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email address is already registered.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login.';
                    
                    // Optionally auto-login the user
                    // $_SESSION['user_id'] = $conn->insert_id;
                    // $_SESSION['user_name'] = $name;
                    // $_SESSION['user_email'] = $email;
                    // $_SESSION['is_admin'] = 0;
                    // header('Location: index.php');
                    // exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-form">
            <h1>Create an Account</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                    <p><a href="login.php" class="btn btn-primary">Login Now</a></p>
                </div>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <small>Password must be at least 6 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php">Terms and Conditions</a></label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
