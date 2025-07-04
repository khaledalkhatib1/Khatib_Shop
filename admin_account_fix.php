<?php
// This script will check if an admin account exists, create one if it doesn't,
// or reset the password of an existing admin account

// Start session and include database connection
session_start();
require_once 'includes/db.php';

// Function to create a new admin account
function create_admin_account($conn, $email, $password) {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email exists, update the password and make sure is_admin is set to 1
        $user = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user['id']);
        $success = $update_stmt->execute();
        $update_stmt->close();
        
        return $success ? "Admin account updated successfully!" : "Error updating admin account: " . $conn->error;
    } else {
        // Email doesn't exist, create a new admin account
        $name = "Admin";
        $is_admin = 1;
        
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("sssi", $name, $email, $hashed_password, $is_admin);
        $success = $insert_stmt->execute();
        $insert_stmt->close();
        
        return $success ? "Admin account created successfully!" : "Error creating admin account: " . $conn->error;
    }
}

// Function to display the form
function display_form($message = '', $success = false) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Account Fix - ElectroShop</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 {
                color: #333;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
            .message {
                padding: 10px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            form {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            input[type="email"],
            input[type="password"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            button {
                background: #4CAF50;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 4px;
                cursor: pointer;
            }
            button:hover {
                background: #45a049;
            }
            .warning {
                margin-top: 20px;
                padding: 10px;
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <h1>ElectroShop Admin Account Fix</h1>';
        
    if (!empty($message)) {
        $class = $success ? 'success' : 'error';
        echo '<div class="message ' . $class . '">' . $message . '</div>';
    }
    
    echo '<form method="post" action="">
            <div>
                <label for="email">Admin Email:</label>
                <input type="email" id="email" name="email" value="admin@electroshop.com" required>
            </div>
            <div>
                <label for="password">New Admin Password:</label>
                <input type="password" id="password" name="password" value="admin123" required>
            </div>
            <button type="submit" name="create_admin">Create/Reset Admin Account</button>
        </form>
        
        <div class="warning">
            <strong>Important:</strong> Delete this file after use for security reasons!
        </div>
        
        <p><a href="login.php">Go to login page</a></p>
    </body>
    </html>';
}

// Check if form was submitted
if (isset($_POST['create_admin'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        display_form("Please enter both email and password.", false);
    } else {
        // Create or update admin account
        $result = create_admin_account($conn, $email, $password);
        display_form($result . " You can now log in with these credentials.", strpos($result, "Error") === false);
    }
} else {
    // Check if admin exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        display_form("No admin account found. Use this form to create one.");
    } else {
        display_form("Admin account(s) already exist. You can use this form to reset the admin password if needed.");
    }
}
?>
