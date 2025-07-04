<?php
// This script creates the contact_messages table in the database
// Run this script once to set up the necessary table for the contact form

// Include database connection
require_once 'includes/db.php';

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Contact Messages Table - ElectroShop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #6c5ce7;
            border-bottom: 2px solid #6c5ce7;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            background-color: #6c5ce7;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #5649c0;
        }
    </style>
</head>
<body>
    <h1>ElectroShop Contact Messages Table Setup</h1>

<?php
// Check if the contact_messages table already exists
if (tableExists($conn, 'contact_messages')) {
    echo '<div class="success">The <code>contact_messages</code> table already exists in the database.</div>';
    echo '<div class="warning">If you\'re experiencing issues with the contact form, the table structure might be incorrect. You can drop the table and run this script again to recreate it.</div>';
} else {
    // Create the contact_messages table
    $sql = "CREATE TABLE contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
        notes TEXT
    )";

    if ($conn->query($sql) === TRUE) {
        echo '<div class="success">The <code>contact_messages</code> table has been created successfully!</div>';
    } else {
        echo '<div class="error">Error creating table: ' . $conn->error . '</div>';
    }
}

// Show the table structure
echo '<h2>Table Structure</h2>';
echo '<p>Here is the structure of the <code>contact_messages</code> table:</p>';
echo '<pre>';
$result = $conn->query("DESCRIBE contact_messages");
if ($result) {
    echo "Field\t\t\tType\t\t\tNull\tKey\tDefault\t\tExtra\n";
    echo "-------------------------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\t\t\t" . 
             $row['Type'] . "\t\t" . 
             $row['Null'] . "\t" . 
             $row['Key'] . "\t" . 
             $row['Default'] . "\t\t" . 
             $row['Extra'] . "\n";
    }
} else {
    echo "Could not retrieve table structure: " . $conn->error;
}
echo '</pre>';

// Close the database connection
$conn->close();
?>

    <h2>Next Steps</h2>
    <p>Now that the contact_messages table has been set up, your contact form should work correctly. Here's what you should do next:</p>
    <ol>
        <li>Test your contact form by submitting a message</li>
        <li>Check that the message is being stored in the database</li>
        <li>For security reasons, <strong>delete this file</strong> from your server after confirming everything works</li>
    </ol>

    <h2>Troubleshooting</h2>
    <p>If you're still experiencing issues with the contact form, check the following:</p>
    <ul>
        <li>Make sure your database connection settings in <code>includes/db.php</code> are correct</li>
        <li>Check that the form in <code>contact.php</code> is submitting to the correct URL</li>
        <li>Verify that the field names in the form match the column names in the database</li>
        <li>Enable error reporting in PHP to see more detailed error messages</li>
    </ul>

    <a href="contact.php" class="btn">Go to Contact Page</a>
</body>
</html>
