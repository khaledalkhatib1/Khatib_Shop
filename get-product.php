<?php
require_once 'includes/db.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product_id = $_GET['id'];

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();

// Return product data as JSON
header('Content-Type: application/json');
echo json_encode($product);
