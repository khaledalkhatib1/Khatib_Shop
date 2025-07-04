<?php
// This script creates the user_tokens table if it doesn't exist
require_once 'includes/db.php';

// Check if the table already exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_tokens'");
if ($table_check->num_rows == 0) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE IF NOT EXISTS user_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (token),
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'user_tokens' created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} else {
    echo "Table 'user_tokens' already exists";
}

$conn->close();
?>
