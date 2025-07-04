<?php
// Include database connection
require_once 'includes/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>ElectroShop Database Setup</h1>";
echo "<h2>Creating Orders Tables</h2>";

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result->num_rows > 0;
}

// Check database connection
if ($conn->connect_error) {
    die("<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
        <h3>Database Connection Failed</h3>
        <p>Error: " . $conn->connect_error . "</p>
        <p>Please check your database connection settings in includes/db.php</p>
    </div>");
}

echo "<div style='color: green;'>Database connection successful!</div>";

// Check if users table exists
if (!tableExists($conn, 'users')) {
    echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
        <h3>Users Table Missing</h3>
        <p>The 'users' table does not exist. This is required for the orders system.</p>
        <p>Please run the main database setup script first.</p>
    </div>";
    exit;
}

// Check if products table exists
if (!tableExists($conn, 'products')) {
    echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
        <h3>Products Table Missing</h3>
        <p>The 'products' table does not exist. This is required for the orders system.</p>
        <p>Please run the main database setup script first.</p>
    </div>";
    exit;
}

// Create orders table without foreign key constraints first
if (!tableExists($conn, 'orders')) {
    $sql = "CREATE TABLE orders (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        shipping_address TEXT NOT NULL,
        billing_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; padding: 10px; background-color: #eeffee; border: 1px solid #aaffaa;'>
            <h3>Orders Table Created Successfully</h3>
            <p>The 'orders' table has been created in your database.</p>
        </div>";
    } else {
        echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
            <h3>Error Creating Orders Table</h3>
            <p>Error: " . $conn->error . "</p>
            <p>SQL: $sql</p>
        </div>";
    }
} else {
    echo "<div style='color: blue; padding: 10px; background-color: #eeeeff; border: 1px solid #aaaaff;'>
        <h3>Orders Table Already Exists</h3>
        <p>The 'orders' table already exists in your database.</p>
    </div>";
}

// Create order_items table without foreign key constraints
if (!tableExists($conn, 'order_items')) {
    $sql = "CREATE TABLE order_items (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; padding: 10px; background-color: #eeffee; border: 1px solid #aaffaa;'>
            <h3>Order Items Table Created Successfully</h3>
            <p>The 'order_items' table has been created in your database.</p>
        </div>";
    } else {
        echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
            <h3>Error Creating Order Items Table</h3>
            <p>Error: " . $conn->error . "</p>
            <p>SQL: $sql</p>
        </div>";
    }
} else {
    echo "<div style='color: blue; padding: 10px; background-color: #eeeeff; border: 1px solid #aaaaff;'>
        <h3>Order Items Table Already Exists</h3>
        <p>The 'order_items' table already exists in your database.</p>
    </div>";
}

// Check if products table has stock column
if (tableExists($conn, 'products') && !columnExists($conn, 'products', 'stock')) {
    $sql = "ALTER TABLE products ADD COLUMN stock INT(11) NOT NULL DEFAULT 10";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; padding: 10px; background-color: #eeffee; border: 1px solid #aaffaa;'>
            <h3>Stock Column Added to Products Table</h3>
            <p>The 'stock' column has been added to the products table with a default value of 10.</p>
        </div>";
    } else {
        echo "<div style='color: red; padding: 10px; background-color: #ffeeee; border: 1px solid #ffaaaa;'>
            <h3>Error Adding Stock Column</h3>
            <p>Error: " . $conn->error . "</p>
            <p>SQL: $sql</p>
        </div>";
    }
}

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd;'>
    <h3>Setup Complete</h3>
    <p>The database tables for the checkout system have been created successfully.</p>
    <p>You can now <a href='checkout.php' style='color: blue; text-decoration: underline;'>go to checkout</a> to test the functionality.</p>
    <p><strong>Important:</strong> Delete this file after confirming everything works correctly.</p>
</div>";
?>
