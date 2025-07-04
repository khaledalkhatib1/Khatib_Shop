<?php
// Include database connection
require_once 'includes/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>ElectroShop Database Update</h1>";
echo "<h2>Adding Billing Address Column</h2>";

// Check database connection
if ($conn->connect_error) {
    die("<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
        <h3>Database Connection Failed</h3>
        <p>Error: " . $conn->connect_error . "</p>
        <p>Please check your database connection settings in includes/db.php</p>
    </div>");
}

echo "<div style='color: green;'>Database connection successful!</div>";

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result->num_rows > 0;
}

// Check if orders table exists
$table_check = $conn->query("SHOW TABLES LIKE 'orders'");
if ($table_check->num_rows == 0) {
    echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
        <h3>Orders Table Missing</h3>
        <p>The 'orders' table does not exist. Please run the main database setup script first.</p>
    </div>";
    exit;
}

// Add billing_address column if it doesn't exist
if (!columnExists($conn, 'orders', 'billing_address')) {
    $sql = "ALTER TABLE orders ADD COLUMN billing_address TEXT AFTER shipping_address";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; padding: 10px; background-color: #eeffee; border: 1px solid #aaffaa;'>
            <h3>Billing Address Column Added Successfully</h3>
            <p>The 'billing_address' column has been added to the orders table.</p>
        </div>";
    } else {
        echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
            <h3>Error Adding Billing Address Column</h3>
            <p>Error: " . $conn->error . "</p>
            <p>SQL: $sql</p>
        </div>";
    }
} else {
    echo "<div style='color: blue; padding: 10px; background-color: #eeeeff; border: 1px solid #aaaaff;'>
        <h3>Billing Address Column Already Exists</h3>
        <p>The 'billing_address' column already exists in the orders table.</p>
    </div>";
}

// Show current table structure
$result = $conn->query("DESCRIBE orders");
if ($result) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd;'>
        <h3>Current Orders Table Structure:</h3>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr style='background-color: #f1f1f1;'>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Null</th>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Key</th>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Default</th>
                <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Extra</th>
            </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Field'] . "</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Type'] . "</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Null'] . "</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Key'] . "</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Default'] . "</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . $row['Extra'] . "</td>
        </tr>";
    }
    
    echo "</table></div>";
}

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd;'>
    <h3>Next Steps</h3>
    <p>You can now <a href='checkout.php' style='color: blue; text-decoration: underline;'>go to checkout</a> to test the functionality.</p>
    <p><strong>Important:</strong> Delete this file after confirming everything works correctly.</p>
</div>";
?>
