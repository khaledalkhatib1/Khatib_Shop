<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$debug_info = '';


$debug_mode = true;

function debug_log($message) {
    global $debug_mode, $debug_info;
    if ($debug_mode) {
        $debug_info .= $message . "<br>";
        error_log($message);
    }
}


if (isset($_SESSION['user_id'])) {
    
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
        debug_log("CSRF validation failed");
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        debug_log("Login attempt for email: " . $email);
        
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
            debug_log("Empty email or password");
        } else {
            
            $stmt = $conn->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
            if (!$stmt) {
                $error = "Database error: " . $conn->error;
                debug_log("Database prepare error: " . $conn->error);
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                debug_log("Query executed, found rows: " . $result->num_rows);
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    debug_log("User found: ID=" . $user['id'] . ", is_admin=" . $user['is_admin']);
                    debug_log("Stored password hash: " . substr($user['password'], 0, 10) . "...");
                    
                    
                    $password_verified = password_verify($password, $user['password']);
                    debug_log("Password verification result: " . ($password_verified ? "SUCCESS" : "FAILED"));
                    
                    if ($password_verified) {
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['is_admin'] = (int)$user['is_admin']; 
                        
                        debug_log("Session created: user_id=" . $_SESSION['user_id'] . ", is_admin=" . $_SESSION['is_admin']);
                        
                       
                        if ($remember) {
                            try {
                             
                                $table_check = $conn->query("SHOW TABLES LIKE 'user_tokens'");
                                if ($table_check->num_rows == 0) {
                                   
                                    $conn->query("CREATE TABLE IF NOT EXISTS user_tokens (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        user_id INT NOT NULL,
                                        token VARCHAR(255) NOT NULL,
                                        expires DATETIME NOT NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        INDEX (token),
                                        INDEX (user_id),
                                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                                    )");
                                    debug_log("Created user_tokens table");
                                }
                                
                                $token = bin2hex(random_bytes(32));
                                $expires = time() + (30 * 24 * 60 * 60); 
                                $expires_date = date('Y-m-d H:i:s', $expires);
                                
                               
                                $token_stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                                if ($token_stmt) {
                                    $token_stmt->bind_param("iss", $user['id'], $token, $expires_date);
                                    $token_stmt->execute();
                                    $token_stmt->close();
                                    
                                   
                                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                                    debug_log("Remember me token created and cookie set");
                                } else {
                                    debug_log("Failed to prepare token insert statement: " . $conn->error);
                                }
                            } catch (Exception $e) {
                                debug_log("Exception in remember me token creation: " . $e->getMessage());
                            }
                        }
                        
                        
                        if ((int)$user['is_admin'] === 1) {
                            debug_log("Redirecting to admin dashboard");
                            header('Location: admin/dashboard.php');
                            exit;
                        } else {
                           
                            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                                debug_log("Redirecting to: " . $_GET['redirect']);
                                header('Location: ' . $_GET['redirect']);
                            } else {
                                debug_log("Redirecting to index.php");
                                header('Location: index.php');
                            }
                            exit;
                        }
                    } else {
                        $error = 'Invalid email or password.';
                        debug_log("Password verification failed");
                    }
                } else {
                    $error = 'Invalid email or password.';
                    debug_log("No user found with email: " . $email);
                }
                
                $stmt->close();
            }
        }
    }
}


if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    debug_log("Found remember_token cookie: " . substr($token, 0, 10) . "...");
    
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'user_tokens'");
        if ($table_check->num_rows == 0) {
            debug_log("user_tokens table doesn't exist");
            setcookie('remember_token', '', time() - 3600, '/');
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires > NOW()");
            
            if ($stmt === false) {
                debug_log("Database prepare error: " . $conn->error);
                setcookie('remember_token', '', time() - 3600, '/');
            } else {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                
                debug_log("Remember token query executed, found rows: " . $result->num_rows);
                
                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $user_id = $row['user_id'];
                    debug_log("Found valid token for user_id: " . $user_id);
                    
                    $user_stmt = $conn->prepare("SELECT id, name, email, is_admin FROM users WHERE id = ?");
                    if ($user_stmt === false) {
                        debug_log("Database prepare error: " . $conn->error);
                    } else {
                        $user_stmt->bind_param("i", $user_id);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        
                        if ($user_result->num_rows === 1) {
                            $user = $user_result->fetch_assoc();
                            debug_log("User found: ID=" . $user['id'] . ", is_admin=" . $user['is_admin']);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['is_admin'] = (int)$user['is_admin'];
                            
                            debug_log("Session created from remember token");
                            
                            if ((int)$user['is_admin'] === 1) {
                                debug_log("Redirecting to admin dashboard");
                                header('Location: admin/dashboard.php');
                            } else {
                                debug_log("Redirecting to index.php");
                                header('Location: index.php');
                            }
                            exit;
                        } else {
                            debug_log("No user found with ID: " . $user_id);
                        }
                        $user_stmt->close();
                    }
                } else {
                    debug_log("No valid token found or token expired");
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        debug_log("Exception in remember me cookie processing: " . $e->getMessage());
    }
    
    debug_log("Clearing invalid remember_token cookie");
    setcookie('remember_token', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .debug-toggle {
            margin-top: 10px;
            text-align: center;
        }
        .debug-toggle button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-form">
            <h1>Login to Your Account</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group remember-forgot">
                    <div>
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
            
            <?php if ($debug_mode && !empty($debug_info)): ?>
                <div class="debug-toggle">
                    <button onclick="document.getElementById('debug-info').style.display = document.getElementById('debug-info').style.display === 'none' ? 'block' : 'none';">
                        Toggle Debug Info
                    </button>
                </div>
                <div id="debug-info" class="debug-info" style="display: none;">
                    <strong>Debug Information:</strong><br>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>
